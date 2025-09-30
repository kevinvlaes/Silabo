public function consulta(): void
{
    // --- rol/ámbito ---
    $usuario       = $_SESSION['usuario'] ?? null;
    $esCoordinador = $usuario && ($usuario['rol'] === 'coordinador') && !empty($usuario['programa']);
    $programaFijo  = $esCoordinador ? (string)$usuario['programa'] : null;

    // --- filtros de la UI ---
    $anio = (string)($_GET['anio'] ?? '');
    $sem  = self::normalizarSemestre((string)($_GET['semestre'] ?? 'Todos'));

    // ---------- Catálogo de programas para el combo ----------
    $programas = [];
    try {
        $pdo = Database::pdo();

        // 1) Preferimos el catálogo maestro (unidades_didacticas)
        $st = $pdo->query("SELECT DISTINCT programa FROM unidades_didacticas ORDER BY programa");
        $programas = array_values(array_filter($st->fetchAll(PDO::FETCH_COLUMN) ?: []));

        // 2) Fallback desde silabos (por si no hay tabla/catálogos aún)
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
    } catch (Throwable $e) {
        $programas = [];
    }

    // ---------- Determinar el programa a filtrar según rol / GET ----------
    if ($esCoordinador) {
        $carreraParaFiltrar = $programaFijo;
    } else {
        // acepta ?carrera= o ?programa= desde la UI
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
    // La vista (consulta.php) usa 'carrera' y 'unidad_didactica'.
    foreach ($silabos as &$row) {
        // programa -> carrera (si no existe ya)
        if (!array_key_exists('carrera', $row)) {
            $row['carrera'] = $row['programa'] ?? '';
        }
        // ud_nombre -> unidad_didactica (si no existe ya)
        if (!array_key_exists('unidad_didactica', $row)) {
            $row['unidad_didactica'] = $row['ud_nombre'] ?? '';
        }
        // sanea por si vienen nulls
        $row['carrera']          = (string)($row['carrera'] ?? '');
        $row['unidad_didactica'] = (string)($row['unidad_didactica'] ?? '');
        $row['semestre']         = (string)($row['semestre'] ?? '');
        $row['anio']             = (string)($row['anio'] ?? '');
        $row['archivo']          = (string)($row['archivo'] ?? '');
    }
    unset($row);

    // ---------- Variables que consume la vista ----------
    $coordinador = $esCoordinador;
    $carrera     = $esCoordinador ? $programaFijo : ((string)($_GET['carrera'] ?? $_GET['programa'] ?? 'Todos'));
    $semestre    = $sem;

    // Render
    include __DIR__ . '/../Views/consulta.php';
}
