<?php
require_once __DIR__ . '/../Core/Auth.php';
Auth::check();

/* Programas disponibles (incluye Empleabilidad) */
$PROGRAMAS = [
  "Diseño y Programación Web",
  "Enfermería Técnica",
  "Mecatrónica Automotriz",
  "Industrias de Alimentos y Bebidas",
  "Producción Agropecuaria",
  "Empleabilidad"
];

/* Protección si no llega la variable */
$usuarios = $usuarios ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Gestión de Usuarios</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">

<h2 class="mb-3">Gestión de Usuarios</h2>

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
    <h5 class="card-title">Registrar Nuevo Usuario</h5>
    <form class="row g-2" method="POST" action="index.php?action=usuario_crear">
      <div class="col-md-4">
        <input name="nombre" class="form-control" placeholder="Nombre completo" required>
      </div>
      <div class="col-md-3">
        <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
      </div>
      <div class="col-md-3">
        <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
      </div>

      <div class="col-md-2">
        <select name="rol" class="form-select" required>
          <option value="docente">Docente</option>
          <option value="coordinador">Coordinador</option>
          <option value="jefe">Jefe</option>
          <option value="admin">Administrador</option>
        </select>
      </div>

      <div class="col-md-4">
        <select name="programa" class="form-select">
          <option value="">— Sin programa —</option>
          <?php foreach ($PROGRAMAS as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <input type="number" name="anio" class="form-control" placeholder="Año" value="<?= date('Y') ?>">
      </div>

      <div class="col-md-2">
        <select name="estado" class="form-select">
          <option value="activo" selected>Activo</option>
          <option value="inactivo">Inactivo</option>
        </select>
      </div>

      <div class="col-12">
        <small class="text-muted">
          Para <b>Docente</b> o <b>Coordinador</b> es obligatorio asignar Programa y Año.
          Para <b>Jefe</b> o <b>Administrador</b> es opcional.
        </small>
      </div>

      <div class="col-12">
        <button class="btn btn-success">Registrar</button>
      </div>
    </form>
  </div>
</div>

<table class="table table-striped align-middle">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Email</th>
      <th>Rol</th>
      <th>Programa</th>
      <th>Año</th>
      <th>Estado</th>
      <th style="width:200px;">Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($usuarios as $u): ?>
    <tr>
      <td><?= (int)$u['id'] ?></td>
      <td><?= htmlspecialchars($u['nombre']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['rol']) ?></td>
      <td><?= htmlspecialchars($u['programa'] ?? '') ?></td>
      <td><?= htmlspecialchars($u['anio'] ?? '') ?></td>
      <td>
        <?php if (($u['estado'] ?? 'activo') === 'activo'): ?>
          <span class="badge bg-success">Activo</span>
        <?php else: ?>
          <span class="badge bg-secondary">Inactivo</span>
        <?php endif; ?>
      </td>
      <td class="d-flex gap-2">
        <button
          class="btn btn-sm btn-warning btnEditar"
          type="button"
          data-id="<?= (int)$u['id'] ?>"
          data-nombre="<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>"
          data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>"
          data-rol="<?= htmlspecialchars($u['rol'], ENT_QUOTES) ?>"
          data-programa="<?= htmlspecialchars($u['programa'] ?? '', ENT_QUOTES) ?>"
          data-anio="<?= htmlspecialchars($u['anio'] ?? '', ENT_QUOTES) ?>"
          data-estado="<?= htmlspecialchars($u['estado'] ?? 'activo', ENT_QUOTES) ?>"
        >Editar</button>

        <a class="btn btn-sm btn-danger"
           href="index.php?action=usuario_eliminar&id=<?= (int)$u['id'] ?>"
           onclick="return confirm('¿Eliminar este usuario?')">Eliminar</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="index.php?action=usuario_actualizar" id="formEditar">
        <div class="modal-header">
          <h5 class="modal-title">Editar usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body row g-3">
          <input type="hidden" name="id" id="e_id">

          <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input class="form-control" name="nombre" id="e_nombre" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Correo</label>
            <input type="email" class="form-control" name="email" id="e_email" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Nueva contraseña (opcional)</label>
            <input type="password" class="form-control" name="password" id="e_password" placeholder="Dejar en blanco para no cambiar">
          </div>

          <div class="col-md-6">
            <label class="form-label">Rol</label>
            <select class="form-select" name="rol" id="e_rol" required>
              <option value="docente">Docente</option>
              <option value="coordinador">Coordinador</option>
              <option value="jefe">Jefe</option>
              <option value="admin">Administrador</option>
            </select>
          </div>

          <div class="col-md-8">
            <label class="form-label">Programa</label>
            <select class="form-select" name="programa" id="e_programa">
              <option value="">— Sin programa —</option>
              <?php foreach ($PROGRAMAS as $p): ?>
                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Año</label>
            <input type="number" class="form-control" name="anio" id="e_anio" placeholder="Opcional">
          </div>

          <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado" id="e_estado">
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
  document.querySelectorAll('.btnEditar').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('e_id').value       = btn.dataset.id;
      document.getElementById('e_nombre').value   = btn.dataset.nombre || '';
      document.getElementById('e_email').value    = btn.dataset.email || '';
      document.getElementById('e_password').value = '';
      document.getElementById('e_rol').value      = btn.dataset.rol || 'docente';
      document.getElementById('e_programa').value = btn.dataset.programa || '';
      document.getElementById('e_anio').value     = btn.dataset.anio || '';
      document.getElementById('e_estado').value   = btn.dataset.estado || 'activo';
      modal.show();
    });
  });
</script>

</body>
</html>
