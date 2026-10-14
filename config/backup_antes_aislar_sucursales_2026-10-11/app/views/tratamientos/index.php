<?php
// Registro por día (Peluquería: recaudo directo). Tratamiento usa tratamientos/lista.php por sus abonos.
$diasCortos = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$diasLargos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$meses      = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

$money   = fn($n) => '$' . number_format((float)$n, 2);
$ts      = strtotime($fecha);
$tituloDia = $diasLargos[date('N', $ts) - 1] . ' ' . date('j', $ts) . ' de ' . $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);
$R       = BASE_URL . '/' . $modulo['ruta'];

$totalDia    = array_sum(array_column($tratamientos, 'valor'));
$totalSemana = array_sum(array_column($resumenSemana, 'total'));
$cantSemana  = array_sum(array_column($resumenSemana, 'cantidad'));
$perros      = count(array_filter($tratamientos, fn($t) => $t['especie'] === 'perro'));
$gatos       = count($tratamientos) - $perros;

$diaAnt = date('Y-m-d', strtotime("$fecha -1 day"));
$diaSig = date('Y-m-d', strtotime("$fecha +1 day"));
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; <?= $modulo['titulo'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .dia-nav { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
        .dia-titulo { font-weight: 700; font-size: 1rem; margin: 0 .5rem; }

        .semana-strip { display: grid; grid-template-columns: repeat(7, minmax(86px, 1fr)); gap: .5rem; overflow-x: auto; }
        .dia-chip {
            display: block; text-decoration: none; color: inherit;
            border: 1px solid #e2e8f0; border-radius: 10px; padding: .5rem .6rem; background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .dia-chip:hover { border-color: var(--primary-light); color: inherit; }
        .dia-chip.activo { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, .12); }
        .dia-chip.hoy .dia-chip-num { color: var(--primary); }
        .dia-chip-nom { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 600; }
        .dia-chip-num { font-size: 1.1rem; font-weight: 700; line-height: 1.2; }
        .dia-chip-info { font-size: .7rem; color: #64748b; white-space: nowrap; }
        .dia-chip-info.vacio { color: #cbd5e1; }

        .form-rapido .form-control, .form-rapido .form-select { font-size: .9rem; }
        .especie-ico {
            width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        .especie-perro { background: #fef3c7; }
        .especie-gato  { background: #ede9fe; }
        .monto { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0"><?= $modulo['titulo'] ?></h5>
                <p class="topbar-date mb-0">Registro diario de <?= $modulo['plural'] ?></p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <div class="dropdown-header">
                                <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($usuario['email']) ?></div>
                                <span class="badge badge-rol mt-1"><?= htmlspecialchars(ucfirst($usuario['rol'])) ?></span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout" onclick="return confirm('¿Cerrar sesión?')">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="main-content">

            <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Navegación por día -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="dia-nav">
                            <a href="<?= $R ?>?fecha=<?= $diaAnt ?>" class="btn btn-sm btn-light" title="Día anterior"><i class="bi bi-chevron-left"></i></a>
                            <a href="<?= $R ?>?fecha=<?= $hoy ?>" class="btn btn-sm btn-light fw-semibold">Hoy</a>
                            <a href="<?= $R ?>?fecha=<?= $diaSig ?>" class="btn btn-sm btn-light" title="Día siguiente"><i class="bi bi-chevron-right"></i></a>
                            <span class="dia-titulo">
                                <?= $tituloDia ?>
                                <?php if ($fecha === $hoy): ?><span class="badge bg-primary-soft text-primary ms-1">Hoy</span><?php endif; ?>
                            </span>
                        </div>
                        <input type="date" class="form-control form-control-sm" style="width:150px" value="<?= $fecha ?>"
                               title="Ir a un día" onchange="if (this.value) location.href='<?= $R ?>?fecha=' + this.value">
                    </div>

                    <div class="semana-strip">
                        <?php for ($i = 0; $i < 7; $i++):
                            $d   = date('Y-m-d', strtotime("$iniSemana +$i day"));
                            $res = $resumenSemana[$d] ?? null;
                            $cls = 'dia-chip' . ($d === $fecha ? ' activo' : '') . ($d === $hoy ? ' hoy' : '');
                        ?>
                        <a href="<?= $R ?>?fecha=<?= $d ?>" class="<?= $cls ?>">
                            <div class="dia-chip-nom"><?= $diasCortos[$i] ?></div>
                            <div class="dia-chip-num"><?= date('j', strtotime($d)) ?></div>
                            <?php if ($res): ?>
                            <div class="dia-chip-info"><?= $res['cantidad'] ?> · <?= $money($res['total']) ?></div>
                            <?php else: ?>
                            <div class="dia-chip-info vacio">Sin registros</div>
                            <?php endif; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Stats del día -->
            <?php if (!empty($reparto)): $rp = $reparto; ?>
            <div class="row g-3 mb-3">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft"><i class="bi bi-cash-coin text-success"></i></div>
                        <div>
                            <div class="stat-value"><?= $money($rp['dia']['total']) ?></div>
                            <div class="stat-label">Cobrado del día · <?= $rp['dia']['cantidad'] ?> servicios</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card" style="box-shadow: inset 3px 0 0 #4f46e5">
                        <div class="stat-icon bg-primary-soft"><i class="bi bi-shop text-primary"></i></div>
                        <div>
                            <div class="stat-value" style="color:#4f46e5"><?= $money($rp['dia']['negocio']) ?></div>
                            <div class="stat-label">Le toca al negocio</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card" style="box-shadow: inset 3px 0 0 #db2777">
                        <div class="stat-icon" style="background:#fce7f3"><i class="bi bi-scissors" style="color:#db2777"></i></div>
                        <div>
                            <div class="stat-value" style="color:#db2777"><?= $money($rp['dia']['peluqueria']) ?></div>
                            <div class="stat-label">Le toca a la peluquería</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft"><i class="bi bi-calendar-week text-info"></i></div>
                        <div>
                            <div class="stat-value"><?= $money($rp['semana']['total']) ?></div>
                            <div class="stat-label">
                                Semana · negocio <?= $money($rp['semana']['negocio']) ?> · peluquería <?= $money($rp['semana']['peluqueria']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 small text-muted mb-3">
                <i class="bi bi-pie-chart-fill"></i>
                Reparto actual: negocio <strong><?= rtrim(rtrim(number_format($rp['pct_negocio'], 2), '0'), '.') ?> %</strong> ·
                peluquería <strong><?= rtrim(rtrim(number_format($rp['pct_peluqueria'], 2), '0'), '.') ?> %</strong>
                <a href="<?= BASE_URL ?>/configuracion/peluqueria" class="ms-1">Cambiar</a>
            </div>
            <?php else: ?>
            <div class="row g-3 mb-3">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft"><i class="bi bi-<?= $modulo['icono'] ?> text-primary"></i></div>
                        <div>
                            <div class="stat-value"><?= count($tratamientos) ?></div>
                            <div class="stat-label"><?= ucfirst($modulo['plural']) ?> del día</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft"><i class="bi bi-cash-coin text-success"></i></div>
                        <div>
                            <div class="stat-value"><?= $money($totalDia) ?></div>
                            <div class="stat-label">Total del día</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft" style="font-size:1.3rem">🐶</div>
                        <div>
                            <div class="stat-value"><?= $perros ?> <span class="text-muted fs-6">/ <?= $gatos ?> 🐱</span></div>
                            <div class="stat-label">Perros / gatos del día</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft"><i class="bi bi-calendar-week text-info"></i></div>
                        <div>
                            <div class="stat-value"><?= $money($totalSemana) ?></div>
                            <div class="stat-label"><?= $cantSemana ?> <?= $modulo['plural'] ?> esta semana</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>

            <!-- Agregar -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-plus-circle text-primary me-1"></i>Agregar <?= $modulo['singular'] ?> al <?= date('d/m/Y', $ts) ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $R ?>/guardar" class="form-rapido row g-2 align-items-end" autocomplete="off">
                        <input type="hidden" name="id" value="0">
                        <input type="hidden" name="fecha" value="<?= $fecha ?>">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="nuevo-nombre" maxlength="100" required placeholder="Nombre de la mascota">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Especie <span class="text-danger">*</span></label>
                            <select class="form-select" name="especie" required>
                                <?php foreach ($especies as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Descripción <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="descripcion" maxlength="255" required placeholder="<?= htmlspecialchars($modulo['placeholder']) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Valor <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="valor" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-primary-custom" title="Agregar" style="padding:.45rem .5rem">
                                <i class="bi bi-plus-lg"></i><span class="d-md-none ms-1">Agregar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Listado del día -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold"><?= ucfirst($modulo['plural']) ?> del día</h6>
                    <?php if ($tratamientos): ?>
                    <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar…" style="width:200px;">
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width:50px">#</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Valor</th>
                                    <?php if (!empty($reparto)): ?>
                                    <th class="text-end" style="color:#4f46e5">Negocio</th>
                                    <th class="text-end" style="color:#db2777">Peluquería</th>
                                    <?php endif; ?>
                                    <th class="text-center" style="width:110px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tratamientos)): ?>
                                <tr>
                                    <td colspan="<?= empty($reparto) ? 5 : 7 ?>" class="text-center text-muted py-5">
                                        <i class="bi bi-<?= $modulo['icono'] ?> fs-2 d-block mb-2 opacity-25"></i>
                                        No hay <?= $modulo['plural'] ?> registrados este día.
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($tratamientos as $i => $t): ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="especie-ico especie-<?= $t['especie'] ?>"><?= $t['especie'] === 'gato' ? '🐱' : '🐶' ?></span>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($t['nombre']) ?></div>
                                                <div class="text-muted small"><?= $especies[$t['especie']] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($t['descripcion']) ?></td>
                                    <td class="text-end monto"><?= $money($t['valor']) ?></td>
                                    <?php if (!empty($reparto)): ?>
                                    <td class="text-end">
                                        <span class="monto" style="color:#4f46e5"><?= $money($t['parte_negocio']) ?></span>
                                        <div class="text-muted" style="font-size:.7rem"><?= rtrim(rtrim(number_format(100 - $t['pct'], 2), '0'), '.') ?> %</div>
                                    </td>
                                    <td class="text-end">
                                        <span class="monto" style="color:#db2777"><?= $money($t['parte_peluqueria']) ?></span>
                                        <div class="text-muted" style="font-size:.7rem"><?= rtrim(rtrim(number_format($t['pct'], 2), '0'), '.') ?> %</div>
                                    </td>
                                    <?php endif; ?>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick='editar(<?= json_encode($t, $jsonFlags) ?>)'>
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar"
                                                    onclick='eliminar(<?= (int)$t['id'] ?>, <?= json_encode($t['nombre'], $jsonFlags) ?>)'>
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if ($tratamientos): ?>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="ps-3 text-end">Total del día</td>
                                    <td class="text-end monto text-success"><?= $money($totalDia) ?></td>
                                    <?php if (!empty($reparto)): ?>
                                    <td class="text-end monto" style="color:#4f46e5"><?= $money($reparto['dia']['negocio']) ?></td>
                                    <td class="text-end monto" style="color:#db2777"><?= $money($reparto['dia']['peluqueria']) ?></td>
                                    <?php endif; ?>
                                    <td></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL EDITAR ──────────────────────────────────── -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= $R ?>/guardar" autocomplete="off">
                <input type="hidden" name="id" id="e-id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Editar <?= $modulo['singular'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="e-nombre" maxlength="100" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Especie <span class="text-danger">*</span></label>
                            <select class="form-select" name="especie" id="e-especie" required>
                                <?php foreach ($especies as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="descripcion" id="e-descripcion" maxlength="255" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Valor <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="valor" id="e-valor" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Día</label>
                            <input type="date" class="form-control" name="fecha" id="e-fecha" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= $R ?>/eliminar" id="formEliminar" class="d-none">
    <input type="hidden" name="id">
    <input type="hidden" name="fecha" value="<?= $fecha ?>">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editar(t) {
    ['id', 'nombre', 'especie', 'descripcion', 'valor', 'fecha'].forEach(c => document.getElementById('e-' + c).value = t[c] ?? '');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditar')).show();
}

function eliminar(id, nombre) {
    if (!confirm('¿Eliminar el <?= $modulo['singular'] ?> de ' + nombre + '?')) return;
    const f = document.getElementById('formEliminar');
    f.id.value = id;
    f.submit();
}

const buscador = document.getElementById('buscador');
if (buscador) {
    buscador.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#tabla tbody tr').forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// Tras agregar, dejar el cursor listo para el siguiente
<?php if (!empty($success) && empty($error)): ?>
document.getElementById('nuevo-nombre').focus();
<?php endif; ?>
</script>
</body>
</html>
