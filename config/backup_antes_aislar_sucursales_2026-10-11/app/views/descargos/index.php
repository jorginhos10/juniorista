<?php
$money = fn($n) => '$' . number_format((float)$n, 2);

$unidades = array_sum(array_column($descargos, 'cantidad'));
$valor    = array_sum(array_column($descargos, 'subtotal'));
$porMotivo = array_fill_keys(array_keys($motivos), 0);
foreach ($descargos as $d) {
    $porMotivo[$d['motivo']] += (int)$d['cantidad'];
}

$hoyF = date('Y-m-d');
$atajos = [
    'Hoy'          => [$hoyF, $hoyF],
    'Este mes'     => [date('Y-m-01'), $hoyF],
    'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Descargos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .monto { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
        .stock-info { font-size: .78rem; }
        .filtro-bar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: end; }
        .filtro-bar .form-control, .filtro-bar .form-select { font-size: .82rem; }
        .atajo { font-size: .75rem; }
        .motivo-detalle-box { display: none; }
        .motivo-detalle-box.visible { display: block; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Descargos</h5>
                <p class="topbar-date mb-0">Salida de productos del inventario por vencimiento, pérdida u otros motivos</p>
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

            <!-- Registrar descargo -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-box-arrow-down text-danger me-1"></i>Registrar descargo
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($productos)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-box-seam fs-2 d-block mb-2 opacity-25"></i>
                        No hay productos con stock en esta sucursal.
                        <div class="small mt-1">Registra mercadería en <a href="<?= BASE_URL ?>/ingresos">Ingresos</a> para poder descargarla.</div>
                    </div>
                    <?php else: ?>
                    <form method="POST" action="<?= BASE_URL ?>/descargos/registrar" id="formDescargo" class="row g-3 align-items-start" autocomplete="off">
                        <div class="col-lg-5">
                            <label class="form-label fw-semibold">Producto <span class="text-danger">*</span></label>
                            <select class="form-select" name="producto_id" id="d-producto" required>
                                <option value="">Selecciona un producto con stock…</option>
                                <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id'] ?>" data-stock="<?= (int)$p['stock'] ?>" data-unidad="<?= htmlspecialchars($p['unidad'] ?: 'unidad') ?>">
                                    <?= htmlspecialchars($p['nombre']) ?><?= $p['codigo'] ? ' (' . htmlspecialchars($p['codigo']) . ')' : '' ?> — stock: <?= (int)$p['stock'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="stock-info text-muted mt-1" id="d-stock-info">&nbsp;</div>
                        </div>
                        <div class="col-sm-4 col-lg-2">
                            <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="cantidad" id="d-cantidad" min="1" step="1" value="1" required>
                        </div>
                        <div class="col-sm-8 col-lg-3">
                            <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                            <select class="form-select" name="motivo" id="d-motivo" required>
                                <option value="">Selecciona…</option>
                                <?php foreach ($motivos as $k => $m): ?>
                                <option value="<?= $k ?>"><?= $m['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 d-grid">
                            <label class="form-label d-none d-lg-block">&nbsp;</label>
                            <button class="btn btn-primary-custom" style="padding:.45rem .75rem">
                                <i class="bi bi-box-arrow-down me-1"></i>Descargar
                            </button>
                        </div>
                        <div class="col-12 motivo-detalle-box" id="d-detalle-box">
                            <label class="form-label fw-semibold">Explica el motivo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="motivo_detalle" id="d-detalle" maxlength="255"
                                   placeholder="Ej: producto dañado en el transporte, muestra para un cliente…">
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger-soft"><i class="bi bi-box-arrow-down text-danger"></i></div>
                        <div>
                            <div class="stat-value"><?= $unidades ?></div>
                            <div class="stat-label">Unidades descargadas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft"><i class="bi bi-cash-stack text-warning"></i></div>
                        <div>
                            <div class="stat-value"><?= $money($valor) ?></div>
                            <div class="stat-label">Valor a precio de compra</div>
                        </div>
                    </div>
                </div>
                <?php foreach (['vencido', 'perdido'] as $k): $m = $motivos[$k]; ?>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-<?= $m['color'] ?>-soft"><i class="bi bi-<?= $m['icon'] ?> text-<?= $m['color'] ?>"></i></div>
                        <div>
                            <div class="stat-value"><?= $porMotivo[$k] ?></div>
                            <div class="stat-label">
                                <?= $m['label'] ?>s
                                <?php if ($k === 'perdido' && $porMotivo['otros']): ?>· <?= $porMotivo['otros'] ?> por otros<?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Historial -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <form method="GET" action="<?= BASE_URL ?>/descargos" class="filtro-bar">
                        <h6 class="card-title mb-0 fw-semibold me-auto align-self-center">Historial de descargos</h6>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Desde</label>
                            <input type="date" class="form-control form-control-sm" name="desde" value="<?= $desde ?>" max="<?= $hoyF ?>">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Hasta</label>
                            <input type="date" class="form-control form-control-sm" name="hasta" value="<?= $hasta ?>" max="<?= $hoyF ?>">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Motivo</label>
                            <select class="form-select form-select-sm" name="motivo">
                                <option value="">Todos</option>
                                <?php foreach ($motivos as $k => $m): ?>
                                <option value="<?= $k ?>" <?= $motivo === $k ? 'selected' : '' ?>><?= $m['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-sm btn-light"><i class="bi bi-search"></i></button>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($atajos as $lbl => [$d1, $h1]): ?>
                            <a href="<?= BASE_URL ?>/descargos?<?= http_build_query(array_filter(['desde' => $d1, 'hasta' => $h1, 'motivo' => $motivo])) ?>"
                               class="btn btn-sm atajo <?= ($d1 === $desde && $h1 === $hasta) ? 'btn-secondary' : 'btn-light' ?>"><?= $lbl ?></a>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th>Motivo</th>
                                    <th>Registrado por</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($descargos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                        No hay descargos en este período.
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($descargos as $d): $m = $motivos[$d['motivo']]; ?>
                                <tr>
                                    <td class="ps-3 small text-nowrap">
                                        <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                                        <div class="text-muted"><?= date('H:i', strtotime($d['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($d['producto_nombre']) ?></div>
                                        <?php if ($d['codigo']): ?><div class="text-muted small"><?= htmlspecialchars($d['codigo']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="text-center fw-semibold"><?= (int)$d['cantidad'] ?> <span class="text-muted small fw-normal"><?= htmlspecialchars($d['unidad'] ?: '') ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= $m['color'] ?>-soft text-<?= $m['color'] ?>"><i class="bi bi-<?= $m['icon'] ?> me-1"></i><?= $m['label'] ?></span>
                                        <?php if ($d['motivo_detalle']): ?>
                                        <div class="text-muted small mt-1"><?= htmlspecialchars($d['motivo_detalle']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($d['usuario_nombre'] ?? '—') ?></td>
                                    <td class="text-end monto"><?= $money($d['subtotal']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger btn-accion" title="Anular y devolver al stock"
                                                onclick='anular(<?= (int)$d['id'] ?>, <?= json_encode($d['producto_nombre'], $jsonFlags) ?>, <?= (int)$d['cantidad'] ?>)'>
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if ($descargos): ?>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="2" class="ps-3 text-end">Total</td>
                                    <td class="text-center"><?= $unidades ?></td>
                                    <td colspan="2"></td>
                                    <td class="text-end monto"><?= $money($valor) ?></td>
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

<form method="POST" action="<?= BASE_URL ?>/descargos/eliminar" id="formAnular" class="d-none">
    <input type="hidden" name="id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const OLD = <?= json_encode($old, $jsonFlags) ?>;

function anular(id, producto, cant) {
    if (!confirm('¿Anular el descargo de ' + cant + ' × ' + producto + '? Las unidades volverán al inventario.')) return;
    const f = document.getElementById('formAnular');
    f.id.value = id;
    f.submit();
}

const form = document.getElementById('formDescargo');
if (form) {
    const prod    = document.getElementById('d-producto');
    const cant    = document.getElementById('d-cantidad');
    const motivo  = document.getElementById('d-motivo');
    const box     = document.getElementById('d-detalle-box');
    const detalle = document.getElementById('d-detalle');
    const info    = document.getElementById('d-stock-info');

    function actualizarStock() {
        const opt = prod.selectedOptions[0];
        const stock = opt && opt.value ? parseInt(opt.dataset.stock, 10) : 0;
        cant.max = stock || '';
        info.innerHTML = stock
            ? `<i class="bi bi-layers me-1"></i>Disponible: <strong>${stock}</strong> ${opt.dataset.unidad}`
            : '&nbsp;';
        if (stock && parseInt(cant.value, 10) > stock) cant.value = stock;
    }

    // «Otros» abre el campo para explicar el motivo
    function actualizarMotivo() {
        const otros = motivo.value === 'otros';
        box.classList.toggle('visible', otros);
        detalle.required = otros;
        if (otros) detalle.focus();
    }

    prod.addEventListener('change', actualizarStock);
    motivo.addEventListener('change', actualizarMotivo);

    form.addEventListener('submit', e => {
        const max = parseInt(cant.max, 10);
        if (max && parseInt(cant.value, 10) > max) {
            e.preventDefault();
            alert('La cantidad supera el stock disponible (' + max + ').');
            cant.focus();
        }
    });

    // Restaurar lo ingresado si el servidor rechazó el descargo
    if (OLD) {
        prod.value    = OLD.producto_id || '';
        cant.value    = OLD.cantidad || 1;
        motivo.value  = OLD.motivo || '';
        detalle.value = OLD.motivo_detalle || '';
    }
    actualizarStock();
    box.classList.toggle('visible', motivo.value === 'otros');
    detalle.required = motivo.value === 'otros';
}
</script>
</body>
</html>
