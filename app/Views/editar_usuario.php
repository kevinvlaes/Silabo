<?php
require_once __DIR__."/../Core/Auth.php"; 
Auth::check(); 
$usuario = $_SESSION['usuario'];

if ($usuario['rol'] !== 'jefe') { die("Acceso denegado"); }

require_once __DIR__."/../Models/Usuario.php";
$u = Usuario::buscarPorId($_GET['id']);

$programas = [
  "Diseño y Programación Web",
  "Enfermería Técnica",
  "Mecatrónica Automotriz",
  "Industrias de Alimentos y Bebidas",
  "Producción Agropecuaria"
];
?>
<!DOCTYPE html>
<html>
<head>
  <title>Editar Usuario</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container">
  <h2 class="mt-5">Editar Usuario</h2>
  <a href="index.php?action=usuarios" class="btn btn-secondary">Volver</a>

  <form method="POST" action="index.php?action=actualizarUsuario">
    <input type="hidden" name="id" value="<?= $u['id'] ?>">

    <input type="text" name="nombre" class="form-control mb-2" value="<?= htmlspecialchars($u['nombre']) ?>" required>
    <input type="email" name="email" class="form-control mb-2" value="<?= htmlspecialchars($u['email']) ?>" required>

    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Rol</label>
        <select name="rol" class="form-select" required>
          <option value="docente" <?= $u['rol']=='docente'?'selected':'' ?>>Docente</option>
          <option value="coordinador" <?= $u['rol']=='coordinador'?'selected':'' ?>>Coordinador</option>
          <option value="jefe" <?= $u['rol']=='jefe'?'selected':'' ?>>Jefe académico</option>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">Programa de estudios</label>
        <select name="programa" class="form-select">
          <option value="">— Selecciona —</option>
          <?php foreach ($programas as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>" <?= ($u['programa']===$p?'selected':'') ?>><?= htmlspecialchars($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Año laborando</label>
        <input type="number" name="anio_labor" class="form-control" value="<?= htmlspecialchars($u['anio_labor']) ?>">
      </div>
    </div>

    <input type="password" name="password" class="form-control mt-2" placeholder="Nueva contraseña (opcional)">
    <button class="btn btn-primary mt-2">Actualizar</button>
  </form>
</body>
</html>
