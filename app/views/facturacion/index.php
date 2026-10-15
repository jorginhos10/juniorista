<?php
$money = fn($n) => '$' . number_format((float)$n, 2);
$totalGeneral = $totalesTipo['venta']['total'] + $totalesTipo['peluqueria']['total'] + $totalesTipo['laboratorio']['total'] + $totalesTipo['tratamiento']['total'];
$cantGeneral  = $totalesTipo['venta']['cantidad'] + $totalesTipo['peluqueria']['cantidad'] + $totalesTipo['laboratorio']['cantidad'] + $totalesTipo['tratamiento']['cantidad'];
$totalTabla   = array_sum(array_map(fn($m) => $m['anulada'] ? 0 : (float)$m['monto'], $movimientos));

$hoyF   = date('Y-m-d');
$atajos = [
    'Hoy'          => [$hoyF, $hoyF],
    'Este mes'     => [date('Y-m-01'), $hoyF],
    'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'Este año'     => [date('Y-01-01'), $hoyF],
];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$url = fn(array $cambios) => BASE_URL . '/facturacion?' . http_build_query(array_filter(array_merge(
    ['desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo], $cambios
)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Facturación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .tipo-card { display: block; text-decoration: none; color: inherit; height: 100%; }
        .tipo-card .stat-card { height: 100%; border: 2px solid transparent; transition: border-color .15s; }
        .tipo-card.activo .stat-card { border-color: var(--c); }
        .tipo-card:hover { color: inherit; }
        .tipo-ico {
            width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
            color: var(--c); background: color-mix(in srgb, var(--c) 14%, #fff);
        }
        .stat-sub { font-size: .72rem; color: #94a3b8; }

        .seg { display: inline-flex; background: #f1f5f9; border-radius: 10px; padding: 3px; flex-wrap: wrap; }
        .seg a {
            padding: .35rem .8rem; border-radius: 8px; font-size: .82rem; font-weight: 600;
            color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: .35rem;
        }
        .seg a i { color: var(--c, #475569); }
        .seg a.activo { background: #fff; color: #1e293b; box-shadow: 0 1px 3px rgba(15, 23, 42, .1); }
        .filtro-bar { display: flex; flex-wrap: wrap; gap: .75rem 1.5rem; align-items: end; justify-content: space-between; }
        .filtro-fechas { display: flex; flex-wrap: wrap; gap: .5rem; align-items: end; }
        .filtro-bar .form-control { font-size: .82rem; }
        .atajo { font-size: .75rem; white-space: nowrap; }

        .tipo-badge {
            font-size: .7rem; font-weight: 700; padding: .25rem .55rem; border-radius: 6px; white-space: nowrap;
            color: var(--c); background: color-mix(in srgb, var(--c) 12%, #fff);
        }
        .comprobante { font-family: ui-monospace, Consolas, monospace; font-size: .78rem; color: #475569; white-space: nowrap; }
        .monto { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
        tr.anulada td { color: #94a3b8; }
        tr.anulada .monto { text-decoration: line-through; }
        .detalle { max-width: 360px; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Facturación</h5>
                <p class="topbar-date mb-0">Todo lo cobrado: ventas, peluquería, laboratorios y abonos de tratamientos</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <a href="<?= BASE_URL ?>/facturacion/exportar?<?= http_build_query(array_filter(['desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo])) ?>"
                   class="btn btn-primary-custom btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar a Excel
                </a>
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

            <!-- Totales (cada tarjeta filtra por su tipo) -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl">
                    <a href="<?= $url(['tipo' => '']) ?>" class="tipo-card <?= $tipo === '' ? 'activo' : '' ?>" style="--c:#16a34a">
                        <div class="stat-card">
                            <div class="tipo-ico"><i class="bi bi-receipt-cutoff"></i></div>
                            <div>
                                <div class="stat-value"><?= $money($totalGeneral) ?></div>
                                <div class="stat-label">Total facturado</div>
                                <div class="stat-sub"><?= $cantGeneral ?> movimientos</div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php foreach ($tipos as $k => $t): ?>
                <div class="col-6 col-md-4 col-xl">
                    <a href="<?= $url(['tipo' => $k]) ?>" class="tipo-card <?= $tipo === $k ? 'activo' : '' ?>" style="--c:<?= $t['color'] ?>">
                        <div class="stat-card">
                            <div class="tipo-ico"><i class="bi bi-<?= $t['icon'] ?>"></i></div>
                            <div>
                                <div class="stat-value"><?= $money($totalesTipo[$k]['total']) ?></div>
                                <div class="stat-label"><?= $k === 'venta' ? 'Ventas' : ($k === 'tratamiento' ? 'Tratamientos (abonos)' : ($k === 'laboratorio' ? 'Laboratorios' : $t['label'])) ?></div>
                                <div class="stat-sub">
                                    <?= $totalesTipo[$k]['cantidad'] ?> registros
                                    <?php if ($totalGeneral > 0): ?>· <?= round($totalesTipo[$k]['total'] / $totalGeneral * 100) ?>%<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Filtros -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" action="<?= BASE_URL ?>/facturacion" class="filtro-bar">
                        <!-- Dos bloques: a la izquierda el tipo, a la derecha las fechas -->
                        <div class="filtro-tipo">
                            <label class="form-label small fw-semibold mb-1 d-block">Tipo</label>
                            <div class="seg">
                                <a href="<?= $url(['tipo' => '']) ?>" class="<?= $tipo === '' ? 'activo' : '' ?>">Todos</a>
                                <?php foreach ($tipos as $k => $t): ?>
                                <a href="<?= $url(['tipo' => $k]) ?>" class="<?= $tipo === $k ? 'activo' : '' ?>" style="--c:<?= $t['color'] ?>">
                                    <i class="bi bi-<?= $t['icon'] ?>"></i><?= $k === 'venta' ? 'Ventas' : ($k === 'tratamiento' ? 'Tratamientos (abonos)' : ($k === 'laboratorio' ? 'Laboratorios' : $t['label'])) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="hidden" name="tipo" value="<?= $tipo ?>">
                        <div class="filtro-fechas">
                        <div>
                            <label class="form-label small fw-semibold mb-1">Desde</label>
                            <input type="date" class="form-control form-control-sm" name="desde" value="<?= $desde ?>" max="<?= $hoyF ?>">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Hasta</label>
                            <input type="date" class="form-control form-control-sm" name="hasta" value="<?= $hasta ?>" max="<?= $hoyF ?>">
                        </div>
                        <button class="btn btn-sm btn-light text-nowrap"><i class="bi bi-search me-1"></i>Filtrar</button>
                        <div class="d-flex gap-1">
                            <?php foreach ($atajos as $lbl => [$d, $h]): ?>
                            <a href="<?= $url(['desde' => $d, 'hasta' => $h]) ?>"
                               class="btn btn-sm atajo <?= ($d === $desde && $h === $hasta) ? 'btn-secondary' : 'btn-light' ?>"><?= $lbl ?></a>
                            <?php endforeach; ?>
                        </div>
                        </div><!-- /filtro-fechas -->
                    </form>
                </div>
            </div>

            <!-- Listado -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold">
                        <?= $tipo === '' ? 'Todos los movimientos' : ($tipo === 'venta' ? 'Ventas' : ($tipo === 'tratamiento' ? 'Abonos de tratamientos' : ($tipo === 'laboratorio' ? 'Laboratorios' : 'Peluquería'))) ?>
                        <span class="text-muted fw-normal small">· <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?></span>
                    </h6>
                    <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar cliente, mascota, detalle…" style="width:240px">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Tipo</th>
                                    <th>Comprobante</th>
                                    <th>Detalle</th>
                                    <th>Cliente / mascota</th>
                                    <th>Registrado por</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-center pe-3" style="width:130px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movimientos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-receipt fs-2 d-block mb-2 opacity-25"></i>
                                        No hay movimientos en este período.
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($movimientos as $m): $t = $tipos[$m['tipo']]; ?>
                                <tr class="<?= $m['anulada'] ? 'anulada' : '' ?>" data-monto="<?= $m['anulada'] ? 0 : (float)$m['monto'] ?>">
                                    <td class="ps-3 small text-nowrap">
                                        <?= date('d/m/Y', strtotime($m['fecha'])) ?>
                                        <div class="text-muted"><?= date('H:i', strtotime($m['creado'])) ?></div>
                                    </td>
                                    <td><span class="tipo-badge" style="--c:<?= $t['color'] ?>"><i class="bi bi-<?= $t['icon'] ?> me-1"></i><?= $t['label'] ?></span></td>
                                    <td class="comprobante"><?= htmlspecialchars(FacturacionController::comprobante($m, $sucursal)) ?></td>
                                    <td class="detalle small">
                                        <?= htmlspecialchars($m['detalle'] ?: '—') ?>
                                        <?php if ($m['anulada']): ?><span class="badge bg-danger-soft text-danger ms-1">Anulada</span><?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if ($m['mascota']): ?>
                                        <?= $m['especie'] === 'gato' ? '🐱' : '🐶' ?> <span class="fw-semibold"><?= htmlspecialchars($m['mascota']) ?></span>
                                        <?php elseif ($m['cliente']): ?>
                                        <i class="bi bi-person text-muted me-1"></i><?= htmlspecialchars($m['cliente']) ?>
                                        <?php else: ?>
                                        <span class="text-muted">Cliente general</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($m['usuario_nombre'] ?? '—') ?></td>
                                    <td class="text-end monto"><?= $money($m['monto']) ?></td>
                                    <td class="text-center pe-3">
                                        <?php
                                            $puedeEditar   = !$m['anulada'] && !$m['tratamiento_cancelado'];
                                            $puedeCancelar = !$m['anulada'] && ($m['tipo'] !== 'tratamiento' || $esAdmin);
                                            $motivoEditar  = $m['anulada'] ? 'Movimiento anulado'
                                                : 'El tratamiento está cancelado';
                                            $motivoCancel  = $m['anulada'] ? 'Ya está anulado' : 'Solo un administrador puede anular abonos';
                                        ?>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="<?= BASE_URL ?>/facturacion/imprimir?tipo=<?= $m['tipo'] ?>&id=<?= $m['id'] ?>" target="_blank"
                                               class="btn btn-sm btn-outline-secondary btn-accion" title="Imprimir factura">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <span title="<?= $puedeEditar ? 'Editar' : $motivoEditar ?>">
                                                <button class="btn btn-sm btn-outline-primary btn-accion" <?= $puedeEditar ? '' : 'disabled' ?>
                                                        onclick='editarMov(<?= json_encode($m + ['comprobante' => FacturacionController::comprobante($m, $sucursal)], $jsonFlags) ?>)'>
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                            </span>
                                            <span title="<?= $puedeCancelar ? 'Cancelar' : $motivoCancel ?>">
                                                <button class="btn btn-sm btn-outline-danger btn-accion" <?= $puedeCancelar ? '' : 'disabled' ?>
                                                        onclick='cancelarMov(<?= json_encode(['tipo' => $m['tipo'], 'id' => $m['id'], 'comprobante' => FacturacionController::comprobante($m, $sucursal), 'monto' => $m['monto']], $jsonFlags) ?>)'>
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if ($movimientos): ?>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="6" class="ps-3 text-end">
                                        Total <span class="text-muted fw-normal small">(<span id="contadorVisible"><?= count($movimientos) ?></span> movimientos<?= $totalesTipo['anuladas']['cantidad'] && $tipo !== 'peluqueria' && $tipo !== 'tratamiento' ? ', sin contar anuladas' : '' ?>)</span>
                                    </td>
                                    <td class="text-end monto text-success" id="totalVisible"><?= $money($totalTabla) ?></td>
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

<!-- ── MODAL EDITAR MOVIMIENTO ───────────────────────── -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/facturacion/editar" autocomplete="off" id="formEditar">
                <input type="hidden" name="tipo" id="ed-tipo">
                <input type="hidden" name="id" id="ed-id">
                <input type="hidden" name="f_desde" value="<?= $desde ?>">
                <input type="hidden" name="f_hasta" value="<?= $hasta ?>">
                <input type="hidden" name="f_tipo" value="<?= $tipo ?>">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i><span id="ed-titulo">Editar</span></h5>
                        <div class="text-muted small comprobante" id="ed-comprobante"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <!-- Peluquería -->
                    <div class="row g-3 js-campos" data-tipo="servicio">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Mascota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="ed-nombre" maxlength="100">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Especie</label>
                            <select class="form-select" name="especie" id="ed-especie">
                                <option value="perro">Perro</option>
                                <option value="gato">Gato</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Servicio <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="descripcion" id="ed-descripcion" maxlength="255">
                        </div>
                    </div>
                    <!-- Abono -->
                    <div class="js-campos" data-tipo="tratamiento">
                        <div class="small text-muted mb-2" id="ed-tratamiento"></div>
                    </div>
                    <div class="row g-3 mt-0">
                        <div class="col-6">
                            <label class="form-label fw-semibold" id="ed-lbl-monto">Valor <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="monto" id="ed-monto" step="0.01" min="0" required>
                            </div>
                            <div class="form-text" id="ed-max"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Fecha</label>
                            <input type="date" class="form-control" name="fecha" id="ed-fecha" max="<?= $hoy ?>" required>
                        </div>
                        <div class="col-12 js-campos" data-tipo="tratamiento">
                            <label class="form-label fw-semibold">Descripción del abono</label>
                            <input type="text" class="form-control" name="nota" id="ed-nota" maxlength="150">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL CANCELAR MOVIMIENTO ─────────────────────── -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/facturacion/cancelar">
                <input type="hidden" name="tipo" id="ca-tipo">
                <input type="hidden" name="id" id="ca-id">
                <input type="hidden" name="f_desde" value="<?= $desde ?>">
                <input type="hidden" name="f_hasta" value="<?= $hasta ?>">
                <input type="hidden" name="f_tipo" value="<?= $tipo ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-x-circle text-danger me-2"></i>Cancelar movimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="ca-texto"></p>
                    <div class="alert alert-warning py-2 small mb-0" id="ca-efecto"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn btn-danger">Sí, cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE  = '<?= BASE_URL ?>';
const money = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const $id   = id => document.getElementById(id);

async function editarMov(m) {
    // Las ventas se editan en el punto de venta (productos, cantidades, cliente, fecha y pago)
    if (m.tipo === 'venta') { location.href = BASE + '/ventas?editar=' + m.id; return; }
    $id('ed-tipo').value  = m.tipo;
    $id('ed-id').value    = m.id;
    $id('ed-monto').value = Number(m.monto).toFixed(2);
    $id('ed-fecha').value = m.fecha;
    $id('ed-fecha').removeAttribute('min');
    $id('ed-max').textContent = '';
    $id('ed-monto').removeAttribute('max');
    const esServicio = m.tipo === 'peluqueria' || m.tipo === 'laboratorio';   // recaudo directo: mismos campos
    $id('ed-comprobante').textContent = m.comprobante;
    document.querySelectorAll('.js-campos').forEach(el => el.style.display = el.dataset.tipo === (esServicio ? 'servicio' : m.tipo) ? '' : 'none');

    if (esServicio) {
        $id('ed-titulo').textContent   = m.tipo === 'laboratorio' ? 'Editar examen de laboratorio' : 'Editar servicio de peluquería';
        $id('ed-lbl-monto').innerHTML  = 'Valor <span class="text-danger">*</span>';
        $id('ed-nombre').value         = m.mascota || '';
        $id('ed-especie').value        = m.especie || 'perro';
        $id('ed-descripcion').value    = m.nota || '';
    } else {
        $id('ed-titulo').textContent   = 'Editar abono de tratamiento';
        $id('ed-lbl-monto').innerHTML  = 'Monto del abono <span class="text-danger">*</span>';
        $id('ed-nota').value           = m.nota || '';
        $id('ed-tratamiento').textContent = (m.especie === 'gato' ? '🐱 ' : '🐶 ') + (m.mascota || '') + ' · cargando saldo…';
        // Máximo permitido = saldo actual del tratamiento + lo que ya vale este abono
        try {
            const r = await fetch(BASE + '/tratamiento/abonos?id=' + m.ref_id);
            const d = await r.json();
            const max = Number(d.tratamiento.saldo) + Number(m.monto);
            $id('ed-monto').max = max.toFixed(2);
            $id('ed-max').textContent = 'Máximo ' + money(max) + ' (valor del tratamiento ' + money(d.tratamiento.valor) + ').';
            $id('ed-fecha').min = d.tratamiento.fecha;
            $id('ed-tratamiento').textContent = (m.especie === 'gato' ? '🐱 ' : '🐶 ') + d.tratamiento.nombre + ' · ' + d.tratamiento.descripcion;
        } catch { $id('ed-tratamiento').textContent = ''; }
    }
    bootstrap.Modal.getOrCreateInstance($id('modalEditar')).show();
}

const EFECTOS = {
    venta:       'La venta queda anulada y el stock de sus productos vuelve al inventario.',
    peluqueria:  'El servicio queda anulado y deja de sumar en los cierres.',
    laboratorio: 'El examen queda anulado y deja de sumar en los cierres.',
    tratamiento: 'El abono queda anulado, deja de sumar en los cierres y el saldo del tratamiento vuelve a quedar pendiente.',
};
function cancelarMov(m) {
    $id('ca-tipo').value = m.tipo;
    $id('ca-id').value   = m.id;
    $id('ca-texto').innerHTML    = '¿Cancelar <strong>' + m.comprobante + '</strong> por <strong>' + money(m.monto) + '</strong>?';
    $id('ca-efecto').textContent = EFECTOS[m.tipo] + ' Seguirá apareciendo en la lista como «Anulada».';
    bootstrap.Modal.getOrCreateInstance($id('modalCancelar')).show();
}

document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let visibles = 0, total = 0;
    document.querySelectorAll('#tabla tbody tr[data-monto]').forEach(tr => {
        const ok = tr.textContent.toLowerCase().includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) { visibles++; total += parseFloat(tr.dataset.monto); }
    });
    const c = document.getElementById('contadorVisible'), t = document.getElementById('totalVisible');
    if (c) c.textContent = visibles;
    if (t) t.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});
</script>
</body>
</html>
