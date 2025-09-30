<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/Periodo.php';

class PeriodoController
{
    /**
     * Muestra el formulario de periodo de subida para el año de contexto.
     * Toma ?anio=YYYY si viene en la URL; si no, usa el año en sesión o el año actual.
     */
    public function index(): void
    {
        Auth::check();

        // Año desde la query (opcional)
        $anioQuery = isset($_GET['anio']) && ctype_digit((string)$_GET['anio'])
            ? (int)$_GET['anio']
            : null;

        // Obtiene/crea el registro para el año y fija el contexto en sesión
        $p = Periodo::actual($anioQuery);
        $anioSel = (int)$p['anio'];

        // Valores para <input type="datetime-local"> -> 'Y-m-d\TH:i'
        $ini = !empty($p['inicio'])
            ? date('Y-m-d\TH:i', strtotime($p['inicio']))
            : '';
        $fin = !empty($p['fin'])
            ? date('Y-m-d\TH:i', strtotime($p['fin']))
            : '';

        // Estado y lectivo seleccionado (devuelve 'YYYY-I' o 'YYYY-II')
        $abierto    = Periodo::abiertoAhora($anioSel);
        $lectivoSel = Periodo::lectivo($anioSel);

        // Render
        include __DIR__ . '/../Views/periodo.php';
    }

    /**
     * Guarda el periodo de un año.
     * Espera: anio(int), inicio(datetime-local), fin(datetime-local), lectivo('YYYY-I'|'YYYY-II'|'I'|'II')
     */
    public function guardar(): void
    {
        Auth::check();

        // Año de destino (POST preferente, si no, query, si no, año actual)
        $anio = null;
        if (isset($_POST['anio']) && is_numeric($_POST['anio'])) {
            $anio = (int)$_POST['anio'];
        } elseif (isset($_GET['anio']) && ctype_digit((string)$_GET['anio'])) {
            $anio = (int)$_GET['anio'];
        } else {
            $anio = (int)date('Y');
        }

        // Normaliza 'YYYY-MM-DDTHH:ii' a 'YYYY-MM-DD HH:ii:ss' para guardar
        $toSqlDateTime = static function (?string $s): ?string {
            if ($s === null) return null;
            $s = trim($s);
            if ($s === '') return null;
            $s = str_replace('T', ' ', $s);
            // Si no vienen segundos, añádelos
            if (preg_match('/^\d{4}\-\d{2}\-\d{2}\s\d{2}:\d{2}$/', $s)) {
                $s .= ':00';
            }
            return $s;
        };

        $inicio  = $toSqlDateTime($_POST['inicio'] ?? null);
        $fin     = $toSqlDateTime($_POST['fin'] ?? null);
        $lectivo = isset($_POST['lectivo']) ? trim((string)$_POST['lectivo']) : null;

        try {
            Periodo::guardar($anio, $inicio, $fin, $lectivo);
            $_SESSION['flash_ok'] = 'Periodo guardado correctamente.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo guardar el periodo. ' . $e->getMessage();
        }

        header('Location: index.php?action=periodo&anio=' . (int)$anio);
        exit;
    }
}
