<?php
require_once __DIR__ . '/../Core/Auth.php';
Auth::check();

/** @var array<int> $anios  viene del controlador */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Años académicos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
  <h2 class="mb-3">Años académicos</h2>

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-secondary" href="index.php?action=dashboard">Volver</a>
    <a class="btn btn-danger" href="index.php?action=logout">Cerrar sesión</a>
  </div>

  <?php if (isset($_SESSION['flash_ok'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_ok']); unset($_SESSION['flash_ok']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">Registrar nuevo año</h5>
      <form class="row g-2" method="POST" action="index.php?action=anio_crear">
        <div class="col-auto">
          <input type="number" min="2000" max="2100" name="anio" class="form-control" placeholder="Ej. 2024" required>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Agregar</button>
        </div>
      </form>
      <small class="text-muted">Solo años válidos. No pasa nada si ya existe: se ignora.</small>
    </div>
  </div>

  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th style="width:120px;">Año</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($anios as $y): ?>
        <tr>
          <td><b><?= (int)$y ?></b></td>
          <td>
            <a class="btn btn-sm btn-outline-danger"
               href="index.php?action=anio_eliminar&anio=<?= (int)$y ?>"
               onclick="return confirm('¿Eliminar el año <?= (int)$y ?>?');">
               Eliminar
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$anios): ?>
        <tr><td colspan="2" class="text-muted">No hay años creados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
