<?php
// Variables que deberían venir del controlador:
// $silabos (array), y los filtros GET si necesitas pintarlos

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
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
    <a class="btn btn-outline-secondary btn-sm" href="index.php?action=dashboard">Volver</a>
  </div>

  <!-- Filtros (opcionales: pinta tus valores GET si quieres) -->
  <form class="row g-2 mb-3" method="get" action="index.php">
    <input type="hidden" name="action" value="consulta">
    <div class="col-md-4">
      <label class="form-label small">Carrera / Programa</label>
      <input name="carrera" class="form-control form-control-sm" value="<?= e($_GET['carrera'] ?? 'Todos') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small">Año</label>
      <input name="anio" class="form-control form-control-sm" value="<?= e($_GET['anio'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small">Semestre</label>
      <input name="semestre" class="form-control form-control-sm" value="<?= e($_GET['semestre'] ?? 'Todos') ?>">
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button class="btn btn-primary w-100 btn-sm">Buscar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped table-sm align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:70px">#</th>
          <th>Carrera / Programa</th>
          <th style="width:90px">Año</th>
          <th style="width:110px">Semestre</th>
          <th>Unidad Didáctica</th>
          <th style="width:130px">Archivo</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($silabos)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No hay resultados.</td></tr>
      <?php else: $i=1; foreach ($silabos as $r):
          // Fallbacks seguros
          $prog = $r['programa'] ?? $r['carrera'] ?? '';
          $anio = $r['anio'] ?? '';
          $sem  = $r['semestre'] ?? '';
          $ud   = $r['ud_nombre'] ?? ($r['unidad_didactica'] ?? ($r['unidad'] ?? ''));
          $arc  = $r['archivo'] ?? '';
      ?>
        <tr>
          <td><?= $i++ ?></td>
          <td class="fw-semibold"><?= e($prog) ?></td>
          <td><?= e($anio) ?></td>
          <td><?= e($sem) ?></td>
          <td><?= e($ud) ?></td>
          <td>
            <?php if ($arc): ?>
              <a class="btn btn-outline-primary btn-xxs btn-sm" target="_blank" href="<?= e($arc) ?>">Descargar</a>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
