<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/Periodo.php';
Auth::check();

$usuario        = $_SESSION['usuario'] ?? [];
$rol            = $usuario['rol'] ?? '';
$nombre         = $usuario['nombre'] ?? strtoupper($rol);
$programaActual = $usuario['programa'] ?? null;

$esJefeOAdmin = in_array($rol, ['jefe', 'admin'], true);
$esDocOCoord  = in_array($rol, ['docente', 'coordinador'], true);

// ------------------------------
// Año seleccionado (filtro UI)
// ------------------------------
$anioSel = (isset($_GET['anio']) && $_GET['anio'] !== '' && is_numeric($_GET['anio']))
  ? (int)$_GET['anio']
  : '';

// Lista de años disponible (inyectada o fallback)
if (empty($aniosDisponibles)) {
  $anioActual        = (int)date('Y');
  $aniosDisponibles  = [];
  for ($y = $anioActual; $y >= $anioActual - 6; $y--) { $aniosDisponibles[] = $y; }
}

// ------------------------------
// Estado del periodo (por año)
// ------------------------------
$periodoAbierto = false;
try {
  if ($anioSel !== '') {
    if (method_exists('Periodo', 'abiertoEn')) {
      $periodoAbierto = Periodo::abiertoEn((int)$anioSel);
    } elseif (method_exists('Periodo', 'abiertoAhora')) {
      // Algunos modelos aceptan $anio como opcional
      $periodoAbierto = Periodo::abiertoAhora((int)$anioSel);
    } else {
      $periodoAbierto = false;
    }
  } else {
    // Sin año (vista "Todos")
    if (method_exists('Periodo', 'abiertoAhora')) {
      $periodoAbierto = Periodo::abiertoAhora();
    } else {
      $periodoAbierto = false;
    }
  }
} catch (Throwable $e) {
  $periodoAbierto = false;
}

$puedeReemplazar = $esDocOCoord && $periodoAbierto;

// ------------------------------
// Datos inyectados desde el controlador
// ------------------------------
$silabos = $silabos ?? [];

// Agregaciones
$conteoTotal  = count($silabos);
$porSemestre  = ['I'=>0,'II'=>0,'III'=>0,'IV'=>0,'V'=>0,'VI'=>0];
$porPrograma  = [];
$programasSet = [];

foreach ($silabos as $s) {
  $sem = (string)($s['semestre'] ?? '');
  if (isset($porSemestre[$sem])) $porSemestre[$sem]++;

  $prog = trim((string)($s['carrera'] ?? $s['programa'] ?? ''));
  if ($prog !== '') {
    $programasSet[$prog] = true;
    $porPrograma[$prog]  = ($porPrograma[$prog] ?? 0) + 1;
  }
}
$listaProgramas = array_keys($programasSet);
sort($listaProgramas);

// Flashes
$flashOK  = $_SESSION['flash_ok']    ?? null;
$flashERR = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Repositorio de Sílabos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body{background:#f6f7fb}
    .card-kpi{border:0; box-shadow:0 2px 12px rgba(10,10,10,.05)}
    .badge-sem{font-weight:600}
    .table thead th{position:sticky; top:0; background:#fff; z-index:5}
    .sticky-tools{position:sticky; top:1rem}
    .btn-xxs{--bs-btn-padding-y:.2rem; --bs-btn-padding-x:.5rem; --bs-btn-font-size:.78rem}
    .pill-filter .btn{border-radius:2rem}
  </style>
</head>
<body>
  <!-- Top Bar -->
  <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
    <div class="container-fluid">
      <span class="navbar-brand fw-semibold">
        <i class="bi bi-folder2-open me-2 text-primary"></i>Repositorio de Sílabos
      </span>

      <div class="d-flex align-items-center gap-2 ms-auto">
        <form class="d-flex align-items-center gap-2" method="get" action="index.php">
          <input type="hidden" name="action" value="dashboard">
          <label class="small text-muted mb-0">Año</label>
          <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($aniosDisponibles as $y): ?>
              <option value="<?= (int)$y ?>" <?= ($anioSel === (int)$y ? 'selected' : '') ?>><?= (int)$y ?></option>
            <?php endforeach; ?>
          </select>
          <a class="btn btn-outline-secondary btn-sm" href="index.php?action=dashboard">Limpiar</a>
        </form>
        <a class="btn btn-outline-danger btn-sm" href="index.php?action=logout" title="Cerrar sesión">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>
  </nav>

  <div class="container-fluid py-4">
    <!-- saludo -->
    <div class="mb-3 small">
      Bienvenido, <b><?= htmlspecialchars($nombre) ?></b>
      <?php if ($rol): ?>(Rol: <b><?= htmlspecialchars($rol) ?></b>)<?php endif; ?>
      <?php if ($esDocOCoord && $programaActual): ?> — Programa: <b><?= htmlspecialchars($programaActual) ?></b><?php endif; ?>
    </div>

    <?php if ($flashOK):  ?><div class="alert alert-success"><?= htmlspecialchars($flashOK)  ?></div><?php endif; ?>
    <?php if ($flashERR): ?><div class="alert alert-danger"><?=  htmlspecialchars($flashERR) ?></div><?php endif; ?>

    <div class="row g-4">
      <!-- Main -->
      <div class="col-xl-9">
        <!-- KPIs -->
        <div class="row g-3 mb-1">
          <div class="col-6 col-md-3">
            <div class="card card-kpi">
              <div class="card-body d-flex align-items-center gap-3">
                <div class="display-6 text-primary"><i class="bi bi-journal-richtext"></i></div>
                <div>
                  <div class="small text-muted">Total sílabos</div>
                  <div class="h3 mb-0 fw-semibold"><?= (int)$conteoTotal ?></div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card card-kpi">
              <div class="card-body d-flex align-items-center gap-3">
                <div class="display-6 text-success"><i class="bi bi-mortarboard"></i></div>
                <div>
                  <div class="small text-muted">Programas</div>
                  <div class="h3 mb-0 fw-semibold"><?= count($listaProgramas) ?></div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="card card-kpi">
              <div class="card-body py-2">
                <div class="small text-muted">Distribución por semestre</div>
                <div class="mt-2">
                  <?php foreach (['I','II','III','IV','V','VI'] as $sx): ?>
                    <span class="me-2">Sem. <?= $sx ?> <span class="badge text-bg-primary badge-sem"><?= (int)$porSemestre[$sx] ?></span></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-lista" type="button" role="tab">
              <i class="bi bi-table me-1"></i> Lista
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-estad" type="button" role="tab">
              <i class="bi bi-bar-chart-line me-1"></i> Estadísticas
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-programas" type="button" role="tab">
              <i class="bi bi-collection me-1"></i> Por programa
            </button>
          </li>
        </ul>

        <div class="tab-content bg-white border border-top-0 rounded-bottom shadow-sm">
          <!-- LISTA -->
          <div class="tab-pane fade show active p-3" id="tab-lista" role="tabpanel">
            <div class="row g-2 align-items-end mb-2">
              <div class="col-12 col-md-4">
                <label class="form-label small">Buscar</label>
                <input id="q" type="search" class="form-control form-control-sm" placeholder="Programa, unidad, semestre...">
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label small">Programa</label>
                <select id="fprog" class="form-select form-select-sm">
                  <option value="">— Todos —</option>
                  <?php foreach ($listaProgramas as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label small">Semestre</label>
                <div class="pill-filter d-flex flex-wrap gap-1">
                  <button class="btn btn-outline-secondary btn-xxs active" data-sem="">Todos</button>
                  <?php foreach (['I','II','III','IV','V','VI'] as $sx): ?>
                    <button class="btn btn-outline-primary btn-xxs" data-sem="<?= $sx ?>"><?= $sx ?></button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="table-responsive" style="max-height:60vh">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th style="width:60px">#</th>
                    <th style="min-width:240px">Programa</th>
                    <th style="width:80px">Año</th>
                    <th style="width:110px">Semestre</th>
                    <th style="min-width:320px">Unidad Didáctica</th>
                    <th style="width:120px">Archivo</th>
                    <?php if ($puedeReemplazar): ?><th style="width:120px">Acciones</th><?php endif; ?>
                  </tr>
                </thead>
                <tbody id="rows">
                  <?php if (!$silabos): ?>
                  <tr><td colspan="<?= $puedeReemplazar ? 7 : 6 ?>" class="text-center text-muted py-4">No hay registros para mostrar.</td></tr>
                  <?php else: ?>
                    <?php $i=1; foreach ($silabos as $s):
                      $id   = (int)($s['id'] ?? 0);
                      $prog = (string)($s['carrera'] ?? $s['programa'] ?? '');
                      $anio = (int)($s['anio'] ?? 0);
                      $sem  = (string)($s['semestre'] ?? '');
                      $ud   = (string)($s['unidad_didactica'] ?? $s['ud_nombre'] ?? '');
                      $arc  = (string)($s['archivo'] ?? '');
                      $offId = 'offRep_'.$id;
                    ?>
                    <tr data-program="<?= htmlspecialchars($prog) ?>" data-sem="<?= htmlspecialchars($sem) ?>">
                      <td><?= $i++ ?></td>
                      <td class="fw-semibold"><?= htmlspecialchars($prog) ?></td>
                      <td><?= $anio ?></td>
                      <td><?= $sem ? '<span class="badge text-bg-primary badge-sem">'.htmlspecialchars($sem).'</span>' : '<span class="text-muted">—</span>' ?></td>
                      <td><?= htmlspecialchars($ud) ?></td>
                      <td>
                        <?php if ($arc): ?>
                          <a class="btn btn-outline-primary btn-xxs" href="<?= htmlspecialchars($arc) ?>" target="_blank"><i class="bi bi-download me-1"></i>Descargar</a>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <?php if ($puedeReemplazar): ?>
                      <td>
                        <button class="btn btn-warning btn-xxs" data-bs-toggle="offcanvas" data-bs-target="#<?= $offId ?>">
                          <i class="bi bi-arrow-repeat me-1"></i>Reemplazar
                        </button>
                      </td>
                      <?php endif; ?>
                    </tr>

                    <?php if ($puedeReemplazar): ?>
                    <div class="offcanvas offcanvas-end" tabindex="-1" id="<?= $offId ?>" aria-labelledby="lab<?= $offId ?>">
                      <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="lab<?= $offId ?>">Reemplazar archivo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                      </div>
                      <div class="offcanvas-body">
                        <div class="mb-2 small text-muted">Programa</div>
                        <div class="fw-semibold mb-1"><?= htmlspecialchars($prog) ?> — Sem. <?= htmlspecialchars($sem) ?></div>
                        <div class="small mb-3"><?= htmlspecialchars($ud) ?></div>
                        <form method="POST" action="index.php?action=silabo_reemplazar" enctype="multipart/form-data">
                          <input type="hidden" name="silabo_id" value="<?= $id ?>">
                          <div class="mb-3">
                            <label class="form-label">Archivo (PDF/DOC/DOCX)</label>
                            <input type="file" class="form-control" name="archivo" accept=".pdf,.doc,.docx" required>
                          </div>
                          <div class="d-grid">
                            <button class="btn btn-warning"><i class="bi bi-cloud-upload me-1"></i>Reemplazar</button>
                          </div>
                        </form>
                      </div>
                    </div>
                    <?php endif; ?>

                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ESTADISTICAS -->
          <div class="tab-pane fade p-3" id="tab-estad" role="tabpanel">
            <div class="row g-3">
              <div class="col-lg-7">
                <div class="card card-kpi">
                  <div class="card-header bg-white fw-semibold">Sílabos por semestre</div>
                  <div class="card-body">
                    <canvas id="chartSem"></canvas>
                  </div>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="card card-kpi">
                  <div class="card-header bg-white fw-semibold">Top programas</div>
                  <div class="card-body">
                    <?php if (!$porPrograma): ?>
                      <div class="text-muted">Sin datos.</div>
                    <?php else: ?>
                      <ol class="mb-0">
                        <?php arsort($porPrograma); foreach ($porPrograma as $pp => $cc): ?>
                          <li class="mb-1 d-flex justify-content-between">
                            <span><?= htmlspecialchars($pp) ?></span>
                            <span class="badge text-bg-primary"><?= (int)$cc ?></span>
                          </li>
                        <?php endforeach; ?>
                      </ol>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- POR PROGRAMA -->
          <div class="tab-pane fade p-3" id="tab-programas" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead><tr><th style="width:60px">#</th><th>Programa</th><th style="width:120px">Total</th></tr></thead>
                <tbody>
                  <?php if (!$porPrograma): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Sin datos.</td></tr>
                  <?php else: $ix=1; foreach ($porPrograma as $pp=>$cc): ?>
                    <tr>
                      <td><?= $ix++ ?></td>
                      <td><?= htmlspecialchars($pp) ?></td>
                      <td><span class="badge text-bg-primary badge-sem"><?= (int)$cc ?></span></td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-xl-3">
        <div class="sticky-tools">
          <div class="card card-kpi mb-3">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="display-6"><?= $periodoAbierto ? '🟢' : '🟠' ?></div>
              <div>
                <div class="small text-muted">Periodo de subida</div>
                <div class="fw-bold"><?= $periodoAbierto ? 'ABIERTO' : 'CERRADO' ?></div>
                <?php if ($esDocOCoord && !$periodoAbierto): ?>
                  <div class="small text-muted">No podrás reemplazar archivos hasta que Jefatura/Administración lo abra.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="card card-kpi">
            <div class="card-header bg-white fw-semibold">Accesos rápidos</div>
            <div class="list-group list-group-flush">
              <?php if ($esDocOCoord): ?>
                <a class="list-group-item list-group-item-action" href="index.php?action=subirForm<?= ($anioSel !== '' ? '&anio='.$anioSel : '') ?>">
                  <i class="bi bi-cloud-upload me-2"></i>Subir sílabo
                </a>
              <?php endif; ?>

              <?php if ($esJefeOAdmin): ?>
                <a class="list-group-item list-group-item-action" href="index.php?action=usuarios">
                  <i class="bi bi-people me-2"></i>Gestionar usuarios
                </a>
                <a class="list-group-item list-group-item-action" href="index.php?action=periodo<?= ($anioSel !== '' ? '&anio='.$anioSel : '') ?>">
                  <i class="bi bi-clock-history me-2"></i>Periodo de subida
                </a>
                <a class="list-group-item list-group-item-action" href="index.php?action=asignaciones<?= ($anioSel !== '' ? '&anio='.$anioSel : '') ?>">
                  <i class="bi bi-pin-map me-2"></i>Asignar UD a docentes
                </a>
                <a class="list-group-item list-group-item-action" href="index.php?action=anios">
                  <i class="bi bi-calendar3 me-2"></i>Años académicos
                </a>
              <?php endif; ?>

              <a class="list-group-item list-group-item-action" href="index.php?action=consulta">
                <i class="bi bi-search me-2"></i>Consulta de Sílabos (público)
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    // Filtros cliente
    const q = document.getElementById('q');
    const fprog = document.getElementById('fprog');
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

    // Chart
    const ctx = document.getElementById('chartSem');
    if (ctx) {
      const data = {
        labels: ['I','II','III','IV','V','VI'],
        datasets: [{
          label: 'Sílabos',
          data: [<?= (int)$porSemestre['I'] ?>,<?= (int)$porSemestre['II'] ?>,<?= (int)$porSemestre['III'] ?>,<?= (int)$porSemestre['IV'] ?>,<?= (int)$porSemestre['V'] ?>,<?= (int)$porSemestre['VI'] ?>],
          borderWidth:1
        }]
      };
      new Chart(ctx,{type:'bar', data, options:{responsive:true, scales:{y:{beginAtZero:true, ticks:{precision:0}}}}});
    }
  </script>
</body>
</html>
