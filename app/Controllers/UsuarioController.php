<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/Database.php';

class UsuarioController
{
    /** Roles válidos */
    private array $ROLES_VALIDOS = ['admin', 'jefe', 'coordinador', 'docente'];

    /** Estados válidos */
    private array $ESTADOS_VALIDOS = ['activo', 'inactivo'];

    private function requireFullAccess(): void
    {
        Auth::requireRole(['jefe', 'admin']);
    }

    private function emailExiste(PDO $pdo, string $email, ?int $excluirId = null): bool
    {
        if ($excluirId) {
            $st = $pdo->prepare("SELECT 1 FROM usuarios WHERE email = ? AND id <> ? LIMIT 1");
            $st->execute([$email, $excluirId]);
        } else {
            $st = $pdo->prepare("SELECT 1 FROM usuarios WHERE email = ? LIMIT 1");
            $st->execute([$email]);
        }
        return (bool)$st->fetchColumn();
    }

    private function totalAdmins(PDO $pdo): int
    {
        $st = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
        return (int)$st->fetchColumn();
    }

    /* =============== LISTAR =============== */
    public function index(): void
    {
        $this->requireFullAccess();

        $pdo = Database::pdo();
        $usuarios = $pdo->query("SELECT id, nombre, email, rol, programa, anio, estado
                                   FROM usuarios
                               ORDER BY id DESC")
                        ->fetchAll(PDO::FETCH_ASSOC) ?: [];

        include __DIR__ . '/../Views/usuarios.php';
    }

    /* =============== CREAR =============== */
    public function crear(): void
    {
        $this->requireFullAccess();
        $pdo = Database::pdo();

        $nombre   = trim((string)($_POST['nombre'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));
        $rol      = trim((string)($_POST['rol'] ?? ''));
        $programa = trim((string)($_POST['programa'] ?? ''));
        $anio     = trim((string)($_POST['anio'] ?? ''));
        $estado   = trim((string)($_POST['estado'] ?? 'activo'));

        if ($nombre === '' || $email === '' || $password === '' || $rol === '') {
            $_SESSION['flash_error'] = 'Completa nombre, email, contraseña y rol.';
            header('Location: index.php?action=usuarios'); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Email inválido.';
            header('Location: index.php?action=usuarios'); exit;
        }
        if (!in_array($rol, $this->ROLES_VALIDOS, true)) {
            $_SESSION['flash_error'] = 'Rol inválido.';
            header('Location: index.php?action=usuarios'); exit;
        }
        if (!in_array($estado, $this->ESTADOS_VALIDOS, true)) {
            $_SESSION['flash_error'] = 'Estado inválido.';
            header('Location: index.php?action=usuarios'); exit;
        }
        if ($this->emailExiste($pdo, $email)) {
            $_SESSION['flash_error'] = 'Ya existe un usuario con ese email.';
            header('Location: index.php?action=usuarios'); exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $st = $pdo->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol, programa, anio, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $st->execute([
                $nombre,
                $email,
                $hash,
                $rol,
                $programa !== '' ? $programa : null,
                $anio !== '' ? (int)$anio : null,
                $estado
            ]);
            $_SESSION['flash_ok'] = 'Usuario creado correctamente.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo crear el usuario. ' . $e->getMessage();
        }

        header('Location: index.php?action=usuarios');
        exit;
    }

    /* =============== ELIMINAR =============== */
    public function eliminar(): void
    {
        $this->requireFullAccess();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID inválido.';
            header('Location: index.php?action=usuarios'); exit;
        }

        $pdo = Database::pdo();

        $yo = Auth::user();
        if ((int)($yo['id'] ?? 0) === $id) {
            $_SESSION['flash_error'] = 'No puedes eliminar tu propio usuario.';
            header('Location: index.php?action=usuarios'); exit;
        }

        $st = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $st->execute([$id]);
        $rolVictima = $st->fetchColumn();

        if ($rolVictima === 'admin' && $this->totalAdmins($pdo) <= 1) {
            $_SESSION['flash_error'] = 'No puedes eliminar al último administrador.';
            header('Location: index.php?action=usuarios'); exit;
        }

        try {
            $st = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $st->execute([$id]);
            $_SESSION['flash_ok'] = 'Usuario eliminado.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo eliminar el usuario. ' . $e->getMessage();
        }

        header('Location: index.php?action=usuarios');
        exit;
    }

    /* =============== ACTUALIZAR =============== */
    public function actualizar(): void
    {
        $this->requireFullAccess();

        $id       = (int)($_POST['id'] ?? 0);
        $nombre   = trim((string)($_POST['nombre'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));
        $rol      = trim((string)($_POST['rol'] ?? ''));
        $programa = trim((string)($_POST['programa'] ?? ''));
        $anio     = trim((string)($_POST['anio'] ?? ''));
        $estado   = trim((string)($_POST['estado'] ?? 'activo'));

        if ($id <= 0 || $nombre === '' || $email === '' || $rol === '') {
            $_SESSION['flash_error'] = 'Faltan datos obligatorios.';
            header('Location: index.php?action=usuarios'); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Email inválido.';
            header('Location: index.php?action=usuarios'); exit;
        }
        if (!in_array($rol, $this->ROLES_VALIDOS, true)) {
            $_SESSION['flash_error'] = 'Rol inválido.';
            header('Location: index.php?action=usuarios'); exit;
        }
        if (!in_array($estado, $this->ESTADOS_VALIDOS, true)) {
            $_SESSION['flash_error'] = 'Estado inválido.';
            header('Location: index.php?action=usuarios'); exit;
        }

        $pdo = Database::pdo();

        if ($this->emailExiste($pdo, $email, $id)) {
            $_SESSION['flash_error'] = 'Ya existe un usuario con ese email.';
            header('Location: index.php?action=usuarios'); exit;
        }

        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $sql  = "UPDATE usuarios
                            SET nombre=?, email=?, password=?, rol=?, programa=?, anio=?, estado=?
                          WHERE id=?";
                $params = [$nombre, $email, $hash, $rol, $programa ?: null, $anio !== '' ? (int)$anio : null, $estado, $id];
            } else {
                $sql  = "UPDATE usuarios
                            SET nombre=?, email=?, rol=?, programa=?, anio=?, estado=?
                          WHERE id=?";
                $params = [$nombre, $email, $rol, $programa ?: null, $anio !== '' ? (int)$anio : null, $estado, $id];
            }

            // Proteger: no dejar al sistema sin administradores
            if ($rol !== 'admin') {
                $st = $pdo->prepare("SELECT rol FROM usuarios WHERE id=?");
                $st->execute([$id]);
                $rolPrevio = $st->fetchColumn();
                if ($rolPrevio === 'admin' && $this->totalAdmins($pdo) <= 1) {
                    $_SESSION['flash_error'] = 'No puedes quitar el último administrador.';
                    header('Location: index.php?action=usuarios'); exit;
                }
            }

            $st = $pdo->prepare($sql);
            $st->execute($params);
            $_SESSION['flash_ok'] = 'Usuario actualizado.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo actualizar el usuario. ' . $e->getMessage();
        }

        header('Location: index.php?action=usuarios');
        exit;
    }
}
