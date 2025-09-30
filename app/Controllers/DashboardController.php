<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Periodo.php';
require_once __DIR__ . '/../Models/Silabo.php';

class DashboardController
{
    public function index(): void
    {
        Auth::check();

        $usuario = $_SESSION['usuario'] ?? [];
        $rol     = $usuario['rol'] ?? '';
        $uid     = isset($usuario['id']) ? (int)$usuario['id'] : 0;

        // Año seleccionado (o vacío = todos)
        $anioSel = (isset($_GET['anio']) && $_GET['anio'] !== '')
            ? (int)$_GET['anio'] : null;

        // Para jefe/admin: todo; para docente/coordinador: por usuario
        if (in_array($rol, ['docente', 'coordinador'], true) && $uid > 0) {
            $silabos = Silabo::listarPorUsuario($uid, $anioSel);
        } else {
            $silabos = Silabo::listarTodos($anioSel);
        }

        // Años para el selector (de la BD si existe tabla anios_academicos; si no, últimos 6)
        $aniosDisponibles = $this->aniosParaSelector();

        // vista
        include __DIR__ . '/../Views/dashboard.php';
    }

    private function aniosParaSelector(): array
    {
        try {
            $pdo = Database::pdo();
            $st  = $pdo->query("SELECT DISTINCT anio FROM anios_academicos ORDER BY anio DESC");
            $rows = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
            if ($rows) return $rows;
        } catch (\Throwable $e) {
            // ignore
        }

        $y = (int)date('Y');
        $out = [];
        for ($i = 0; $i < 6; $i++) $out[] = $y - $i;
        return $out;
    }
}
