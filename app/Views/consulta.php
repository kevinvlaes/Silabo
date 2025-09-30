<?php
// Utilidad para escapar
if (!function_exists('e')) {
  function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Consulta de Sílabos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Consulta de Sílabos</h3>
    <a href="index.php?action=dashboard" class="btn btn-outline-secondary btn-sm">Volver</a>
  </div>

  <!-- Filtros -->
  <form class="row g-3 mb-3" method="get" action="index.php">
    <input type="hidden" name="action" value="consulta">

    <!-- Programa -->
    <div class="col-md-4">
      <label class="form-label">Carrera / Programa</label>
      <?php
        $isCoord = !empty($coordinador);
        $disabled = $isCoord ? 'disabled' : '';
      ?>
      <select name="programa" class="form-select" <?= $disabled ?>>
        <option value="Todos">— Todos —</option>
        <?php foreach (($programasDisponibles ?? []) as $prog): ?>
          <?php $sel = ((string)($programaSeleccionado ?? 'Todos') === (string)$prog) ? 'selected' : ''; ?>
          <option value="<?= e($prog) ?>" <?= $sel ?>><?= e($prog) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($isCoord): ?>
        <!-- Si es coordinador, fijamos el programa con un input hidden -->
        <input type="hidden" name="programa" value="<?= e($programaSeleccionado ?? '') ?>">
      <?php endif; ?>
    </div>

    <!-- Año -->
    <div class="col-md-4">
      <label class="form-label">Año</label>
      <select name="anio" class="form-select">
        <option value="">— Todos —</option>
        <?php foreach (($aniosParaSelect ?? []) as $y): ?>
          <?php $sel = ($anioSeleccionado !== null && (int)$anioSeleccionado === (int)$y) ? 'selected' : ''; ?>
          <option value="<?= (int)$y ?>" <?= $sel ?>><?= (int)$y ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Semestre -->
    <div class="col-md-3">
      <label class="form-label d-block">Semestre</label>
      <input type="hidden" name="semestre" id="semestreInput" value="<?= e($semestreSeleccionado ?? 'Todos') ?>">
      <div class="d-flex gap-2 flex-wrap">
        <?php
          $ops = array_merge(['Todos'], $semestresParaSelect ?? ['I','II','III','IV','V','VI']);
          foreach ($ops as $op):
            $isActive = (string)($semestreSeleccionado ?? 'Todos') === (string)$op;
        ?>
          <button type="button"
                  class="btn btn-sm <?= $isActive ? 'btn-secondary' : 'btn-outline-primary' ?>"
                  onclick="document.getElementById('semestreInput').value='<?= e($op) ?>'; this.form.submit();">
            <?= e($op) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Buscar -->
    <div class="col-md-1 d-flex align-items-end">
      <button class="btn btn-primary w-100" type="submit">Buscar</button>
    </div>
  </form>

  <!-- Resultados -->
  <div class="table-responsive bg-white shadow-sm rounded">
    <table class="table table-striped align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th>Carrera / Programa</th>
          <th style="width:100px">Año</th>
          <th style="width:100px">Semestre</th>
          <th>Unidad Didáctica</th>
          <th style="width:140px">Archivo</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($silabos)): ?>
        <?php $i=1; foreach ($silabos as $row): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= e($row['carrera'] ?? '') ?></td>
            <td><?= e($row['anio'] ?? '') ?></td>
            <td><span class="badge text-bg-primary"><?= e($row['semestre'] ?? '') ?></span></td>
            <td><?= e($row['unidad_didactica'] ?? '') ?></td>
            <td>
              <?php if (!empty($row['archivo'])): ?>
                <a class="btn btn-sm btn-outline-primary"
                   href="<?= e($row['archivo']) ?>" target="_blank" rel="noopener">
                   ⬇ Descargar
                </a>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
