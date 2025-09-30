<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Database.php';

class Anio
{
    /** Crea la tabla si no existe */
    private static function ensure(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS anios_academicos (
                anio INT PRIMARY KEY
            )
        ");
    }

    /** Devuelve TODOS los años académicos registrados (desc) */
    public static function todos(): array
    {
        self::ensure();
        $pdo = Database::pdo();
        $st  = $pdo->query("SELECT anio FROM anios_academicos ORDER BY anio DESC");
        $rows = $st->fetchAll(PDO::FETCH_COLUMN);
        return $rows ? array_map('intval', $rows) : [];
    }

    /** Agrega un año (si ya existe, lo ignora) */
    public static function agregar(int $anio): void
    {
        self::ensure();
        $pdo = Database::pdo();
        $st  = $pdo->prepare("INSERT IGNORE INTO anios_academicos (anio) VALUES (?)");
        $st->execute([$anio]);
    }

    /** Elimina un año (opcional) */
    public static function eliminar(int $anio): void
    {
        self::ensure();
        $pdo = Database::pdo();
        $st  = $pdo->prepare("DELETE FROM anios_academicos WHERE anio = ?");
        $st->execute([$anio]);
    }
}
