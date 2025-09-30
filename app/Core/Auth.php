<?php
class Auth {
    public static function check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['usuario'])) {
            header("Location: index.php?action=loginForm");
            exit;
        }
        // Bloquear usuarios inactivos aunque consigan sesión
        $estado = $_SESSION['usuario']['estado'] ?? 'activo';
        if ($estado !== 'activo') {
            self::logout();
            header("Location: index.php?action=loginForm");
            exit;
        }
    }

    public static function user() {
        return $_SESSION['usuario'] ?? null;
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /** Requiere 1 o varios roles */
    public static function requireRole($roles) {
        self::check();
        $roles = (array)$roles;
        $user  = $_SESSION['usuario'] ?? [];
        $rol   = $user['rol'] ?? '';
        if (!in_array($rol, $roles, true)) {
            header('Location: index.php?action=dashboard');
            exit;
        }
    }
}
