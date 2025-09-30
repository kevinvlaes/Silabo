<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Asignacion.php';

class AsignacionController
{
    /* ============================================================
     * LISTA / VISTA
     * ========================================================== */
    public function index(): void
    {
        Auth::check();

        // Año seleccionado (o null = todos)
        $anioSel = (isset($_GET['anio']) && $_GET['anio'] !== '')
            ? (int)$_GET['anio'] : null;

        // Usuarios para el combo
        $usuarios = self::getUsuarios();

        // Programas para el combo (intenta usar el helper del modelo;
        // si no existe, hace fallback directo a la tabla de UDs)
        if (method_exists('Asignacion', 'programasCatalogo')) {
            $programas = Asignacion::programasCatalogo();
        } else {
            $pdo = Database::pdo();
            $st  = $pdo->query("SELECT DISTINCT programa FROM unidades_didacticas ORDER BY programa");
            $programas = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        // Semestres fijos
        $semestres = ['I','II','III','IV','V','VI'];

        // Asignaciones (filtradas por año si llega)
        $asignaciones = Asignacion::listarPorAnio($anioSel);

        // Años para el selector superior
        $aniosDisponibles = self::aniosParaSelector();

        include __DIR__ . '/../Views/asignaciones.php';
    }

    /* ============================================================
     * API: Lista de UDs por programa/semestre (para el combo)
     * ========================================================== */
    public function api_unidades(): void
    {
        Auth::check();
        header('Content-Type: application/json; charset=utf-8');

        $programa = trim((string)($_GET['programa'] ?? ''));
        $semestre = trim((string)($_GET['semestre'] ?? ''));
        // anio se acepta pero no es necesario para el catálogo
        if ($programa === '' || $semestre === '') {
            echo json_encode([]);
            return;
        }

        try {
            $pdo = Database::pdo();
            $st  = $pdo->prepare(
                'SELECT nombre
                   FROM unidades_didacticas
                  WHERE programa = ? AND semestre = ?
               ORDER BY nombre'
            );
            $st->execute([$programa, $semestre]);
            $nombres = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
            echo json_encode($nombres);
        } catch (\Throwable $e) {
            echo json_encode([]);
        }
    }

    /* ============================================================
     * CREAR ASIGNACIÓN
     * ========================================================== */
    public function crear(): void
    {
        Auth::check();

        // Siempre redirigimos de vuelta a la vista de asignaciones
        $anioBack = isset($_POST['anio']) ? (int)$_POST['anio'] : (isset($_GET['anio']) ? (int)$_GET['anio'] : 0);

        // Solo por POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::flash('flash_error', 'Solicitud inválida.');
            header('Location: index.php?action=asignaciones' . ($anioBack ? '&anio='.$anioBack : ''));
            return;
        }

        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        $programa  = trim((string)($_POST['programa']   ?? ''));
        $anio      = (int)($_POST['anio']               ?? 0);
        $semestre  = trim((string)($_POST['semestre']   ?? ''));
        $unidad    = trim((string)($_POST['unidad']     ?? ''));

        if ($usuarioId <= 0 || $programa === '' || $anio <= 0 || $semestre === '' || $unidad === '') {
            self::flash('flash_error', 'Completa todos los datos para registrar la asignación.');
            header('Location: index.php?action=asignaciones' . ($anioBack ? '&anio='.$anioBack : ''));
            return;
        }

        try {
            // Usa las reglas del modelo (incluye verificación de “UD ya ocupada”)
            Asignacion::crear($usuarioId, $programa, $anio, $semestre, $unidad);
            self::flash('flash_ok', 'Asignación creada correctamente.');
        } catch (\RuntimeException $e) {
            self::flash('flash_error', $e->getMessage());
        } catch (\Throwable $e) {
            self::flash('flash_error', 'No se pudo crear la asignación. '.$e->getMessage());
        }

        header('Location: index.php?action=asignaciones' . ($anioBack ? '&anio='.$anioBack : ''));
    }

    /* ============================================================
     * ELIMINAR ASIGNACIÓN
     * ========================================================== */
    public function eliminar(): void
    {
        Auth::check();

        $anioBack = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            self::flash('flash_error', 'ID inválido.');
            header('Location: index.php?action=asignaciones' . ($anioBack ? '&anio='.$anioBack : ''));
            return;
        }

        try {
            Asignacion::eliminar($id);
            self::flash('flash_ok', 'Asignación eliminada.');
        } catch (\Throwable $e) {
            self::flash('flash_error', 'No se pudo eliminar. '.$e->getMessage());
        }

        header('Location: index.php?action=asignaciones' . ($anioBack ? '&anio='.$anioBack : ''));
    }

    /* ============================================================
     * Helpers internos
     * ========================================================== */
    private static function flash(string $key, string $msg): void
    {
        $_SESSION[$key] = $msg;
    }

    private static function getUsuarios(): array
    {
        $pdo = Database::pdo();
        $st  = $pdo->query("SELECT id, nombre, rol, email FROM usuarios ORDER BY nombre");
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private static function aniosParaSelector(): array
    {
        // intenta sacar de la tabla anios_academicos; si no existe, usa últimos 6
        try {
            $pdo = Database::pdo();
            $st = $pdo->query("SELECT DISTINCT anio FROM anios_academicos ORDER BY anio DESC");
            $res = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
            if ($res) return $res;
        } catch (\Throwable $e) { /* ignore */ }

        $y = (int)date('Y'); $out=[];
        for ($i=0; $i<6; $i++) $out[] = $y-$i;
        return $out;
    }
}
