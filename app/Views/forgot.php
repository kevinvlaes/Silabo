<?php
if (!function_exists('e')) { function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recuperar contraseña</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h4 class="mb-3">Recuperar contraseña</h4>
          <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
          <?php endif; ?>
          <form method="post" action="index.php?action=forgot">
            <div class="mb-3">
              <label class="form-label">Correo electrónico</label>
              <input type="email" name="email" class="form-control" placeholder="tucorreo@dominio.com" required>
            </div>
            <button class="btn btn-primary w-100">Enviar enlace</button>
          </form>
          <div class="mt-3 text-center">
            <a href="index.php?action=loginForm" class="small">Volver al login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
