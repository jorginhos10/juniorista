<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Ventas por Unidad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .rpt-filter {
            background:#fff; border:1px solid #e8eaf0; border-radius:1rem;
            padding:1.25rem 1.5rem; display:flex; align-items:flex-end;
            gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;
        }
        .rpt-filter label { font-weight:600; font-size:.82rem; color:#374151; margin-bottom:.3rem; display:block; }
        .serial-badge {
            font-family:monospace; font-size:.72rem; font-weight:700;
            background:#f5f3ff; color:#4f46e5; border:1px solid #c7d2fe;
            border-radius:.3rem; padding:.1rem .4rem; white-space:nowrap;
        }
        .producto-selected {
            background:#f0fdf4; border:1px solid #bbf7d0; border-radius:.5rem;
            padding:.4rem .85rem; font-size:.82rem; font-weight:600;
            color:#15803d; display:flex; align-items:center; gap:.5rem;
        }
        .sugerencias-drop {
            position:absolute; top:100%; left:0; right:0; z-index:1050;
            background:#fff; border:1px solid #e8eaf0; border-radius:.5rem;
            box-shadow:0 8px 24px rgba(0,0,0,.1);
            max-height:220px; overflow-y:auto; display:none;
        }
        .sug-item {
            padding:.55rem 1rem; cursor:pointer; font-size:.83rem;
            border-bottom:1px solid #f3f4f6;
        }
        .sug-item:last-child { border-bottom:none; }
        .sug-item:hover { background:#f5f3ff; }
        .sug-item .sug-cod { font-size:.72rem; color:#9ca3af; }
        .dia-badge {
            font-size:.7rem; font-weight:700; text-transform:uppercase;
            background:#f0f4ff; color:#4f46e5; border-radius:.3rem;
            padding:.1rem .4rem;
        }
    </style>
</head>
<body class="dashboard-body">
<div class="app-layout">
    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>
    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Ventas por Unidad</h5>
                <p class="topbar-date mb-0">
                    <?php if ($producto_id > 0): ?>
                        <span class="text-success fw-semibold"><?= htmlspecialchars($producto_nombre) ?></span> &middot;
                    <?php endif; ?>
                    <?= date('d/m/Y', strtotime($desde)) ?>
                    <?= $desde !== $hasta ? ' → ' . date('d/m/Y', strtotime($hasta)) : '' ?>
                </p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><div class="dropdown-header">
                            <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($usuario['email']) ?></div>
                        </div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"
                               onclick="return confirm('¿Cerrar sesión?')">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="main-content">

            <!-- Filtro -->
            <form method="GET" action="<?= BASE_URL ?>/reportes/unidades" id="formFiltro" class="rpt-filter">
                <input type="hidden" name="producto_id" id="inp-producto-id" value="<?= $producto_id ?>">

                <div>
                    <label>Desde</label>
                    <input type="date" class="form-control form-control-sm" name="desde"
                           value="<?= $desde ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label>Hasta</label>
                    <input type="date" class="form-control form-control-sm" name="hasta"
                           value="<?= $hasta ?>" max="<?= date('Y-m-d') ?>">
                </div>

                <!-- Autocomplete producto -->
                <div style="min-width:240px; position:relative;">
                    <label>Producto</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-box-seam"></i></span>
                        <input type="text" class="form-control" id="inp-producto-txt"
                               placeholder="Buscar producto…" autocomplete="off"
                               value="<?= htmlspecialchars($producto_nombre) ?>">
                        <?php if ($producto_id > 0): ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2"
                                id="btn-limpiar-prod" onclick="limpiarProducto()">
                            <i class="bi bi-x"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="sugerencias-drop" id="prodSugerencias"></div>
                </div>

                <button type="submit" class="btn btn-primary-custom btn-sm">
                    <i class="bi bi-search me-1"></i> Consultar
                </button>
                <a href="<?= BASE_URL ?>/reportes/unidades" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Hoy
                </a>
                <a href="<?= BASE_URL ?>/reportes/unidades/imprimir?desde=<?= $desde ?>&hasta=<?= $hasta ?>&producto_id=<?= $producto_id ?>"
                   target="_blank" class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="bi bi-printer me-1"></i> Imprimir / PDF
                </a>
            </form>

            <!-- Stats -->
            <?php require_once ROOT . '/app/models/VeterinariaModel.php'; ?>
            <?php $iniciales = VeterinariaModel::generarIniciales($sucursal_nombre); ?>
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft"><i class="bi bi-boxes text-primary"></i></div>
                        <div>
                            <div class="stat-value"><?= number_format($resumen['total_unidades'] ?? 0) ?></div>
                            <div class="stat-label">Unidades vendidas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft"><i class="bi bi-bag-check-fill text-success"></i></div>
                        <div>
                            <div class="stat-value"><?= $resumen['total_ventas'] ?? 0 ?></div>
                            <div class="stat-label">Ventas involucradas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft"><i class="bi bi-box-seam-fill text-warning"></i></div>
                        <div>
                            <div class="stat-value"><?= $resumen['productos_distintos'] ?? 0 ?></div>
                            <div class="stat-label">Productos distintos</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft"><i class="bi bi-currency-dollar text-info"></i></div>
                        <div>
                            <div class="stat-value">$<?= number_format($resumen['total_ingresos'] ?? 0, 2) ?></div>
                            <div class="stat-label">Total en ventas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card border-0 shadow-sm" style="border-radius:.875rem;">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-list-ul text-primary"></i>
                        <?php if ($producto_id > 0): ?>
                            Ventas de <span class="text-success ms-1"><?= htmlspecialchars($producto_nombre) ?></span>
                        <?php else: ?>
                            Detalle por unidad
                        <?php endif; ?>
                        <span class="badge bg-primary-soft text-primary fw-semibold" style="font-size:.75rem;">
                            <?= count($lineas) ?> líneas
                        </span>
                    </h6>
                    <span class="text-muted small">
                        Prefijo: <span class="serial-badge"><?= $iniciales ?></span>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Radicado</th>
                                    <?php if (!$producto_id): ?><th>Producto</th><?php endif; ?>
                                    <th class="text-center">Día</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Hora</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">P. Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                    <th>Vendedor</th>
                                    <th>Cliente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lineas)): ?>
                                <tr>
                                    <td colspan="<?= $producto_id ? 9 : 10 ?>" class="text-center text-muted py-5">
                                        <i class="bi bi-boxes fs-2 d-block mb-2 opacity-25"></i>
                                        <?= $producto_id
                                            ? 'No hay ventas de este producto en el período.'
                                            : 'Selecciona un producto y consulta, o amplía el rango de fechas.' ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php
                                $diasES = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes',
                                           'Wednesday'=>'Miércoles','Thursday'=>'Jueves',
                                           'Friday'=>'Viernes','Saturday'=>'Sábado'];
                                foreach ($lineas as $l):
                                    $serial   = VeterinariaModel::serialVenta($sucursal_nombre, $l['venta_id'], $l['created_at']);
                                    $subtotal = ($l['cantidad'] * $l['precio_unitario']) - ($l['desc_linea'] ?? 0);
                                    $dia      = $diasES[$l['dia_semana']] ?? $l['dia_semana'];
                                ?>
                                <tr>
                                    <td class="ps-3"><span class="serial-badge"><?= $serial ?></span></td>
                                    <?php if (!$producto_id): ?>
                                    <td>
                                        <div class="fw-semibold small"><?= htmlspecialchars($l['producto_nombre']) ?></div>
                                        <?php if ($l['producto_codigo']): ?>
                                        <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($l['producto_codigo']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td class="text-center"><span class="dia-badge"><?= $dia ?></span></td>
                                    <td class="text-center text-muted small"><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
                                    <td class="text-center text-muted small"><?= date('H:i', strtotime($l['created_at'])) ?></td>
                                    <td class="text-center fw-bold"><?= $l['cantidad'] ?> <span class="text-muted fw-normal" style="font-size:.72rem;"><?= htmlspecialchars($l['unidad']) ?></span></td>
                                    <td class="text-end small">$<?= number_format($l['precio_unitario'], 2) ?></td>
                                    <td class="text-end fw-bold">$<?= number_format($subtotal, 2) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="cliente-avatar" style="width:24px;height:24px;font-size:.65rem;background:var(--primary-color);">
                                                <?= strtoupper(substr($l['vendedor_nombre'] ?? 'S', 0, 1)) ?>
                                            </div>
                                            <span class="small"><?= htmlspecialchars($l['vendedor_nombre'] ?? 'Sistema') ?></span>
                                        </div>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars(trim($l['cliente_nombre']) ?: 'General') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($lineas)): ?>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="<?= $producto_id ? 4 : 5 ?>" class="ps-3 fw-bold">Total</td>
                                    <td class="text-center fw-bold"><?= number_format($resumen['total_unidades'] ?? 0) ?></td>
                                    <td></td>
                                    <td class="text-end fw-bold text-success">$<?= number_format($resumen['total_ingresos'] ?? 0, 2) ?></td>
                                    <td colspan="2"></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const _productos = <?= json_encode(array_map(fn($p) => [
    'id'     => $p['id'],
    'nombre' => $p['nombre'],
    'codigo' => $p['codigo'] ?? '',
], $productosVendidos)) ?>;

const _inpTxt  = document.getElementById('inp-producto-txt');
const _inpId   = document.getElementById('inp-producto-id');
const _sugs    = document.getElementById('prodSugerencias');

function renderProductos(lista) {
    _sugs.innerHTML = '';
    const base = [{id:0, nombre:'— Ver todos —', codigo:''}].concat(lista);
    base.forEach(p => {
        const div = document.createElement('div');
        div.className = 'sug-item';
        div.innerHTML = p.nombre + (p.codigo ? ' <span class="sug-cod">(' + p.codigo + ')</span>' : '');
        div.addEventListener('mousedown', e => {
            e.preventDefault();
            _inpId.value  = p.id;
            _inpTxt.value = p.id ? p.nombre : '';
            _inpTxt.placeholder = p.id ? '' : 'Buscar producto…';
            _sugs.style.display = 'none';
        });
        _sugs.appendChild(div);
    });
    _sugs.style.display = 'block';
}

_inpTxt.addEventListener('focus', function() {
    const q = this.value.toLowerCase();
    renderProductos(q ? _productos.filter(p => p.nombre.toLowerCase().includes(q) || (p.codigo||'').toLowerCase().includes(q)) : _productos);
});

_inpTxt.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    if (!q) { _inpId.value = 0; }
    renderProductos(q ? _productos.filter(p => p.nombre.toLowerCase().includes(q) || (p.codigo||'').toLowerCase().includes(q)) : _productos);
});

_inpTxt.addEventListener('blur', () => setTimeout(() => { _sugs.style.display = 'none'; }, 150));

function limpiarProducto() {
    _inpId.value  = 0;
    _inpTxt.value = '';
    _inpTxt.placeholder = 'Buscar producto…';
}
</script>
</body>
</html>
