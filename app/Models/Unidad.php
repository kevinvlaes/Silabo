<?php
require_once __DIR__ . '/../Core/DB.php';

class Unidad {
    private static function db() {
        return DB::get();
    }

    /** Lista oficial de UD por programa/semestre (tabla unidades_didacticas) */
    public static function porProgramaSemestre(string $programa, string $semestre): array {
        $sql = "SELECT nombre
                  FROM unidades_didacticas
                 WHERE programa = :prog
                   AND semestre = :sem
              ORDER BY nombre";
        $st = self::db()->prepare($sql);
        $st->execute([
            ':prog' => $programa,
            ':sem'  => $semestre
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
