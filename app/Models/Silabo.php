<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Database.php';

class Silabo
{
    /* =============== Infraestructura y compatibilidad =============== */

    private static function ensureTable(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS silabos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                programa VARCHAR(255) NULL,
                carrera  VARCHAR(255) NULL,
                anio INT NOT NULL,
                semestre VARCHAR(10) NOT NULL,
                ud_nombre VARCHAR(255) NULL,
                unidad_didactica VARCHAR(255) NULL,
                archivo VARCHAR(255) NULL,
                docente_id INT NULL,
                usuario_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX ix_silabos_anio (anio),
                INDEX ix_silabos_prog (programa),
                INDEX ix_silabos_carr (carrera),
                INDEX ix_silabos_sem (semestre)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private static function colExists(string $col): bool
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare("
            SELECT COUNT(*)
              FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'silabos'
               AND COLUMN_NAME  = ?
        ");
        $st->execute([$col]);
        return (bool)$st->fetchColumn();
    }

    private static function colPrograma(): string
    {
        if (self::colExists('programa')) return 'programa';
        if (self::colExists('carrera'))  return 'carrera';
        return 'programa';
    }

    private static function exprPrograma(): string
    {
        $hasProg = self::colExists('programa');
        $hasCarr = self::colExists('carrera');
        if ($hasProg && $hasCarr) return 'COALESCE(programa, carrera)';
        if ($hasProg)             return 'programa';
        if ($hasCarr)             return 'carrera';
        return "''";
    }

    private static function colUd(): string
    {
        if (self::colExists('ud_nombre'))        return 'ud_nombre';
        if (self::colExists('unidad_didactica')) return 'unidad_didactica';
        return 'ud_nombre';
    }

    private static function exprUd(): string
    {
        $hasUd1 = self::colExists('ud_nombre');
        $hasUd2 = self::colExists('unidad_didactica');
        if ($hasUd1 && $hasUd2) return 'COALESCE(ud_nombre, unidad_didactica)';
        if ($hasUd1)            return 'ud_nombre';
        if ($hasUd2)            return 'unidad_didactica';
        return "''";
    }

    private static function colDocente(): string
    {
        if (self::colExists('docente_id')) return 'docente_id';
        if (self::colExists('usuario_id')) return 'usuario_id';
        return 'docente_id';
    }

    /* =============== Consultas =============== */

    /** Consulta pública (para la vista de consulta). */
    public static function filtrar(?string $programa, $anio, ?string $semestre): array
    {
        self::ensureTable();
        $pdo      = Database::pdo();
        $colProg  = self::colPrograma();
        $exprProg = self::exprPrograma();
        $exprUd   = self::exprUd();

        $sql = "
            SELECT id,
                   {$exprProg} AS programa,
                   anio,
                   semestre,
                   {$exprUd} AS ud_nombre,
                   archivo
              FROM silabos
             WHERE 1=1
        ";
        $params = [];

        if ($programa !== null && $programa !== '' && $programa !== 'Todos') {
            if (self::colExists($colProg)) {
                $sql     .= " AND {$colProg} = ? ";
                $params[] = $programa;
            }
        }
        if ($anio !== null && $anio !== '' && $anio !== 'Todos') {
            $sql     .= " AND anio = ? ";
            $params[] = (int)$anio;
        }
        if ($semestre !== null && $semestre !== '' && $semestre !== 'Todos') {
            $sql     .= " AND semestre = ? ";
            $params[] = trim($semestre);
        }

        $sql .= " ORDER BY anio DESC, programa, semestre, ud_nombre ";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Listado general para paneles.
     * - $anio, $programa opcionales
     * - $docenteId: cuando quieras filtrar por el dueño del sílabo
     */
    public static function listar(?int $anio = null, ?string $programa = null, ?int $docenteId = null): array
    {
        self::ensureTable();
        $pdo      = Database::pdo();
        $colProg  = self::colPrograma();
        $colDoc   = self::colDocente();
        $exprProg = self::exprPrograma();
        $exprUd   = self::exprUd();

        $sql = "
            SELECT id,
                   {$exprProg} AS programa,
                   anio,
                   semestre,
                   {$exprUd} AS ud_nombre,
                   archivo
              FROM silabos
             WHERE 1=1
        ";
        $params = [];

        if ($anio !== null) {
            $sql     .= " AND anio = ? ";
            $params[] = $anio;
        }
        if ($programa !== null && $programa !== '') {
            if (self::colExists($colProg)) {
                $sql     .= " AND {$colProg} = ? ";
                $params[] = $programa;
            }
        }
        if ($docenteId !== null && $docenteId > 0 && self::colExists($colDoc)) {
            $sql     .= " AND {$colDoc} = ? ";
            $params[] = $docenteId;
        }

        $sql .= " ORDER BY anio DESC, programa, semestre, ud_nombre ";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /* ------- Wrappers de compatibilidad que suelen llamar los controladores ------- */

    public static function listarTodos(?int $anio = null, ?string $programa = null): array
    {
        return self::listar($anio, $programa, null);
    }

    public static function listarPorUsuario(int $usuarioId, ?int $anio = null, ?string $programa = null): array
    {
        return self::listar($anio, $programa, $usuarioId);
    }

    /**
     * Compatibilidad extra: si algún controlador antiguo llama a un nombre
     * diferente, lo redirigimos aquí para evitar "Undefined method".
     */
    public static function __callStatic($name, $arguments)
    {
        $n = strtolower($name);

        // variantes comunes que he visto en proyectos similares
        if (in_array($n, ['listarporusuario', 'listarpousuario', 'listar_docente', 'listardocente', 'listarporusuarioid'], true)) {
            $usuarioId = (int)($arguments[0] ?? 0);
            $anio      = $arguments[1] ?? null;
            $programa  = $arguments[2] ?? null;
            return self::listarPorUsuario($usuarioId, $anio, $programa);
        }

        if (in_array($n, ['listartodos', 'listar_todos', 'listarall'], true)) {
            $anio     = $arguments[0] ?? null;
            $programa = $arguments[1] ?? null;
            return self::listarTodos($anio, $programa);
        }

        throw new BadMethodCallException("Metodo {$name} no definido en Silabo");
    }

    /* =============== Comprobaciones =============== */

    public static function existeCombo(string $programa, int $anio, string $semestre, string $unidad): bool
    {
        self::ensureTable();
        $pdo     = Database::pdo();
        $colProg = self::colPrograma();
        $colUd   = self::colUd();

        $sql = "SELECT 1
                  FROM silabos
                 WHERE ".(self::colExists($colProg) ? "{$colProg} = ?" : "1=1")."
                   AND anio       = ?
                   AND semestre   = ?
                   AND ".(self::colExists($colUd) ? "{$colUd} = ?" : "1=1")."
                 LIMIT 1";

        $params = [];
        if (self::colExists($colProg)) $params[] = $programa;
        $params[] = $anio;
        $params[] = $semestre;
        if (self::colExists($colUd))   $params[] = $unidad;

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (bool)$st->fetchColumn();
    }

    /* =============== Mutaciones =============== */

    public static function crear(
        string $programa,
        int $anio,
        string $semestre,
        string $unidad,
        string $rutaArchivo,
        ?int $docenteId = null
    ): void {
        self::ensureTable();
        $pdo     = Database::pdo();
        $colProg = self::colPrograma();
        $colUd   = self::colUd();
        $colDoc  = self::colDocente();

        $cols = [];
        $vals = [];
        $q    = [];

        if (self::colExists($colProg)) { $cols[] = $colProg; $vals[] = $programa; $q[]='?'; }
        $cols[] = 'anio';      $vals[] = $anio;      $q[]='?';
        $cols[] = 'semestre';  $vals[] = $semestre;  $q[]='?';
        if (self::colExists($colUd))   { $cols[] = $colUd;   $vals[] = $unidad;   $q[]='?'; }
        $cols[] = 'archivo';   $vals[] = $rutaArchivo;       $q[]='?';
        if ($docenteId !== null && self::colExists($colDoc)) {
            $cols[] = $colDoc; $vals[] = $docenteId; $q[]='?';
        }

        $sql = "INSERT INTO silabos (" . implode(',', $cols) . ")
                VALUES (" . implode(',', $q) . ")";
        $st  = $pdo->prepare($sql);
        $st->execute($vals);
    }
}
