<?php
$acta = $_SESSION['descargo_acta'] ?? 0;   // acta recién registrada: se abre para imprimir
unset($_SESSION['descargo_acta']);
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
                    <form method="POST" action="<?= BASE_URL ?>/descargos/registrar" id="formDescargo" autocomplete="off">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-6">
                                <label class="form-label fw-semibold">Productos <span class="text-danger">*</span></label>
                                <div class="dsc-ac">
                                    <input type="text" class="form-control" id="d-producto-buscar" autocomplete="off"
                                           placeholder="Busca por nombre o código y agrégalo a la lista (puedes agregar varios)…">
                                    <div class="dsc-ac-lista" id="d-producto-lista"></div>
                                </div>
                                <select class="d-none" id="d-producto" tabindex="-1" aria-hidden="true">
                                    <?php foreach ($productos as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-stock="<?= (int)$p['stock'] ?>" data-unidad="<?= htmlspecialchars($p['unidad'] ?: 'unidad') ?>"
                                            data-nombre="<?= htmlspecialchars($p['nombre']) ?>" data-codigo="<?= htmlspecialchars($p['codigo'] ?? '') ?>"
                                            data-costo="<?= (float)$p['precio_compra'] ?>"></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-4 col-lg-2">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha" id="d-fecha" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-sm-4 col-lg-2">
                                <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                                <select class="form-select" name="motivo" id="d-motivo" required>
                                    <option value="">Selecciona…</option>
                                    <?php foreach ($motivos as $k => $m): ?>
                                    <option value="<?= $k ?>"><?= $m['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-4 col-lg-2 d-grid">
                                <label class="form-label d-none d-sm-block">&nbsp;</label>
                                <button class="btn btn-primary-custom" style="padding:.45rem .75rem" id="btnDescargar">
                                    <i class="bi bi-box-arrow-down me-1"></i>Descargar
                                </button>
                            </div>
                            <div class="col-12 motivo-detalle-box" id="d-detalle-box">
                                <label class="form-label fw-semibold">Explica el motivo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="motivo_detalle" id="d-detalle" maxlength="255"
                                       placeholder="Ej: producto dañado en el transporte, muestra para un cliente…">
                            </div>
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0 dsc-items">
                                        <thead class="table-light">
                                            <tr><th>Producto</th><th class="text-center" style="width:110px;">Disponible</th>
                                                <th class="text-center" style="width:130px;">Cantidad</th><th class="text-end" style="width:120px;">Costo</th><th style="width:44px;"></th></tr>
                                        </thead>
                                        <tbody id="dItems"></tbody>
                                        <tfoot>
                                            <tr id="dVacio"><td colspan="5" class="text-center text-muted small py-3">
                                                <i class="bi bi-arrow-up me-1"></i>Busca los productos arriba: cada uno se agrega a esta lista con su cantidad.
                                            </td></tr>
                                            <tr id="dTotal" class="d-none fw-semibold"><td class="text-end" colspan="2">Total</td>
                                                <td class="text-center" id="dTotalUnidades"></td><td class="text-end" id="dTotalCosto"></td><td></td></tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
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
                                        <?= date('d/m/Y', strtotime($d['fecha'])) ?>
                                        <?php if ($d['fecha'] === date('Y-m-d', strtotime($d['created_at']))): ?>
                                        <div class="text-muted"><?= date('H:i', strtotime($d['created_at'])) ?></div>
                                        <?php else: ?>
                                        <div class="text-muted" title="Fecha en que se registró en el sistema">reg. <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></div>
                                        <?php endif; ?>
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
                                    <td class="text-center text-nowrap">
                                        <a href="<?= BASE_URL ?>/descargos/acta?grupo=<?= (int)($d['grupo'] ?: $d['id']) ?>" target="_blank"
                                           class="btn btn-sm btn-outline-secondary btn-accion" title="Imprimir acta de descargo"><i class="bi bi-printer"></i></a>
                                        <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                onclick='editarDescargo(<?= json_encode(['id' => (int)$d['id'], 'fecha' => $d['fecha'], 'producto' => $d['producto_nombre'], 'cantidad' => (int)$d['cantidad'], 'motivo' => $d['motivo'], 'detalle' => (string)$d['motivo_detalle'], 'unidad' => $d['unidad'] ?: 'unidad'], $jsonFlags) ?>)'>
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
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

<!-- Editar descargo: cantidad y motivo (el producto no cambia; el stock se ajusta por la diferencia) -->
<div class="modal fade" id="modalEditarDescargo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= BASE_URL ?>/descargos/editar" class="modal-content border-0 shadow" id="formEditarDescargo">
            <input type="hidden" name="id" id="e-id">
            <input type="hidden" name="volver" value="<?= htmlspecialchars('?' . http_build_query(['desde' => $desde, 'hasta' => $hasta, 'motivo' => $_GET['motivo'] ?? ''])) ?>">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Editar descargo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="small text-muted">Producto</div>
                    <div class="fw-semibold" id="e-producto"></div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="fecha" id="e-fecha" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="cantidad" id="e-cantidad" min="1" step="1" required>
                    </div>
                    <div class="col-7">
                        <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                        <select class="form-select" name="motivo" id="e-motivo" required>
                            <?php foreach ($motivos as $k => $m): ?>
                            <option value="<?= $k ?>"><?= $m['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12" id="e-detalle-box">
                        <label class="form-label fw-semibold">Explica el motivo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="motivo_detalle" id="e-detalle" maxlength="255">
                    </div>
                </div>
                <div class="form-text mt-2" id="e-ayuda"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/descargos/eliminar" id="formAnular" class="d-none">
    <input type="hidden" name="id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .dsc-ac { position: relative; }
    .dsc-ac-lista { display: none; position: absolute; left: 0; right: 0; top: calc(100% + 2px); z-index: 1050; max-height: 280px; overflow-y: auto;
                    background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .12); }
    .dsc-ac-op { padding: .45rem .75rem; font-size: .88rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: .75rem; }
    .dsc-ac-op:hover, .dsc-ac-op.activa { background: #eef2ff; }
    .dsc-ac-op small { color: #94a3b8; margin-left: .5rem; }
    .dsc-ac-stock { color: #15803d; font-size: .78rem; font-weight: 600; white-space: nowrap; }
    .dsc-ac-vacio { padding: .55rem .75rem; font-size: .84rem; color: #94a3b8; }
</style>
<script>
const OLD = <?= json_encode($old, $jsonFlags) ?>;

// Editar: «Otros» pide explicación; el aviso indica cómo cambia el stock
let cantOriginal = 0;
function ayudaEditar() {
    const otros = document.getElementById('e-motivo').value === 'otros';
    document.getElementById('e-detalle-box').style.display = otros ? '' : 'none';
    document.getElementById('e-detalle').required = otros;
    const dif = (parseInt(document.getElementById('e-cantidad').value, 10) || 0) - cantOriginal;
    document.getElementById('e-ayuda').textContent = dif > 0 ? 'Se descontarán ' + dif + ' unidad(es) más del inventario.'
        : dif < 0 ? 'Volverán ' + (-dif) + ' unidad(es) al inventario.' : '';
}
function editarDescargo(d) {
    cantOriginal = d.cantidad;
    document.getElementById('e-id').value = d.id;
    document.getElementById('e-fecha').value = d.fecha;
    document.getElementById('e-producto').textContent = d.producto;
    document.getElementById('e-cantidad').value = d.cantidad;
    document.getElementById('e-motivo').value = d.motivo;
    document.getElementById('e-detalle').value = d.detalle;
    ayudaEditar();
    new bootstrap.Modal(document.getElementById('modalEditarDescargo')).show();
}
document.getElementById('e-motivo').addEventListener('change', ayudaEditar);
document.getElementById('e-cantidad').addEventListener('input', ayudaEditar);

function anular(id, producto, cant) {
    if (!confirm('¿Anular el descargo de ' + cant + ' × ' + producto + '? Las unidades volverán al inventario.')) return;
    const f = document.getElementById('formAnular');
    f.id.value = id;
    f.submit();
}

const form = document.getElementById('formDescargo');
if (form) {
    const prod    = document.getElementById('d-producto');
    const motivo  = document.getElementById('d-motivo');
    const box     = document.getElementById('d-detalle-box');
    const detalle = document.getElementById('d-detalle');
    const tbody   = document.getElementById('dItems');
    const dinero  = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // «Otros» abre el campo para explicar el motivo
    function actualizarMotivo() {
        const otros = motivo.value === 'otros';
        box.classList.toggle('visible', otros);
        detalle.required = otros;
        if (otros) detalle.focus();
    }
    motivo.addEventListener('change', actualizarMotivo);

    const opciones = [...prod.options].map(o => ({
        id: o.value, nombre: o.dataset.nombre, codigo: o.dataset.codigo || '', stock: parseInt(o.dataset.stock, 10),
        unidad: o.dataset.unidad, costo: parseFloat(o.dataset.costo) || 0,
    }));
    const escAc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    // ── Lista de productos a descargar ──
    function totales() {
        const filas = [...tbody.rows];
        document.getElementById('dVacio').classList.toggle('d-none', filas.length > 0);
        document.getElementById('dTotal').classList.toggle('d-none', filas.length === 0);
        let u = 0, c = 0;
        filas.forEach(tr => {
            const cant = parseInt(tr.querySelector('.d-cant').value, 10) || 0;
            u += cant; c += cant * parseFloat(tr.dataset.costo);
            tr.querySelector('.d-costo').textContent = dinero(cant * parseFloat(tr.dataset.costo));
        });
        document.getElementById('dTotalUnidades').textContent = u;
        document.getElementById('dTotalCosto').textContent = dinero(c);
        document.getElementById('btnDescargar').innerHTML = '<i class="bi bi-box-arrow-down me-1"></i>Descargar' + (filas.length > 1 ? ' (' + filas.length + ')' : '');
    }
    function agregar(id, cantidad = 1) {
        const p = opciones.find(o => o.id === String(id));
        if (!p) return;
        const existe = tbody.querySelector(`tr[data-id="${p.id}"]`);
        if (existe) {
            const inp = existe.querySelector('.d-cant');
            inp.value = Math.min(p.stock, (parseInt(inp.value, 10) || 0) + cantidad);
            inp.focus(); inp.select(); totales();
            return;
        }
        const tr = document.createElement('tr');
        tr.dataset.id = p.id; tr.dataset.costo = p.costo;
        tr.innerHTML = `
            <td><input type="hidden" name="producto_id[]" value="${p.id}">
                <div class="fw-semibold">${escAc(p.nombre)}</div>${p.codigo ? `<div class="text-muted small">${escAc(p.codigo)}</div>` : ''}</td>
            <td class="text-center small text-muted">${p.stock} ${escAc(p.unidad)}</td>
            <td><input type="number" class="form-control form-control-sm text-center d-cant" name="cantidad[]" min="1" max="${p.stock}" step="1" value="${Math.min(cantidad, p.stock)}" required></td>
            <td class="text-end small d-costo"></td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger p-0" title="Quitar"><i class="bi bi-x-lg"></i></button></td>`;
        tr.querySelector('button').addEventListener('click', () => { tr.remove(); totales(); });
        tr.querySelector('.d-cant').addEventListener('input', totales);
        tbody.appendChild(tr);
        totales();
        const inp = tr.querySelector('.d-cant'); inp.focus(); inp.select();
    }

    // ── Buscador predictivo: cada producto elegido se agrega a la lista ──
    const buscar = document.getElementById('d-producto-buscar');
    const lista  = document.getElementById('d-producto-lista');
    let activo = -1;
    function elegirProducto(id) {
        buscar.value = '';
        lista.style.display = 'none';
        agregar(id);
    }
    function sugerir() {
        const q = buscar.value.trim().toLowerCase();
        activo = -1;
        const res = opciones.filter(o => !q || o.nombre.toLowerCase().includes(q) || o.codigo.toLowerCase().includes(q)).slice(0, 10);
        lista.innerHTML = res.length
            ? res.map(o => `<div class="dsc-ac-op" data-id="${o.id}">
                    <span>${escAc(o.nombre)}${o.codigo ? `<small>${escAc(o.codigo)}</small>` : ''}</span>
                    <span class="dsc-ac-stock">${tbody.querySelector(`tr[data-id="${o.id}"]`) ? '✓ en la lista · ' : ''}${o.stock} ${escAc(o.unidad)}</span>
               </div>`).join('')
            : '<div class="dsc-ac-vacio">Sin productos con stock que coincidan</div>';
        lista.style.display = 'block';
    }
    buscar.addEventListener('input', sugerir);
    buscar.addEventListener('focus', sugerir);
    buscar.addEventListener('keydown', e => {
        const ops = [...lista.querySelectorAll('.dsc-ac-op')];
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (lista.style.display !== 'block') { sugerir(); return; }
            if (!ops.length) return;
            activo = (activo + (e.key === 'ArrowDown' ? 1 : -1) + ops.length) % ops.length;
            ops.forEach((o, i) => o.classList.toggle('activa', i === activo));
            ops[activo].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (lista.style.display !== 'block') return;
            const op = ops[activo] || ops[0];
            if (op) elegirProducto(op.dataset.id);
        } else if (e.key === 'Escape') {
            lista.style.display = 'none';
        }
    });
    lista.addEventListener('mousedown', e => {
        const op = e.target.closest('.dsc-ac-op');
        if (op) { e.preventDefault(); elegirProducto(op.dataset.id); }
    });
    buscar.addEventListener('blur', () => setTimeout(() => { lista.style.display = 'none'; }, 120));

    form.addEventListener('submit', e => {
        if (!tbody.rows.length) {
            e.preventDefault();
            alert('Agrega al menos un producto a la lista.');
            buscar.focus();
            return;
        }
        const pasado = [...tbody.rows].find(tr => {
            const inp = tr.querySelector('.d-cant');
            return (parseInt(inp.value, 10) || 0) > parseInt(inp.max, 10);
        });
        if (pasado) {
            e.preventDefault();
            alert('La cantidad de «' + pasado.querySelector('.fw-semibold').textContent + '» supera el stock disponible.');
            pasado.querySelector('.d-cant').focus();
        }
    });

    // Restaurar lo ingresado si el servidor rechazó el descargo
    if (OLD) {
        (OLD.producto_id || []).forEach((id, i) => agregar(id, parseInt((OLD.cantidad || [])[i], 10) || 1));
        if (OLD.fecha) document.getElementById('d-fecha').value = OLD.fecha;
        motivo.value  = OLD.motivo || '';
        detalle.value = OLD.motivo_detalle || '';
    }
    totales();
    box.classList.toggle('visible', motivo.value === 'otros');
    detalle.required = motivo.value === 'otros';
}

<?php if ($acta): ?>
// Recién registrado: se abre el acta de descargo para imprimir
window.open('<?= BASE_URL ?>/descargos/acta?grupo=<?= (int)$acta ?>', '_blank');
<?php endif; ?>
</script>
</body>
</html>
