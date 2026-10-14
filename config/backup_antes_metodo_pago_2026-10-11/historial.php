<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Historial de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Historial de Ventas</h5>
                <p class="topbar-date mb-0"><?= date('l, d \d\e F \d\e Y') ?></p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <a href="<?= BASE_URL ?>/ventas" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Ventas
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
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"
                               onclick="return confirm('¿Cerrar sesión?')">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="main-content">

            <!-- Flash -->
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

            <!-- Stats rápidas -->
            <?php
            $totalVentas   = count(array_filter($ventas, fn($v) => $v['estado'] !== 'anulada'));
            $totalAnuladas = count(array_filter($ventas, fn($v) => $v['estado'] === 'anulada'));
            $totalIngresos = array_sum(array_map(fn($v) => $v['estado'] !== 'anulada' ? $v['total'] : 0, $ventas));
            $totalUnidades = array_sum(array_map(fn($v) => $v['estado'] !== 'anulada' ? $v['total_unidades'] : 0, $ventas));
            ?>
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-bag-check-fill text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totalVentas ?></div>
                            <div class="stat-label">Ventas completadas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger-soft">
                            <i class="bi bi-bag-x-fill text-danger"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totalAnuladas ?></div>
                            <div class="stat-label">Anuladas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-layers-fill text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= number_format($totalUnidades) ?></div>
                            <div class="stat-label">Unidades vendidas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-currency-dollar text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value">$<?= number_format($totalIngresos, 2) ?></div>
                            <div class="stat-label">Total ingresos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-clock-history text-primary me-2"></i>Registro de ventas
                    </h6>
                    <input type="text" class="form-control form-control-sm" id="buscador"
                           placeholder="Buscar por cliente, vendedor…" style="width:220px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaHistorial">
                            <?php
                                require_once ROOT . '/app/models/VeterinariaModel.php';
                                $sucNombre = '';
                                foreach ($veterinarias as $_sv) {
                                    if ((int)$_sv['id'] === (int)$veterinaria_id) { $sucNombre = $_sv['nombre']; break; }
                                }
                            ?>
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Radicado</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th class="text-center">Prods.</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Descuento</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ventas)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bi bi-bag fs-2 d-block mb-2 opacity-25"></i>
                                        No hay ventas registradas para esta veterinaria.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($ventas as $i => $v): ?>
                                <?php $anulada = $v['estado'] === 'anulada'; ?>
                                <tr class="<?= $anulada ? 'fila-bloqueada' : '' ?>">
                                    <td class="ps-3">
                                        <span style="font-family:monospace;font-size:.72rem;font-weight:700;
                                                     background:#f5f3ff;color:#4f46e5;border:1px solid #c7d2fe;
                                                     border-radius:.3rem;padding:.1rem .4rem;white-space:nowrap;">
                                            <?= VeterinariaModel::serialVenta($sucNombre, $v['id'], $v['created_at']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="cliente-avatar" style="width:30px;height:30px;font-size:.75rem;">
                                                <?= strtoupper(substr($v['cliente_nombre'] ?: 'G', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="small fw-semibold <?= $anulada ? 'text-muted' : '' ?>">
                                                    <?= htmlspecialchars($v['cliente_nombre'] ?: 'Cliente general') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="cliente-avatar" style="width:26px;height:26px;font-size:.68rem;background:var(--primary-color);">
                                                <?= strtoupper(substr($v['vendedor_nombre'] ?? 'S', 0, 1)) ?>
                                            </div>
                                            <span class="small text-muted"><?= htmlspecialchars($v['vendedor_nombre'] ?? 'Sistema') ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $anulada ? 'secondary' : 'success' ?>-soft text-<?= $anulada ? 'secondary' : 'success' ?>">
                                            <?= $v['total_lineas'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($anulada): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Anulada</span>
                                        <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Completada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-muted small">
                                        <?= $v['descuento'] > 0 ? '-$' . number_format($v['descuento'], 2) : '—' ?>
                                    </td>
                                    <td class="text-end fw-bold <?= $anulada ? 'text-decoration-line-through text-muted' : '' ?>">
                                        $<?= number_format($v['total'], 2) ?>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?= date('d/m/Y', strtotime($v['created_at'])) ?>
                                        <div style="font-size:.72rem;"><?= date('H:i', strtotime($v['created_at'])) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Ver detalle"
                                                    onclick="verDetalle(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['cliente_nombre'] ?: 'Cliente general')) ?>')">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <?php if (!$anulada): ?>
                                            <a href="<?= BASE_URL ?>/ventas/anular?id=<?= $v['id'] ?>"
                                               class="btn btn-sm btn-outline-danger btn-accion" title="Anular"
                                               onclick="return confirm('¿Anular esta venta? Se restaurará el stock.')">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (!empty($ventas)): ?>
                <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center text-muted small">
                    <span>Mostrando <span id="contadorVisible"><?= count($ventas) ?></span> de <?= count($ventas) ?> registros</span>
                    <span class="fw-semibold text-dark">
                        Total: $<?= number_format($totalIngresos, 2) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- Modal detalle de venta -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-receipt-cutoff text-success me-2"></i>
                    Detalle — <span id="detalleCliente"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleBody"></div>
            <div class="modal-footer border-0">
                <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
function verDetalle(id, clienteNombre) {
    document.getElementById('detalleCliente').textContent = clienteNombre;
    document.getElementById('detalleBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    new bootstrap.Modal(document.getElementById('modalDetalle')).show();

    fetch('<?= BASE_URL ?>/ventas/detalle?id=' + id)
        .then(r => r.json())
        .then(lineas => {
            if (!lineas.length) {
                document.getElementById('detalleBody').innerHTML =
                    '<p class="text-center text-muted py-3">Sin productos.</p>';
                return;
            }
            let html = '<div class="table-responsive"><table class="table table-sm align-middle mb-0">';
            html += '<thead class="table-light"><tr><th>Producto</th><th class="text-center">Cant.</th>'
                  + '<th class="text-end">P. Unit.</th><th class="text-end">Subtotal</th></tr></thead><tbody>';
            let total = 0;
            lineas.forEach(l => {
                const sub = l.cantidad * l.precio_unitario - (parseFloat(l.descuento) || 0);
                total += sub;
                html += `<tr>
                    <td>${escHtml(l.producto_nombre)}<br><span class="text-muted" style="font-size:.75rem;">${escHtml(l.codigo||'')}</span></td>
                    <td class="text-center">${l.cantidad} ${escHtml(l.unidad)}</td>
                    <td class="text-end">$${parseFloat(l.precio_unitario).toFixed(2)}</td>
                    <td class="text-end fw-semibold">$${sub.toFixed(2)}</td>
                </tr>`;
            });
            html += `</tbody><tfoot class="table-light">
                <tr><td colspan="3" class="text-end fw-bold">Total</td>
                <td class="text-end fw-bold text-success">$${total.toFixed(2)}</td></tr>
            </tfoot></table></div>`;
            document.getElementById('detalleBody').innerHTML = html;
        });
}

// Buscador
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let v = 0;
    document.querySelectorAll('#tablaHistorial tbody tr').forEach(tr => {
        const show = tr.textContent.toLowerCase().includes(q);
        tr.style.display = show ? '' : 'none';
        if (show) v++;
    });
    const c = document.getElementById('contadorVisible');
    if (c) c.textContent = v;
});
</script>
</body>
</html>
