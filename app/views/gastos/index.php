<?php
$totalPeriodo = array_sum(array_column($gastos, 'monto'));
$balance      = $ventasPeriodo - $totalPeriodo;
$variacion    = $totales['mes_anterior'] > 0
    ? round((($totales['mes'] - $totales['mes_anterior']) / $totales['mes_anterior']) * 100)
    : null;
$money = fn($n) => '$' . number_format((float)$n, 2);

$filtrosQuery = http_build_query(['desde' => $desde, 'hasta' => $hasta]);

// Atajos de período
$hoyF = date('Y-m-d');
$atajos = [
    'Hoy'          => [$hoyF, $hoyF],
    'Este mes'     => [date('Y-m-01'), $hoyF],
    'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'Este año'     => [date('Y-01-01'), $hoyF],
];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Gastos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .gasto-ico {
            width: 34px; height: 34px; border-radius: 9px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .95rem; flex-shrink: 0;
            color: #dc2626; background: #fee2e2;
        }
        .monto { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
        .filtro-bar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: end; }
        .filtro-bar .form-control { font-size: .82rem; }
        .atajo { font-size: .75rem; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Gastos</h5>
                <p class="topbar-date mb-0">Registro y reporte de egresos de la sucursal</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-primary-custom btn-sm" onclick="nuevoGasto()">
                    <i class="bi bi-plus-lg me-1"></i> Registrar gasto
                </button>
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

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger-soft"><i class="bi bi-cash-stack text-danger"></i></div>
                        <div>
                            <div class="stat-value"><?= $money($totales['hoy']) ?></div>
                            <div class="stat-label">Gastado hoy</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft"><i class="bi bi-calendar-month text-warning"></i></div>
                        <div>
                            <div class="stat-value"><?= $money($totales['mes']) ?></div>
                            <div class="stat-label">
                                Este mes
                                <?php if ($variacion !== null): ?>
                                · <span class="fw-semibold <?= $variacion > 0 ? 'text-danger' : 'text-success' ?>">
                                    <i class="bi bi-arrow-<?= $variacion > 0 ? 'up' : 'down' ?>"></i><?= abs($variacion) ?>% vs mes ant.
                                  </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft"><i class="bi bi-funnel-fill text-info"></i></div>
                        <div>
                            <div class="stat-value"><?= $money($totalPeriodo) ?></div>
                            <div class="stat-label">Gastos del período</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon <?= $balance >= 0 ? 'bg-success-soft' : 'bg-danger-soft' ?>">
                            <i class="bi bi-graph-<?= $balance >= 0 ? 'up' : 'down' ?>-arrow <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>"></i>
                        </div>
                        <div>
                            <div class="stat-value <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>"><?= ($balance < 0 ? '-' : '') . $money(abs($balance)) ?></div>
                            <div class="stat-label" title="Ventas, peluquería, laboratorios y abonos de tratamientos del período, menos los gastos">
                                Ingresos <?= $money($ventasPeriodo) ?> − gastos
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" action="<?= BASE_URL ?>/gastos" class="filtro-bar">
                        <div>
                            <label class="form-label small fw-semibold mb-1">Desde</label>
                            <input type="date" class="form-control form-control-sm" name="desde" value="<?= $desde ?>" max="<?= $hoyF ?>">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Hasta</label>
                            <input type="date" class="form-control form-control-sm" name="hasta" value="<?= $hasta ?>" max="<?= $hoyF ?>">
                        </div>
                        <button class="btn btn-sm btn-primary-custom" style="padding:.35rem .9rem"><i class="bi bi-search me-1"></i>Filtrar</button>
                        <div class="d-flex flex-wrap gap-1 ms-md-2">
                            <?php foreach ($atajos as $lbl => [$d, $h]): ?>
                            <a href="<?= BASE_URL ?>/gastos?<?= http_build_query(['desde' => $d, 'hasta' => $h]) ?>"
                               class="btn btn-sm atajo <?= ($d === $desde && $h === $hasta) ? 'btn-secondary' : 'btn-light' ?>"><?= $lbl ?></a>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?= BASE_URL ?>/gastos/imprimir?<?= $filtrosQuery ?>" target="_blank" class="btn btn-sm btn-outline-secondary ms-auto">
                            <i class="bi bi-printer me-1"></i>Imprimir reporte
                        </a>
                    </form>
                </div>
            </div>

            <!-- Listado -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold">
                        Gastos del <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?>
                    </h6>
                    <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar…" style="width:200px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaGastos">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Descripción</th>
                                    <th>Registrado por</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($gastos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-wallet2 fs-2 d-block mb-2 opacity-25"></i>
                                        No hay gastos registrados en este período.
                                        <div><a href="#" onclick="nuevoGasto(); return false;">Registrar el primero</a></div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($gastos as $g): ?>
                                <tr>
                                    <td class="ps-3 small text-nowrap"><?= date('d/m/Y', strtotime($g['fecha'])) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="gasto-ico"><i class="bi bi-wallet2"></i></span>
                                            <div class="min-w-0">
                                                <div class="fw-semibold"><?= htmlspecialchars($g['descripcion']) ?></div>
                                                <?php if ($g['notas']): ?>
                                                <div class="text-muted small text-truncate" style="max-width:420px"><?= htmlspecialchars($g['notas']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($g['usuario_nombre'] ?? '—') ?></td>
                                    <td class="text-end monto text-danger"><?= $money($g['monto']) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick='editarGasto(<?= json_encode($g, $jsonFlags) ?>)'>
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar"
                                                    onclick='eliminarGasto(<?= (int)$g['id'] ?>, <?= json_encode($g['descripcion'], $jsonFlags) ?>)'>
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (!empty($gastos)): ?>
                <div class="card-footer bg-white border-0 d-flex justify-content-between small">
                    <span class="text-muted"><span id="contadorVisible"><?= count($gastos) ?></span> de <?= count($gastos) ?> gastos</span>
                    <span>Total: <span class="monto text-danger"><?= $money($totalPeriodo) ?></span></span>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL GASTO ───────────────────────────────────── -->
<div class="modal fade" id="modalGasto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/gastos/guardar" id="formGasto">
                <input type="hidden" name="id" id="g-id" value="0">
                <input type="hidden" name="f_desde" value="<?= $desde ?>">
                <input type="hidden" name="f_hasta" value="<?= $hasta ?>">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-wallet2 text-danger me-2"></i><span id="gastoTitulo">Registrar gasto</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="descripcion" id="g-descripcion" maxlength="200" required
                                   placeholder="Ej: Pago de luz de septiembre">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="monto" id="g-monto" step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha" id="g-fecha" max="<?= $hoyF ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas</label>
                            <textarea class="form-control" name="notas" id="g-notas" rows="3" placeholder="Detalle adicional…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> <span id="gastoBtnTexto">Registrar gasto</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/gastos/eliminar" id="formEliminar" class="d-none">
    <input type="hidden" name="id">
    <input type="hidden" name="f_desde" value="<?= $desde ?>">
    <input type="hidden" name="f_hasta" value="<?= $hasta ?>">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const HOY = '<?= $hoyF ?>';
const campos = ['descripcion', 'monto', 'fecha', 'notas'];
const modal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGasto'));

function nuevoGasto() {
    document.getElementById('gastoTitulo').textContent   = 'Registrar gasto';
    document.getElementById('gastoBtnTexto').textContent = 'Registrar gasto';
    document.getElementById('g-id').value = '0';
    campos.forEach(c => document.getElementById('g-' + c).value = '');
    document.getElementById('g-fecha').value = HOY;
    modal().show();
    setTimeout(() => document.getElementById('g-descripcion').focus(), 300);
}

function editarGasto(g) {
    document.getElementById('gastoTitulo').textContent   = 'Editar gasto';
    document.getElementById('gastoBtnTexto').textContent = 'Guardar cambios';
    document.getElementById('g-id').value = g.id;
    campos.forEach(c => document.getElementById('g-' + c).value = g[c] ?? '');
    modal().show();
}

function eliminarGasto(id, desc) {
    if (!confirm('¿Eliminar el gasto «' + desc + '»?')) return;
    const f = document.getElementById('formEliminar');
    f.id.value = id;
    f.submit();
}

document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let visibles = 0;
    document.querySelectorAll('#tablaGastos tbody tr').forEach(tr => {
        const ok = tr.textContent.toLowerCase().includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) visibles++;
    });
    const c = document.getElementById('contadorVisible');
    if (c) c.textContent = visibles;
});
</script>
</body>
</html>
