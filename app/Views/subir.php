<?php
require_once __DIR__ . "/../Core/Auth.php";
Auth::check();

$usuario         = $_SESSION['usuario'] ?? [];
$rolUsuario      = $usuario['rol'] ?? '';
$programaUsuario = $usuario['programa'] ?? '';

require_once __DIR__ . "/../Models/Periodo.php";

/** Semestres permitidos por el periodo lectivo vigente (desde BD) */
$SEMESTRES_VIGENTES = Periodo::semestresDelPeriodo();
$LECTIVO_PERIODO    = Periodo::lectivo();

/** Catálogo de programas (para Jefe/Admin o usuarios sin programa fijo) */
$PROGRAMAS = [
  "Diseño y Programación Web",
  "Enfermería Técnica",
  "Mecatrónica Automotriz",
  "Industrias de Alimentos y Bebidas",
  "Producción Agropecuaria",
  "Empleabilidad",
];

/** ¿Bloqueamos el selector de programa? (docente/coordinador con programa fijo) */
$bloquearPrograma = (in_array($rolUsuario, ['docente', 'coordinador'], true) && !empty($programaUsuario));

/** Si el controlador envió esta variable (docente/coordinador con múltiples programas asignados) */
$programasAsignados = $programasAsignados ?? [];

/** Año que usará el formulario y el AJAX (lo trae el controlador) */
$ANIO_UI = isset($ANIO_UI) ? (int)$ANIO_UI : (int)date('Y');

$abierto    = Periodo::abiertoAhora();
$puedeSubir = $abierto || $rolUsuario === 'jefe' || $rolUsuario === 'admin';

/** Flashes */
$flash_ok    = $_SESSION['flash_ok']    ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Subir Sílabo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">

  <h2 class="mb-3">Subir Sílabo</h2>

  <?php if ($flash_ok): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash_ok) ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
  <?php endif; ?>

  <div class="alert alert-info">
    <div>
      Selecciona el <b>Programa</b> y el <b>Semestre</b> para que el sistema cargue la <b>Unidad Didáctica</b> desde la base de datos.
    </div>
    <div class="mt-1">
      Periodo lectivo actual: <b><?= htmlspecialchars($LECTIVO_PERIODO) ?></b>.
      Semestres disponibles: <b><?= htmlspecialchars(implode(', ', $SEMESTRES_VIGENTES)) ?></b>.
    </div>
    <small class="text-muted d-block mt-1">
      Nota: si una Unidad Didáctica ya tiene sílabo cargado para el mismo Programa/Año/Semestre, no aparecerá en la lista (el reemplazo se hace desde el panel principal).
    </small>
  </div>

  <?php if (!$puedeSubir): ?>
    <div class="alert alert-warning">
      La ventana para subir sílabos está <strong>cerrada</strong>. Consulta a Jefatura Académica.
    </div>
  <?php elseif (!$abierto && $puedeSubir): ?>
    <div class="alert alert-secondary">
      El periodo está cerrado, pero como <b><?= htmlspecialchars($rolUsuario) ?></b> puedes subir/gestionar sílabos.
    </div>
  <?php endif; ?>

  <form method="POST" action="index.php?action=subir<?= $ANIO_UI ? '&anio='.$ANIO_UI : '' ?>" enctype="multipart/form-data" id="formSubida" class="mb-3">

    <!-- PROGRAMA -->
    <label class="form-label">Programa de Estudios</label>

    <?php if (!empty($programasAsignados)): ?>
      <!-- Docente/Coordinador con múltiples programas asignados (para el año seleccionado) -->
      <select id="programa" name="carrera" class="form-select mb-2" required>
        <option value="">— Selecciona —</option>
        <?php foreach ($programasAsignados as $p): ?>
          <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
        <?php endforeach; ?>
      </select>

    <?php elseif ($bloquearPrograma): ?>
      <!-- Docente/Coordinador con 1 solo programa -->
      <select id="programa" class="form-select mb-2" disabled>
        <option value="<?= htmlspecialchars($programaUsuario) ?>" selected>
          <?= htmlspecialchars($programaUsuario) ?>
        </option>
      </select>
      <input type="hidden" name="carrera" id="carreraHidden" value="<?= htmlspecialchars($programaUsuario) ?>">

    <?php else: ?>
      <!-- Jefe/Admin (o usuario sin programa asignado) -->
      <select id="programa" name="carrera" class="form-select mb-2" required>
        <option value="">— Selecciona —</option>
        <?php foreach ($PROGRAMAS as $p): ?>
          <option value="<?= htmlspecialchars($p) ?>" <?= ($programaUsuario === $p ? 'selected' : '') ?>>
            <?= htmlspecialchars($p) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <!-- AÑO -->
    <label class="form-label">Año académico</label>
    <input type="number" name="anio" class="form-control mb-2" value="<?= (int)$ANIO_UI ?>" required>

    <!-- SEMESTRE (los del periodo lectivo vigente) -->
    <label class="form-label">Semestre</label>
    <select name="semestre" id="semestre" class="form-select mb-2" required>
      <option value="">— Selecciona —</option>
      <?php foreach ($SEMESTRES_VIGENTES as $s): ?>
        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
      <?php endforeach; ?>
    </select>

    <!-- UNIDAD DIDÁCTICA (cargada por AJAX) -->
    <label class="form-label">Unidad Didáctica</label>
    <select name="unidad_didactica" id="unidad" class="form-select mb-3" required disabled>
      <option value="">— Selecciona programa y semestre —</option>
    </select>

    <!-- ARCHIVO -->
    <label class="form-label">Archivo (PDF/DOC/DOCX)</label>
    <input type="file" name="archivo" class="form-control" accept=".pdf,.doc,.docx" required <?= $puedeSubir ? '' : 'disabled' ?>>

    <div class="d-flex align-items-center gap-2 mt-3">
      <button class="btn btn-primary" <?= $puedeSubir ? '' : 'disabled' ?>>Guardar</button>
      <a href="index.php?action=dashboard<?= $ANIO_UI ? '&anio='.$ANIO_UI : '' ?>" class="btn btn-outline-secondary">Volver</a>
    </div>
  </form>

<script>
async function cargarUnidades() {
  const progEl  = document.getElementById('programa');
  const hidden  = document.getElementById('carreraHidden');
  const prog    = (progEl && !progEl.disabled) ? progEl.value : (hidden ? hidden.value : '');
  const sem     = document.getElementById('semestre').value;  // 'I','III','V' o 'II','IV','VI'
  const anio    = document.querySelector('input[name="anio"]').value;
  const unidad  = document.getElementById('unidad');

  unidad.innerHTML = '<option value="">— Selecciona programa y semestre —</option>';
  unidad.disabled = true;

  if (!prog || !sem) return;

  try {
    const qs  = new URLSearchParams({ action: 'api_unidades', programa: prog, semestre: sem, anio });
    const res = await fetch('index.php?' + qs.toString());
    const data = await res.json();

    unidad.innerHTML = '';
    if (Array.isArray(data) && data.length) {
      data.forEach(n => {
        const opt = document.createElement('option');
        opt.value = n;
        opt.textContent = n;
        unidad.appendChild(opt);
      });
      unidad.disabled = false;
    } else {
      unidad.innerHTML = '<option value="">No hay unidades para este semestre</option>';
      unidad.disabled = true;
    }
  } catch (e) {
    unidad.innerHTML = '<option value="">Error cargando unidades</option>';
    unidad.disabled = true;
  }
}

// Eventos
document.getElementById('programa')?.addEventListener('change', cargarUnidades);
document.getElementById('semestre').addEventListener('change', cargarUnidades);
document.querySelector('input[name="anio"]').addEventListener('input', cargarUnidades);
</script>
</body>
</html>
