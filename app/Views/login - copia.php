<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container">
  <h2 class="mt-5">Acceso al Repositorio</h2>
  <form method="POST" action="index.php?action=login">
    <input type="email" name="email" class="form-control mb-2" placeholder="Correo" required>
    <input type="password" name="password" class="form-control mb-2" placeholder="Contraseña" required>
    <button class="btn btn-primary">Ingresar</button>
  </form>
</body>
</html>
