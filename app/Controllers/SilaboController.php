<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Silabo.php';
require_once __DIR__ . '/../Models/Periodo.php';

class SilaboController
{
    /* =================== Helpers =================== */

    /** Normaliza el valor recibido para semestre. */
    private static function normalizarSemestre(string $s): string
    {
        $s = trim($s);
        if ($s === '' || strcasecmp($s, 'Todos') === 0) return 'Todos';

        $map = [
            '1'=>'I','01'=>'I','i'=>'I','I'=>'I',
            '2'=>'II','02'=>'II','ii'=>'II','II'=>'II',
            '3'=>'III','03'=>'III','iii'=>'III','III'=>'III',
            '4'=>'IV','04'=>'IV','iv'=>'IV','IV'=>'IV',
            '5'=>'V','05'=>'V','v'=>'V','V'=>'V',
            '6'=>'VI','06'=>'VI','vi'=>'VI','VI'=>'VI',
        ];
        return $map[$s] ?? 'Todos';
    }

    /* =================== Acciones =================== */

    /** Vista pública/privada de consulta de sílabos. */
    public function consulta(): void
    {
        // --- rol/ámbito ---
        $usuario       = $_SESSION['usuario'] ?? null;
        $esCoordinador = $usuario && ($usuario['rol'] === 'coordinador') && !empty($usuario['programa']);
        $programaFijo  = $esCoordinador ? (string)$usuario['programa'] : null;

        // --- filtros de la UI (GET) ---
        $anio = (string)($_GET['anio'] ?? '');
        $sem  = self::normalizarSemestre((string)($_GET['semestre'] ?? 'Todos'));

        // ---------- Catálogo de programas para el combo ----------
        $programas = [];
        try {
            $pdo = Database::pdo();

            // 1) Preferimos catálogo maestro
            $st = $pdo->query("SELECT DISTINCT programa FROM unidades_didacticas ORDER BY programa");
            $programas = array_values(array_filter($st->fetchAll(PDO::FETCH_COLUMN) ?: []));

            // 2) Fallback desde silabos
            if (!$programas) {
                $st = $pdo->query("
                    SELECT DISTINCT COALESCE(programa, carrera) AS programa
                      FROM silabos
                     WHERE COALESCE(programa, carrera) IS NOT NULL
                       AND COALESCE(programa, carrera) <> ''
                  ORDER BY programa
                ");
                $programas = array_values(array_filter($st->fetchAll(PDO::FETCH_COLUMN) ?: []));
            }
        } catch (\Throwable $e) {
            $programas = [];
        }

        // --- Año seleccionado (int|null) ---
        $anioSel = (isset($_GET['anio']) && $_GET['anio'] !== '' && ctype_digit((string)$_GET['anio']))
            ? (int)$_GET['anio'] : null;

        // --- Años disponibles (preferir tabla maestra; luego fallback) ---
        $aniosDisponibles = [];
        try {
            $pdo = Database::pdo();
            // Si no existe la tabla, lanzará excepción y pasará a fallback
            $pdo->query("SELECT 1 FROM anios_academicos LIMIT 1");
            $st  = $pdo->query("SELECT DISTINCT anio FROM anios_academicos ORDER BY anio DESC");
            $aniosDisponibles = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable $e) {
            // Fallback: distinct de silabos
            try {
                $st  = Database::pdo()->query("SELECT DISTINCT anio FROM silabos WHERE anio IS NOT NULL ORDER BY anio DESC");
                $aniosDisponibles = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } catch (\Throwable $e2) {
                // Último recurso: últimos 6 años
                $y = (int)date('Y');
                for ($i=0;$i<6;$i++) $aniosDisponibles[] = $y - $i;
            }
        }

        // --- Semestres disponibles según periodo del año (si hay) ---
        $semestresDisponibles = ['I','II','III','IV','V','VI'];
        try {
            if ($anioSel !== null) {
                $semestresDisponibles = Periodo::semestresDelPeriodo($anioSel);
            }
        } catch (\Throwable $e) {
            // dejamos todos
        }

        // ---------- Determinar el programa a filtrar según rol / GET ----------
        if ($esCoordinador) {
            $carreraParaFiltrar = $programaFijo;
        } else {
            // acepta ?carrera= o ?programa=
            $carreraGet = (string)($_GET['carrera'] ?? $_GET['programa'] ?? 'Todos');
            $carreraParaFiltrar = ($carreraGet !== '' && $carreraGet !== 'Todos') ? $carreraGet : null;
        }

        // ---------- Traer datos del modelo ----------
        $silabos = Silabo::filtrar(
            $carreraParaFiltrar,
            ($anio !== '' ? $anio : null),
            ($sem && $sem !== 'Todos') ? $sem : null
        );

        // ---------- Normalizar claves para la vista ----------
        foreach ($silabos as &$row) {
            if (!array_key_exists('carrera', $row)) {
                $row['carrera'] = $row['programa'] ?? '';
            }
            if (!array_key_exists('unidad_didactica', $row)) {
                $row['unidad_didactica'] = $row['ud_nombre'] ?? '';
            }
            $row['carrera']          = (string)($row['carrera'] ?? '');
            $row['unidad_didactica'] = (string)($row['unidad_didactica'] ?? '');
            $row['semestre']         = (string)($row['semestre'] ?? '');
            $row['anio']             = (string)($row['anio'] ?? '');
            $row['archivo']          = (string)($row['archivo'] ?? '');
        }
        unset($row);

        // ---------- Variables que consume la vista ----------
        $coordinador             = $esCoordinador;
        $carrera                 = $esCoordinador ? $programaFijo : ((string)($_GET['carrera'] ?? $_GET['programa'] ?? 'Todos'));
        $semestre                = $sem;

        // Extras para selects
        $programasDisponibles    = $programas;
        $aniosParaSelect         = $aniosDisponibles;
        $semestresParaSelect     = $semestresDisponibles;
        $anioSeleccionado        = $anioSel;             // int|null
        $programaSeleccionado    = $carrera;             // string ('Todos' o nombre)
        $semestreSeleccionado    = $sem;                 // 'Todos' o I..VI

        // Render
        include __DIR__ . '/../Views/consulta.php';
    }

    /** Form de subida (placeholder). */
    public function subirForm(): void
    {
        Auth::check();
        include __DIR__ . '/../Views/subir.php';
    }

    public function subir(): void
    {
        Auth::check();
        header('Location: index.php?action=dashboard');
        exit;
    }

    public function reemplazar(): void
    {
        Auth::check();
        header('Location: index.php?action=dashboard');
        exit;
    }

    /** API para poblar combo de UDs en la vista de subir/asignar. */
    public function api_unidades(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $programa = trim((string)($_GET['programa'] ?? ''));
        $semestre = self::normalizarSemestre((string)($_GET['semestre'] ?? ''));

        $out = [];
        try {
            $pdo = Database::pdo();

            // Prioriza tabla maestra
            $sql = "SELECT nombre FROM unidades_didacticas WHERE 1=1";
            $params = [];
            if ($programa !== '') { $sql .= " AND programa = ?"; $params[] = $programa; }
            if ($semestre && $semestre !== 'Todos') { $sql .= " AND semestre = ?"; $params[] = $semestre; }
            $sql .= " ORDER BY nombre";

            $st = $pdo->prepare($sql);
            $st->execute($params);
            $out = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Fallback: deducir desde silabos
            if (!$out) {
                $where = [];
                $params = [];
                if ($programa !== '') { $where[] = "COALESCE(programa,carrera) = ?"; $params[] = $programa; }
                if ($semestre && $semestre !== 'Todos') { $where[] = "semestre = ?"; $params[] = $semestre; }

                $st = $pdo->prepare("
                    SELECT DISTINCT COALESCE(ud_nombre, unidad_didactica) AS nombre
                      FROM silabos
                     ".($where ? "WHERE ".implode(" AND ", $where) : "")."
                  ORDER BY nombre
                ");
                $st->execute($params);
                $out = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }
        } catch (\Throwable $e) {
            $out = [];
        }

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
