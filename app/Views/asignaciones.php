<?php
require_once __DIR__ . '/../Core/Auth.php';
Auth::check();

/**
 * VARIABLES ESPERADAS (usa defaults defensivos si no llegan)
 * $anioSel             int|null   Año filtrado (o null/Todos)
 * $aniosDisponibles    int[]      Años para el combo
 * $usuarios            array      [ ['id'=>..., 'nombre'=>..., 'rol'=>..., 'email'=>...], ... ]
 * $programas           string[]   Lista de programas
 * $semestres           string[]   ['I','II','III','IV','V','VI']
 * $asignaciones        array      [ ['id','usuario','email','rol','programa','anio','semestre','ud_nombre'], ... ]
 */

$anioSel          = isset($anioSel) ? $anioSel : (isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : '');
$aniosDisponibles = $aniosDisponibles ?? (function(){
  $y = (int)date('Y'); $out=[];
  for($i=0;$i<6;$i++) $out[]=$y-$i;
  return $out;
})();
$usuarios   = $usuarios   ?? [];
$programas  = $programas  ?? [];
$semestres  = $semestres  ?? ['I','II','III','IV','V','VI'];
$asignaciones = $asignaciones ?? [];

$flashOK  = $_SESSION['flash_ok']    ?? null;
$flashERR = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_error']);

$totalKpi = $asignaciones ? count($asignaciones) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Asignar Unidades Didácticas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body{background:#f6f7fb}
    .card-kpi{border:0; box-shadow:0 2px 12px rgba(10,10,10,.06)}
    .form-card{border:0; box-shadow:0 2px 10px rgba(10,10,10,.05)}
    .sticky-top-xs{position:sticky; top:0; z-index:1030}
    .badge-sem{font-weight:600}
    .table thead th{position:sticky; top:0; background:#fff; z-index:5}
    .btn-xxs{--bs-btn-padding-y:.2rem; --bs-btn-padding-x:.5rem; --bs-btn-font-size:.78rem}
    .pill-filter .btn{border-radius:2rem}
  </style>
</head>
<body>

<!-- Top bar -->
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm sticky-top-xs">
  <div class="container-fluid">
    <span class="navbar-brand fw-semibold">
      <i class="bi bi-people me-2 text-primary"></i>Asignar Unidades Didácticas
    </span>

    <form class="d-flex align-items-center gap-2 ms-auto" method="get" action="index.php">
      <input type="hidden" name="action" value="asignaciones">
      <label class="small text-muted mb-0">Año</label>
      <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach($aniosDisponibles as $y): ?>
          <option value="<?= (int)$y ?>" <?= ($anioSel===$y?'selected':'') ?>><?= (int)$y ?></option>
        <?php endforeach; ?>
      </select>
      <a class="btn btn-outline-secondary btn-sm" href="index.php?action=asignaciones"><i class="bi bi-eraser"></i> Limpiar</a>
      <a class="btn btn-outline-dark btn-sm" href="index.php?action=dashboard<?= $anioSel? '&anio='.$anioSel:'' ?>"><i class="bi bi-arrow-left"></i> Volver</a>
    </form>
  </div>
</nav>

<div class="container-fluid py-4">

  <?php if ($flashOK): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($flashOK) ?></div><?php endif; ?>
  <?php if ($flashERR): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($flashERR) ?></div><?php endif; ?>

  <div class="row g-4">
    <!-- KPIs -->
    <div class="col-12 col-lg-3">
      <div class="card card-kpi">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="display-6 text-primary"><i class="bi bi-list-check"></i></div>
          <div>
            <div class="small text-muted">Asignaciones registradas <?= $anioSel? "— Año {$anioSel}" : '— Año Todos' ?></div>
            <div class="h3 mb-0 fw-semibold"><?= (int)$totalKpi ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Formulario -->
    <div class="col-12 col-lg-9">
      <div class="card form-card">
        <div class="card-body">
          <div class="row g-3">
            <!-- usuario_id -->
            <div class="col-12 col-md-4">
              <label class="form-label small">Docente / Coordinador</label>
              <select name="usuario_id" id="fUsuario" class="form-select" form="frmAsignacion" required>
                <option value="">— Selecciona —</option>
                <?php foreach($usuarios as $u): ?>
                  <option value="<?= (int)$u['id'] ?>">
                    <?= htmlspecialchars($u['nombre'] ?? '') ?> (<?= htmlspecialchars($u['rol'] ?? '') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- programa -->
            <div class="col-12 col-md-3">
              <label class="form-label small">Programa</label>
              <select name="programa" id="fPrograma" class="form-select" form="frmAsignacion" required>
                <option value="">— Selecciona —</option>
                <?php foreach($programas as $p): ?>
                  <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- semestre -->
            <div class="col-6 col-md-2">
              <label class="form-label small">Semestre</label>
              <select name="semestre" id="fSemestre" class="form-select" form="frmAsignacion" required>
                <option value="">— Selecciona —</option>
                <?php foreach($semestres as $s): ?>
                  <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- anio -->
            <div class="col-6 col-md-3">
              <label class="form-label small">Año</label>
              <input type="number" min="2000" max="2100" step="1" class="form-control" name="anio" id="fAnio" form="frmAsignacion" value="<?= $anioSel ?: (int)date('Y') ?>" required>
            </div>

            <!-- unidad -->
            <div class="col-12">
              <label class="form-label small">Unidad Didáctica</label>
              <select name="unidad" id="fUnidad" class="form-select" form="frmAsignacion" required>
                <option value="">— Selecciona programa y semestre —</option>
                <?php /* Puedes precargar si lo deseas */ ?>
              </select>
              <div class="form-text">Una unidad solo puede asignarse a un docente por (Programa, Semestre y Año).</div>
            </div>

            <div class="col-12">
              <form id="frmAsignacion" method="post" action="index.php?action=asignacion_crear">
                <!-- Los inputs usan form="frmAsignacion" arriba -->
              </form>
              <button form="frmAsignacion" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Guardar asignación
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros + tabla -->
    <div class="col-12">
      <div class="card card-kpi">
        <div class="card-header bg-white">
          <div class="d-flex flex-wrap gap-2 align-items-end">
            <div class="me-auto">
              <span class="fw-semibold">Asignaciones registradas</span>
              <span class="text-muted">— Año <?= $anioSel ? (int)$anioSel : 'Todos' ?></span>
            </div>
            <div class="input-group input-group-sm" style="max-width:320px">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input id="q" type="search" class="form-control" placeholder="Buscar (usuario, programa, unidad...)">
            </div>
            <div>
              <select id="fProgTabla" class="form-select form-select-sm">
                <option value="">Todos los programas</option>
                <?php foreach($programas as $p): ?>
                  <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="pill-filter d-flex gap-1">
              <button class="btn btn-outline-secondary btn-xxs active" data-sem="">Todos</button>
              <?php foreach($semestres as $s): ?>
                <button class="btn btn-outline-primary btn-xxs" data-sem="<?= $s ?>"><?= $s ?></button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:60vh">
            <table class="table table-sm align-middle mb-0">
              <thead class="border-bottom">
                <tr>
                  <th style="width:42px">#</th>
                  <th>Usuario</th>
                  <th style="width:120px">Rol</th>
                  <th>Programa</th>
                  <th style="width:90px">Año</th>
                  <th style="width:110px">Semestre</th>
                  <th>Unidad Didáctica</th>
                  <th style="width:110px" class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody id="rows">
                <?php if (!$asignaciones): ?>
                  <tr><td colspan="8" class="text-center text-muted py-4">Sin asignaciones registradas.</td></tr>
                <?php else: $i=1; foreach($asignaciones as $a): ?>
                  <tr data-program="<?= htmlspecialchars($a['programa'] ?? '') ?>"
                      data-sem="<?= htmlspecialchars($a['semestre'] ?? '') ?>">
                    <td><?= $i++ ?></td>
                    <td class="fw-semibold">
                      <?= htmlspecialchars($a['usuario'] ?? '') ?>
                      <div class="text-muted small"><?= htmlspecialchars($a['email'] ?? '') ?></div>
                    </td>
                    <td><span class="badge text-bg-secondary"><?= htmlspecialchars($a['rol'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($a['programa'] ?? '') ?></td>
                    <td><?= (int)($a['anio'] ?? 0) ?></td>
                    <td><?= $a['semestre'] ? '<span class="badge text-bg-primary badge-sem">'.htmlspecialchars($a['semestre']).'</span>' : '—' ?></td>
                    <td><?= htmlspecialchars($a['ud_nombre'] ?? '') ?></td>
                    <td class="text-end">
                      <button class="btn btn-outline-danger btn-xxs" data-bs-toggle="modal" data-bs-target="#mDel" data-id="<?= (int)$a['id'] ?>">
                        <i class="bi bi-trash"></i> Eliminar
                      </button>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /row -->
</div><!-- /container -->

<!-- Modal eliminar -->
<div class="modal fade" id="mDel" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="index.php?action=asignacion_eliminar" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-trash text-danger me-2"></i>Eliminar asignación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        ¿Seguro que deseas eliminar esta asignación?
        <input type="hidden" name="id" id="delId">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* -------------------- Poblado dinámico de UD --------------------
   Si ya tienes un endpoint para obtener UD por programa/semestre/año,
   ajústalo aquí. Por defecto uso: index.php?action=api_ud_asignaciones
   Cambia a tu ruta real si es distinta (o elimina y rellena server-side).
------------------------------------------------------------------ */
const selProg = document.getElementById('fPrograma');
const selSem  = document.getElementById('fSemestre');
const selAnio = document.getElementById('fAnio');
const selUD   = document.getElementById('fUnidad');

async function cargarUD() {
  selUD.innerHTML = '<option value="">Cargando…</option>';
  const p = (selProg.value||'').trim();
  const s = (selSem.value||'').trim();
  const a = (selAnio.value||'').trim();

  if(!p || !s || !a){ selUD.innerHTML = '<option value="">— Selecciona programa y semestre —</option>'; return; }

  try{
    // Ajusta la acción a tu controlador real:
    const url = `index.php?action=api_ud_asignaciones&programa=${encodeURIComponent(p)}&semestre=${encodeURIComponent(s)}&anio=${encodeURIComponent(a)}`;
    const res = await fetch(url, {headers:{'X-Requested-With':'fetch'}});
    const data = (await res.json()) || [];

    if(!data.length){
      selUD.innerHTML = '<option value="">No hay unidades disponibles</option>';
      return;
    }
    selUD.innerHTML = '<option value="">— Selecciona —</option>' + data.map(u => `<option value="${u}">${u}</option>`).join('');
  }catch(e){
    selUD.innerHTML = '<option value="">Error al cargar</option>';
  }
}
selProg.addEventListener('change', cargarUD);
selSem.addEventListener('change', cargarUD);
selAnio.addEventListener('input', cargarUD);

/* -------- Filtros tabla -------- */
const q = document.getElementById('q');
const fprog = document.getElementById('fProgTabla');
const rows = Array.from(document.querySelectorAll('#rows tr'));
let semFilter = '';

document.querySelectorAll('.pill-filter [data-sem]').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.pill-filter .btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    semFilter = btn.getAttribute('data-sem') || '';
    applyFilters();
  });
});
q && q.addEventListener('input', applyFilters);
fprog && fprog.addEventListener('change', applyFilters);

function applyFilters(){
  const text = (q?.value || '').toLowerCase();
  const prog = fprog?.value || '';
  rows.forEach(tr=>{
    const matchesText = !text || tr.innerText.toLowerCase().includes(text);
    const matchesProg = !prog || (tr.getAttribute('data-program')===prog);
    const matchesSem  = !semFilter || (tr.getAttribute('data-sem')===semFilter);
    tr.style.display = (matchesText && matchesProg && matchesSem) ? '' : 'none';
  });
}

/* ---- Modal eliminar: set id ---- */
const mDel = document.getElementById('mDel');
mDel?.addEventListener('show.bs.modal', (ev)=>{
  const id = ev.relatedTarget?.getAttribute('data-id') || '';
  document.getElementById('delId').value = id;
});
</script>
</body>
</html>
