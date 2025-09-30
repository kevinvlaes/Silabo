<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Iniciar sesión</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{
      background:#fff;
    }
    .login-wrap{
      min-height:100vh;
    }
    .brand img{
      max-width:260px; /* ajusta si tu logo es más grande */
      height:auto;
    }
    .headline{
      color:#7a8aa0;
      font-weight:500;
      letter-spacing:.2px;
    }
    .footer-mark{
      color:#1f2937;
      font-weight:600;
      letter-spacing:.3px;
    }
    .btn-primary{
      padding:.8rem 2rem;
      font-size:1.1rem;
      border-radius:.6rem;
    }
    .form-control{
      border-radius:.6rem;
      padding:1rem 1.2rem;
      font-size:1.05rem;
    }
  </style>
</head>
<body>
  <div class="container login-wrap d-flex align-items-center justify-content-center">
    <div class="w-100" style="max-width:640px;">
      <div class="text-center mb-4 brand">
        <!-- Pon tu logo en public/assets/logo.png o actualiza la ruta -->
        <img src="assets/logo.png" alt="Logo IESP Huanta">
      </div>

      <h5 class="text-center headline mb-2">Bienvenido al sistema de repositorio de sílabos</h5>
      <h5 class="text-center headline mb-4">Inicie Sesión para acceder</h5>

      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
      <?php endif; ?>

      <form action="index.php?action=login" method="POST" class="mt-3">
        <div class="mb-3">
          <input type="email" name="email" class="form-control" placeholder="Correo" required>
        </div>
        <div class="mb-4">
          <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
        </div>
        <div class="text-center">
          <button class="btn btn-primary" type="submit">Ingresar</button>
          
        </div>
        <a href="index.php?action=forgotForm" class="small">Olvidé mi contraseña</a>

      </form>

      <div class="text-center mt-5">
        <div class="footer-mark">I.E.S.P. "HUANTA"</div>
      </div>
    </div>
  </div>
</body>
</html>
