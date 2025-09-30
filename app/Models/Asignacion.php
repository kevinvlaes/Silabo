<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Database.php';

class Asignacion
{
    /* ============================================================
     * Infraestructura
     * ========================================================== */

    /** Crea la tabla base si no existe (no altera si ya existe). */
    private static function ensureTable(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS asignaciones (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT UNSIGNED NOT NULL,
                programa VARCHAR(255) NOT NULL,
                anio INT NOT NULL,
                semestre VARCHAR(10) NOT NULL,
                /* La columna de UD puede variar entre instalaciones. */
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX ix_asig_usuario (usuario_id),
                INDEX ix_asig_anio (anio),
                INDEX ix_asig_prog (programa)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /** Verifica si existe una columna en la tabla `asignaciones`. */
    private static function columnExists(string $column): bool
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare("
            SELECT COUNT(*)
              FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'asignaciones'
               AND COLUMN_NAME = ?
        ");
        $st->execute([$column]);
        return (bool)$st->fetchColumn();
    }

    /**
     * Devuelve el nombre real de la columna "Unidad Didáctica".
     * Orden de búsqueda: unidad, ud_nombre, unidad_didactica, ud.
     * Si no hay ninguna, intenta crear `unidad` (VARCHAR(255)).
     */
    private static function udColumn(): string
    {
        $candidatas = ['unidad', 'ud_nombre', 'unidad_didactica', 'ud'];
        foreach ($candidatas as $c) {
            if (self::columnExists($c)) {
                return $c;
            }
        }

        // Intentamos crear 'unidad' si no existe ninguna
        try {
            $pdo = Database::pdo();
            $pdo->exec("ALTER TABLE asignaciones ADD COLUMN unidad VARCHAR(255) NULL AFTER semestre");
            return 'unidad';
        } catch (\Throwable $e) {
            // Si no se pudo crear por permisos/versión, devolvemos igualmente 'unidad'
            // (deberás crearla/renombrarla manualmente).
            return 'unidad';
        }
    }

    /**
     * Crea el índice único (si no existe) que impide duplicados:
     * (programa, semestre, <UD>, anio)
     */
    private static function ensureUniqueGuard(string $udCol): void
    {
        $pdo = Database::pdo();

        // ¿Existe el índice?
        $check = $pdo->prepare("
            SELECT COUNT(*)
              FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'asignaciones'
               AND INDEX_NAME   = 'uniq_asig_ud'
        ");
        $check->execute();
        $exists = (bool)$check->fetchColumn();

        if ($exists) {
            return;
        }

        // Crear índice único de forma segura
        try {
            $pdo->exec("CREATE UNIQUE INDEX uniq_asig_ud ON asignaciones (programa, semestre, `$udCol`, anio)");
        } catch (\Throwable $e) {
            // Si falla porque hay duplicados o por versión, lo ignoramos aquí.
            // La verificación lógica en crear() evitará el duplicado de todos modos.
        }
    }

    /* ============================================================
     * Catálogos
     * ========================================================== */

    /**
     * Catálogo de programas para poblar el combo "Programa".
     * - Intenta primero leer de `unidades_didacticas` (lo ideal).
     * - Si no hay datos o no existe la tabla, hace fallback a `asignaciones`.
     * - Acepta $anio opcional (solo aplica en el fallback).
     */
    public static function programasCatalogo(?int $anio = null): array
    {
        $pdo = Database::pdo();

        // 1) Fuente principal: `unidades_didacticas`
        try {
            $sql = "SELECT DISTINCT programa
                      FROM unidades_didacticas
                     WHERE programa IS NOT NULL AND programa <> ''
                  ORDER BY programa";
            $st  = $pdo->query($sql);
            $res = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
            if ($res) {
                return $res;
            }
        } catch (\Throwable $e) {
            // La tabla podría no existir; continuar al fallback
        }

        // 2) Fallback: `asignaciones` (filtrando por año si fue provisto)
        self::ensureTable();
        try {
            if ($anio !== null) {
                $st = $pdo->prepare(
                    "SELECT DISTINCT programa
                       FROM asignaciones
                      WHERE programa IS NOT NULL AND programa <> '' AND anio = ?
                   ORDER BY programa"
                );
                $st->execute([$anio]);
                return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }

            $st = $pdo->query(
                "SELECT DISTINCT programa
                   FROM asignaciones
                  WHERE programa IS NOT NULL AND programa <> ''
               ORDER BY programa"
            );
            return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* ============================================================
     * Consultas
     * ========================================================== */

    /** Lista asignaciones de un año (si $anio es null, lista todas). */
    public static function listarPorAnio(?int $anio = null): array
    {
        self::ensureTable();
        $pdo   = Database::pdo();
        $udCol = self::udColumn();

        $sql = "SELECT a.id,
                       u.nombre  AS usuario,
                       u.email   AS email,
                       u.rol     AS rol,
                       a.programa,
                       a.anio,
                       a.semestre,
                       a.`$udCol` AS ud_nombre
                  FROM asignaciones a
             LEFT JOIN usuarios u ON u.id = a.usuario_id ";
        $params = [];

        if ($anio !== null) {
            $sql .= "WHERE a.anio = ? ";
            $params[] = $anio;
        }

        $sql .= "ORDER BY u.nombre, a.programa, a.semestre, ud_nombre";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Programas asignados a un usuario en un año. */
    public static function programasAsignados(int $usuarioId, int $anio): array
    {
        self::ensureTable();
        $pdo = Database::pdo();
        $st  = $pdo->prepare(
            "SELECT DISTINCT programa
               FROM asignaciones
              WHERE usuario_id = ? AND anio = ?
           ORDER BY programa"
        );
        $st->execute([$usuarioId, $anio]);
        return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * UDs permitidas al usuario para programa/semestre/año.
     */
    public static function udsPermitidas(
        int $usuarioId,
        string $programa,
        string $semestre,
        int $anio
    ): array {
        self::ensureTable();
        $pdo   = Database::pdo();
        $udCol = self::udColumn();

        $sql = "SELECT `$udCol`
                  FROM asignaciones
                 WHERE usuario_id = ?
                   AND programa   = ?
                   AND semestre   = ?
                   AND anio       = ?
              ORDER BY `$udCol`";
        $st = $pdo->prepare($sql);
        $st->execute([$usuarioId, $programa, $semestre, $anio]);
        return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * ¿Esa combinación exacta ya existe para ese usuario?
     */
    public static function existe(
        int $usuarioId,
        string $programa,
        string $semestre,
        string $unidad,
        int $anio
    ): bool {
        self::ensureTable();
        $pdo   = Database::pdo();
        $udCol = self::udColumn();

        $sql = "SELECT 1
                  FROM asignaciones
                 WHERE usuario_id = ?
                   AND programa   = ?
                   AND semestre   = ?
                   AND `$udCol`   = ?
                   AND anio       = ?
                 LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([$usuarioId, $programa, $semestre, $unidad, $anio]);
        return (bool)$st->fetchColumn();
    }

    /**
     * Devuelve la fila de asignación si la UD YA está ocupada
     * para (programa, semestre, anio) por otro usuario; null si libre.
     */
    public static function udOcupada(
        string $programa,
        string $semestre,
        string $unidad,
        int $anio
    ): ?array {
        self::ensureTable();
        $pdo   = Database::pdo();
        $udCol = self::udColumn();

        $sql = "SELECT a.id, a.usuario_id, u.nombre, u.email, u.rol
                  FROM asignaciones a
             LEFT JOIN usuarios u ON u.id = a.usuario_id
                 WHERE a.programa = ?
                   AND a.semestre = ?
                   AND a.`$udCol` = ?
                   AND a.anio     = ?
                 LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([$programa, $semestre, $unidad, $anio]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* ============================================================
     * Mutaciones
     * ========================================================== */

    /**
     * Crea una asignación.
     * - Verifica si la UD ya está ocupada (por cualquiera).
     * - Crea el índice único si no existe (guardia a nivel BD).
     */
    public static function crear(
        int $usuarioId,
        string $programa,
        int $anio,
        string $semestre,
        string $unidad
    ): void {
        self::ensureTable();
        $pdo   = Database::pdo();
        $udCol = self::udColumn();

        // Guardia lógica: ¿la UD ya está ocupada por alguien?
        $ocupada = self::udOcupada($programa, $semestre, $unidad, $anio);
        if ($ocupada) {
            // Si la única coincidencia es del mismo usuario y misma combinación exacta,
            // intentamos evitar el mensaje de ocupado (es un duplicado exacto).
            if ((int)$ocupada['usuario_id'] !== $usuarioId) {
                $nombre = $ocupada['nombre'] ?? 'otro docente';
                throw new RuntimeException(
                    "Esta Unidad Didáctica ya está asignada a: {$nombre} (Año {$anio}, Semestre {$semestre})."
                );
            }
            // Si es el mismo usuario, igualmente impedimos duplicar la fila exacta:
            if (self::existe($usuarioId, $programa, $semestre, $unidad, $anio)) {
                throw new RuntimeException('Esta asignación ya existe para este usuario.');
            }
        }

        // Guardia a nivel BD: índice único
        self::ensureUniqueGuard($udCol);

        // Inserción
        $sql = "INSERT INTO asignaciones (usuario_id, programa, anio, semestre, `$udCol`)
                VALUES (?, ?, ?, ?, ?)";
        $st = $pdo->prepare($sql);

        try {
            $st->execute([$usuarioId, $programa, $anio, $semestre, $unidad]);
        } catch (\PDOException $e) {
            // Si el índice único existe y salta 23000, devolvemos mensaje amable
            if ($e->getCode() === '23000') {
                throw new RuntimeException(
                    "Esta Unidad Didáctica ya está asignada para (Programa: {$programa}, Semestre: {$semestre}, Año: {$anio})."
                );
            }
            throw $e;
        }
    }

    /** Elimina una asignación por id. */
    public static function eliminar(int $id): void
    {
        self::ensureTable();
        $pdo = Database::pdo();
        $st  = $pdo->prepare("DELETE FROM asignaciones WHERE id = ?");
        $st->execute([$id]);
    }
}
