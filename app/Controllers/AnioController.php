<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/Anio.php';

class AnioController
{
    private function onlyJefeAdmin(): void
    {
        Auth::check();
        Auth::requireRole(['jefe', 'admin']);
    }

    /** Página principal: listar/crear años */
    public function index(): void
    {
        $this->onlyJefeAdmin();

        $anios = Anio::todos();
        if (!$anios) {
            Anio::asegurarActual();
            $anios = Anio::todos();
        }

        include __DIR__ . '/../Views/anios.php';
    }

    /** POST crear */
    public function crear(): void
    {
        $this->onlyJefeAdmin();

        $anio = (int)($_POST['anio'] ?? 0);
        if ($anio < 2000 || $anio > 2100) {
            $_SESSION['flash_error'] = 'Año inválido.';
            header('Location: index.php?action=anios'); return;
        }

        Anio::crear($anio);
        $_SESSION['flash_ok'] = 'Año registrado.';
        header('Location: index.php?action=anios');
    }

    /** GET eliminar */
    public function eliminar(): void
    {
        $this->onlyJefeAdmin();

        $anio = (int)($_GET['anio'] ?? 0);
        if ($anio > 0) {
            Anio::eliminar($anio);
            $_SESSION['flash_ok'] = "Año $anio eliminado.";
        }
        header('Location: index.php?action=anios');
    }
}
