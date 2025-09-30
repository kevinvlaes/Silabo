<?php
if (!function_exists('e')) { function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$tokenValido = empty($_SESSION['flash_error']); // si hubo error antes, probablemente inválido
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Definir nueva contraseña</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h4 class="mb-3">Definir nueva contraseña</h4>

          <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
          <?php endif; ?>

          <?php if ($token !== '' && $tokenValido): ?>
            <form method="post" action="index.php?action=reset">
              <input type="hidden" name="token" value="<?= e($token) ?>">
              <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Confirmar contraseña</label>
                <input type="password" name="confirm" class="form-control" minlength="6" required>
              </div>
              <button class="btn btn-primary w-100">Guardar</button>
            </form>
          <?php else: ?>
            <p>El enlace no es válido o ha expirado.</p>
            <a href="index.php?action=forgotForm" class="btn btn-outline-primary">Volver a solicitar</a>
          <?php endif; ?>

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
