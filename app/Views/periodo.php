<?php
require_once __DIR__ . '/../Core/Auth.php';
Auth::check();

/**
 * Variables que pueden venir del controlador:
 * - $ini           string 'Y-m-d\TH:i' para <input type="datetime-local">
 * - $fin           string 'Y-m-d\TH:i'
 * - $abierto       bool   (estado actual del año en edición)
 * - $lectivoSel    string con formato 'YYYY-I' o 'YYYY-II'
 * - $ANIO_UI / $anioSel  int, año que se está configurando (contexto)
 *
 * La vista es tolerante: si no llega ANIO_UI/anioSel, toma ?anio= o el año actual.
 */

// Año de contexto (tolerante con distintos nombres / fuentes)
$year = null;
if (isset($ANIO_UI) && ctype_digit((string)$ANIO_UI)) {
    $year = (int)$ANIO_UI;
} elseif (isset($anioSel) && ctype_digit((string)$anioSel)) {
    $year = (int)$anioSel;
} elseif (isset($_GET['anio']) && ctype_digit((string)$_GET['anio'])) {
    $year = (int)$_GET['anio'];
} else {
    $year = (int)date('Y');
}

// Defaults seguros por si algo no viene
$ini        = isset($ini) && $ini !== '' ? $ini : date('Y-m-d\TH:i');
$fin        = isset($fin) && $fin !== '' ? $fin : date('Y-m-d\TH:i', time() + 3600);
$abierto    = isset($abierto) ? (bool)$abierto : false;
$lectivoSel = isset($lectivoSel) && $lectivoSel !== '' ? $lectivoSel : "{$year}-I";

// Opciones de lectivo para el año de contexto
$op1 = "{$year}-I";
$op2 = "{$year}-II";

// Texto de ayuda según el lectivo seleccionado
$hint = '';
if (preg_match('/-I$/', (string)$lectivoSel)) {
    $hint = 'Semestres habilitados: I, III y V.';
} elseif (preg_match('/-II$/', (string)$lectivoSel)) {
    $hint = 'Semestres habilitados: II, IV y VI.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Periodo de subida de sílabos — Año <?= htmlspecialchars((string)$year) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">

  <h2 class="mb-3">
    Periodo de subida de sílabos — <span class="text-primary">Año <?= htmlspecialchars((string)$year) ?></span>
  </h2>

  <?php if (isset($_SESSION['flash_ok'])): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($_SESSION['flash_ok']); unset($_SESSION['flash_ok']); ?>
    </div>
  <?php endif; ?>
  <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
      <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    </div>
  <?php endif; ?>

  <div class="alert <?= $abierto ? 'alert-success' : 'alert-warning' ?>">
    Estado actual para <?= htmlspecialchars((string)$year) ?>:
    <b><?= $abierto ? 'ABIERTO' : 'CERRADO' ?></b>
  </div>

  <form method="POST" action="index.php?action=periodo_guardar" class="row g-3">
    <!-- ¡IMPORTANTE! Mantener el año en el POST para no perder contexto -->
    <input type="hidden" name="anio" value="<?= (int)$year ?>">

    <div class="col-md-4">
      <label class="form-label">Inicio</label>
      <input
        type="datetime-local"
        name="inicio"
        class="form-control"
        value="<?= htmlspecialchars($ini) ?>"
        required
      >
    </div>

    <div class="col-md-4">
      <label class="form-label">Fin</label>
      <input
        type="datetime-local"
        name="fin"
        class="form-control"
        value="<?= htmlspecialchars($fin) ?>"
        required
      >
    </div>

    <div class="col-md-4">
      <label class="form-label">Periodo lectivo</label>
      <select name="lectivo" id="lectivo" class="form-select">
        <option value="<?= htmlspecialchars($op1) ?>" <?= ($lectivoSel === $op1 ? 'selected' : '') ?>>
          Periodo lectivo <?= htmlspecialchars($op1) ?>
        </option>
        <option value="<?= htmlspecialchars($op2) ?>" <?= ($lectivoSel === $op2 ? 'selected' : '') ?>>
          Periodo lectivo <?= htmlspecialchars($op2) ?>
        </option>
      </select>
      <small class="text-muted d-block">
        Configurando el periodo para el año <b><?= htmlspecialchars((string)$year) ?></b>.
      </small>
      <small id="lectivoHint" class="text-info"><?= htmlspecialchars($hint) ?></small>
    </div>

    <div class="col-12 d-flex gap-2">
      <button class="btn btn-primary">Guardar</button>
      <!-- Volver al dashboard del MISMO AÑO -->
      <a class="btn btn-outline-secondary" href="index.php?action=dashboard&anio=<?= (int)$year ?>">Volver</a>
    </div>
  </form>

  <script>
    // Mensaje dinámico según el periodo lectivo seleccionado
    const sel  = document.getElementById('lectivo');
    const hint = document.getElementById('lectivoHint');

    function updateHint() {
      const v = (sel.value || '').trim();
      if (/-I$/.test(v)) {
        hint.textContent = 'Semestres habilitados: I, III y V.';
      } else if (/-II$/.test(v)) {
        hint.textContent = 'Semestres habilitados: II, IV y VI.';
      } else {
        hint.textContent = '';
      }
    }

    sel.addEventListener('change', updateHint);
  </script>
</body>
</html>
