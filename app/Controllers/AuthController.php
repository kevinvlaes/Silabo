<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';

class AuthController
{
    public function loginForm(): void
    {
        // Ajusta el path si tu vista de login tiene otro nombre/ubicación
        include __DIR__ . '/../Views/login.php';
    }

    public function login(string $email, string $password): void
    {
        $email    = trim($email);
        $password = trim($password);

        if ($email === '' || $password === '') {
            $_SESSION['flash_error'] = 'Ingresa tu correo y contraseña.';
            header('Location: index.php?action=loginForm');
            return;
        }

        try {
            $pdo = Database::pdo();
            $st  = $pdo->prepare(
                "SELECT id, nombre, email, password, rol, programa, anio, estado
                   FROM usuarios
                  WHERE email = ?
                  LIMIT 1"
            );
            $st->execute([$email]);
            $user = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Error de conexión.';
            header('Location: index.php?action=loginForm');
            return;
        }

        // Credenciales
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['flash_error'] = 'Credenciales inválidas.';
            header('Location: index.php?action=loginForm');
            return;
        }

        // >>> Bloqueo por estado inactivo <<<
        if (($user['estado'] ?? 'activo') !== 'activo') {
            $_SESSION['flash_error'] = 'Usuario inactivo. Consulta con Jefatura.';
            header('Location: index.php?action=loginForm');
            return;
        }

        // OK: crear sesión
        $_SESSION['usuario'] = [
            'id'       => (int)$user['id'],
            'nombre'   => $user['nombre'],
            'email'    => $user['email'],
            'rol'      => $user['rol'],
            'programa' => $user['programa'],
            'anio'     => $user['anio'],
            'estado'   => $user['estado'],
        ];

        header('Location: index.php?action=dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: index.php?action=loginForm');
    }
}
