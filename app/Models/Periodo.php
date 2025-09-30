<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Database.php';

class Periodo
{
    /* ============================================================
     * Infraestructura / utilidades
     * ============================================================ */

    /**
     * Crea la tabla multi-año si no existe.
     * anio es la PK para evitar filas duplicadas por año.
     */
    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS periodo_subida (
                anio    INT UNSIGNED NOT NULL PRIMARY KEY,
                inicio  DATETIME NULL,
                fin     DATETIME NULL,
                lectivo VARCHAR(10) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Devuelve un año seguro (1900..3000).
     * Orden de fuentes: argumento -> $_GET['anio'] -> $_SESSION['anio_contexto'] -> año actual.
     */
    private static function normalizarAnio($anio): int
    {
        // 1) argumento directo
        if (is_int($anio) && $anio >= 1900 && $anio <= 3000) {
            return $anio;
        }
        if (is_string($anio) && ctype_digit($anio)) {
            $v = (int)$anio;
            if ($v >= 1900 && $v <= 3000) return $v;
        }

        // 2) GET
        if (isset($_GET['anio'])) {
            $g = $_GET['anio'];
            if (is_string($g) && ctype_digit($g)) {
                $v = (int)$g;
                if ($v >= 1900 && $v <= 3000) return $v;
            }
        }

        // 3) Sesión
        if (isset($_SESSION['anio_contexto'])) {
            $s = $_SESSION['anio_contexto'];
            if ((is_int($s) || (is_string($s) && ctype_digit($s)))) {
                $v = (int)$s;
                if ($v >= 1900 && $v <= 3000) return $v;
            }
        }

        // 4) Fallback
        return (int)date('Y');
    }

    /**
     * Normaliza el lectivo a 'I' o 'II' (acepta 'YYYY-I/II' o 'I/II').
     */
    private static function normalizeLectivo(?string $lectivo): ?string
    {
        if ($lectivo === null || $lectivo === '') return null;

        $lectivo = trim($lectivo);
        if (preg_match('/^[0-9]{4}\-(I|II)$/i', $lectivo, $m)) {
            return strtoupper($m[1]); // I o II
        }
        if (preg_match('/^(I|II)$/i', $lectivo)) {
            return strtoupper($lectivo);
        }
        return null;
    }

    /**
     * Lectivo por defecto para un año dado.
     * - Si no es el año actual => 'I'
     * - Si es el año actual => I (ene-jun) / II (jul-dic)
     */
    private static function defaultLectivoForYear(int $anio): string
    {
        if ($anio !== (int)date('Y')) {
            return 'I';
        }
        return ((int)date('n') <= 6) ? 'I' : 'II';
    }

    /* ============================================================
     * API pública del modelo
     * ============================================================ */

    /**
     * Devuelve la fila de periodo para el año indicado (y crea stub si no existe).
     * @return array{anio:int,inicio:?string,fin:?string,lectivo:?string}
     */
    public static function actual(?int $anio = null): array
    {
        $anio = self::normalizarAnio($anio);
        $_SESSION['anio_contexto'] = $anio; // fija el contexto de año

        $pdo = Database::pdo();
        self::ensureTable($pdo);

        $st = $pdo->prepare("SELECT anio, inicio, fin, lectivo FROM periodo_subida WHERE anio = ?");
        $st->execute([$anio]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Crea stub seguro (nunca 0) para el año
            $ins = $pdo->prepare("INSERT INTO periodo_subida (anio, inicio, fin, lectivo) VALUES (?, NULL, NULL, NULL)");
            $ins->execute([$anio]);
            $row = ['anio' => $anio, 'inicio' => null, 'fin' => null, 'lectivo' => null];
        }

        return $row;
    }

    /**
     * Guarda/actualiza el periodo de un año (upsert manual).
     * $lectivo acepta 'I'/'II' o 'YYYY-I'/'YYYY-II'.
     */
    public static function guardar(int $anio, ?string $inicio, ?string $fin, ?string $lectivo): void
    {
        $anio = self::normalizarAnio($anio);
        $_SESSION['anio_contexto'] = $anio;

        $pdo = Database::pdo();
        self::ensureTable($pdo);

        $lect = self::normalizeLectivo($lectivo);

        // ¿existe fila?
        $ex = $pdo->prepare("SELECT 1 FROM periodo_subida WHERE anio = ?");
        $ex->execute([$anio]);
        $exists = (bool)$ex->fetchColumn();

        if ($exists) {
            $st = $pdo->prepare("UPDATE periodo_subida SET inicio = ?, fin = ?, lectivo = ? WHERE anio = ?");
            $st->execute([$inicio, $fin, $lect, $anio]);
        } else {
            $st = $pdo->prepare("INSERT INTO periodo_subida (anio, inicio, fin, lectivo) VALUES (?, ?, ?, ?)");
            $st->execute([$anio, $inicio, $fin, $lect]);
        }
    }

    /**
     * ¿La ventana de subida del año está abierta ahora?
     * Considera inclusivo inicio/fin.
     */
    public static function abiertoAhora(?int $anio = null): bool
    {
        $p = self::actual($anio);
        if (empty($p['inicio']) || empty($p['fin'])) {
            return false;
        }

        $tz  = new DateTimeZone(date_default_timezone_get());
        $ini = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$p['inicio'], $tz);
        $fin = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$p['fin'], $tz);
        if (!$ini || !$fin) {
            // si se guardó en otro formato, no habilitamos
            return false;
        }

        $now = new DateTimeImmutable('now', $tz);
        return ($now >= $ini) && ($now <= $fin);
    }

    /**
     * Devuelve el lectivo completo "YYYY-I" o "YYYY-II".
     * Si no hay lectivo guardado, usa uno por defecto.
     */
    public static function lectivo(?int $anio = null): string
    {
        $anio  = self::normalizarAnio($anio);
        $row   = self::actual($anio);
        $letra = self::normalizeLectivo($row['lectivo']) ?? self::defaultLectivoForYear($anio);
        return "{$anio}-{$letra}";
    }

    /**
     * Devuelve los semestres habilitados según el lectivo guardado:
     *  - 'I'  => ['I','III','V']
     *  - 'II' => ['II','IV','VI']
     *  - null => todos
     */
    public static function semestresDelPeriodo(?int $anio = null): array
    {
        $anio  = self::normalizarAnio($anio);
        $row   = self::actual($anio);
        $letra = self::normalizeLectivo($row['lectivo']);

        if ($letra === 'I')  return ['I', 'III', 'V'];
        if ($letra === 'II') return ['II', 'IV', 'VI'];

        // Sin lectivo definido: todos
        return ['I', 'II', 'III', 'IV', 'V', 'VI'];
    }

    /* ============================================================
     * Aliases de compatibilidad (para controladores antiguos)
     * ============================================================ */

    /** Alias del método moderno abiertoAhora(). */
    public static function abiertoParaAnio(int $anio): bool
    {
        return self::abiertoAhora($anio);
    }

    /** Alias del método moderno lectivo(). */
    public static function lectivoParaAnio(int $anio): string
    {
        return self::lectivo($anio);
    }

    /** Alias del método moderno semestresDelPeriodo(). */
    public static function semestresParaAnio(int $anio): array
    {
        return self::semestresDelPeriodo($anio);
    }
}
