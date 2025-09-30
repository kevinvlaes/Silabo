<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/Mailer.php';
require_once __DIR__ . '/../Models/PasswordReset.php';

class AuthController
{
    public function loginForm(): void
    {
        include __DIR__ . '/../Views/login.php';
    }

    public function login(string $email, string $password): void
    {
        $email    = strtolower(trim($email));
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

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['flash_error'] = 'Credenciales inválidas.';
            header('Location: index.php?action=loginForm');
            return;
        }

        if (($user['estado'] ?? 'activo') !== 'activo') {
            $_SESSION['flash_error'] = 'Usuario inactivo. Consulta con Jefatura.';
            header('Location: index.php?action=loginForm');
            return;
        }

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

    /* ====================== RECUPERACIÓN ====================== */

    public function forgotForm(): void
    {
        include __DIR__ . '/../Views/forgot.php';
    }

    public function forgot(): void
    {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if ($email === '') {
            $_SESSION['flash_error'] = 'Ingresa tu correo.';
            header('Location: index.php?action=forgotForm');
            return;
        }

        try {
            $pdo = Database::pdo();
            $st  = $pdo->prepare("SELECT id, email, nombre FROM usuarios WHERE email = ? LIMIT 1");
            $st->execute([$email]);
            $user = $st->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $user = null; // tratamos igual para no filtrar existencia
        }

        // Para no revelar si existe o no, mostramos siempre "enviado"
        if ($user) {
            $token = PasswordReset::createToken((int)$user['id']);
            $config = require __DIR__ . '/../Config/config.php';
            $appUrl = rtrim($config['app_url'] ?? '', '/');
            $link   = $appUrl . '/index.php?action=resetForm&token=' . urlencode($token);

            $html = '<p>Hola ' . htmlspecialchars($user['nombre'] ?? '', ENT_QUOTES, 'UTF-8') . ',</p>';
            $html .= '<p>Hemos recibido una solicitud para restablecer tu contraseña.</p>';
            $html .= '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Haz clic aquí para crear una nueva contraseña</a></p>';
            $html .= '<p>El enlace expira en 60 minutos.</p>';
            $html .= '<p>Si no solicitaste esto, ignora este mensaje.</p>';

            (new Mailer())->send($user['email'], 'Restablecer contraseña', $html);
        }

        include __DIR__ . '/../Views/forgot_sent.php';
    }

    public function resetForm(): void
    {
        $token = (string)($_GET['token'] ?? '');
        $row = ($token !== '') ? PasswordReset::findValid($token) : null;
        if (!$row) {
            $_SESSION['flash_error'] = 'Enlace inválido o expirado.';
        }
        include __DIR__ . '/../Views/reset.php';
    }

    public function reset(): void
    {
        $token    = (string)($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm'] ?? '');

        if ($token === '' || $password === '' || $confirm === '') {
            $_SESSION['flash_error'] = 'Completa todos los campos.';
            header('Location: index.php?action=resetForm&token=' . urlencode($token));
            return;
        }
        if ($password !== $confirm) {
            $_SESSION['flash_error'] = 'Las contraseñas no coinciden.';
            header('Location: index.php?action=resetForm&token=' . urlencode($token));
            return;
        }
        if (strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Usa al menos 6 caracteres.';
            header('Location: index.php?action=resetForm&token=' . urlencode($token));
            return;
        }

        $row = PasswordReset::findValid($token);
        if (!$row) {
            $_SESSION['flash_error'] = 'Enlace inválido o expirado.';
            header('Location: index.php?action=forgotForm');
            return;
        }

        // Actualiza contraseña del usuario
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo  = Database::pdo();
            $st   = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $st->execute([$hash, (int)$row['usuario_id']]);
            PasswordReset::markUsed((int)$row['id']);
            $_SESSION['flash_ok'] = 'Tu contraseña ha sido actualizada. Inicia sesión.';
            header('Location: index.php?action=loginForm');
            return;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo actualizar la contraseña.';
            header('Location: index.php?action=resetForm&token=' . urlencode($token));
            return;
        }
    }
}
