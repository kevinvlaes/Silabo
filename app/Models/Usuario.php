<?php
require_once __DIR__."/../Core/Database.php";

class Usuario {
    public static function buscarPorEmail($email) {
        $stmt = Database::pdo()->prepare("SELECT * FROM usuarios WHERE email=?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function crear($nombre, $email, $password, $rol, $programa = null, $anio_labor = null) {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO usuarios (nombre,email,password,rol,programa,anio_labor)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute([$nombre, $email, $password, $rol, $programa, $anio_labor]);
    }

    public static function todos() {
        $stmt = Database::pdo()->query("SELECT id, nombre, email, rol, programa, anio_labor FROM usuarios ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarPorId($id) {
        $stmt = Database::pdo()->prepare("SELECT * FROM usuarios WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function actualizar($id, $nombre, $email, $rol, $password = null, $programa = null, $anio_labor = null) {
        if ($password) {
            $stmt = Database::pdo()->prepare(
                "UPDATE usuarios SET nombre=?, email=?, rol=?, password=?, programa=?, anio_labor=? WHERE id=?"
            );
            $stmt->execute([$nombre, $email, $rol, $password, $programa, $anio_labor, $id]);
        } else {
            $stmt = Database::pdo()->prepare(
                "UPDATE usuarios SET nombre=?, email=?, rol=?, programa=?, anio_labor=? WHERE id=?"
            );
            $stmt->execute([$nombre, $email, $rol, $programa, $anio_labor, $id]);
        }
    }

    public static function eliminar($id) {
        $stmt = Database::pdo()->prepare("DELETE FROM usuarios WHERE id=?");
        $stmt->execute([$id]);
    }
}
