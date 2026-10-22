<?php
// Historial de cotizaciones: solo cotizaciones (las ventas tienen su propio historial)
require_once ROOT . '/app/models/VeterinariaModel.php';
$sucNombre = '';
foreach ($veterinarias as $_sv) { if ((int)$_sv['id'] === (int)$veterinaria_id) { $sucNombre = $_sv['nombre']; break; } }
$totalCotizado = array_sum(array_map(fn($c) => (float)$c['total'], $cotizaciones));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Cotizaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-body">
<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Historial de cotizaciones</h5>
                <p class="topbar-date mb-0">Cotizaciones guardadas desde el punto de venta</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <a href="<?= BASE_URL ?>/ventas?cotizar=1" class="btn btn-primary-custom btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva cotización
                </a>
                <a href="<?= BASE_URL ?>/ventas" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Ventas
                </a>
            </div>
        </header>

        <main class="main-content">
            <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i><span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i><span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-file-earmark-text text-primary me-2"></i>Cotizaciones
                        <span class="badge bg-primary-soft text-primary ms-1"><?= count($cotizaciones) ?></span>
                    </h6>
                    <?php if ($cotizaciones): ?>
                    <input type="text" class="form-control form-control-sm" id="buscarCotizacion"
                           placeholder="Buscar por cliente, número, producto…" style="width:260px;">
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaCotizaciones">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>N.º</th>
                                    <th>Cliente</th>
                                    <th>Detalle</th>
                                    <th>Cotizó</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$cotizaciones): ?>
                                <tr><td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-file-earmark-text fs-2 d-block mb-2 opacity-25"></i>
                                    Aún no hay cotizaciones. Se crean con el «Modo cotización» del punto de venta.
                                </td></tr>
                                <?php endif; ?>
                                <?php foreach ($cotizaciones as $c): ?>
                                <tr data-monto="<?= (float)$c['total'] ?>">
                                    <td class="ps-3 text-nowrap small">
                                        <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                                        <div class="text-muted"><?= date('H:i', strtotime($c['created_at'])) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars(VeterinariaModel::serialServicio($sucNombre, 'COT', (int)$c['id'], $c['created_at'])) ?></span></td>
                                    <td><?= htmlspecialchars(trim((string)$c['cliente']) ?: 'Cliente general') ?></td>
                                    <td class="small text-muted" style="max-width:340px;">
                                        <div class="text-truncate" title="<?= htmlspecialchars((string)$c['detalle']) ?>"><?= (int)$c['lineas'] ?> ítem<?= (int)$c['lineas'] === 1 ? '' : 's' ?> · <?= htmlspecialchars((string)$c['detalle']) ?></div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($c['usuario_nombre'] ?? '—') ?></td>
                                    <td class="text-end fw-semibold">$<?= number_format((float)$c['total'], 2) ?></td>
                                    <td class="text-center text-nowrap pe-3">
                                        <a href="<?= BASE_URL ?>/ventas/cotizacion/ver?id=<?= (int)$c['id'] ?>" target="_blank"
                                           class="btn btn-sm btn-outline-secondary btn-accion" title="Ver e imprimir"><i class="bi bi-printer"></i></a>
                                        <a href="<?= BASE_URL ?>/ventas?cotizacion=<?= (int)$c['id'] ?>"
                                           class="btn btn-sm btn-outline-primary btn-accion" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($cotizaciones): ?>
                <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center text-muted small">
                    <span>Mostrando <span id="contadorVisible"><?= count($cotizaciones) ?></span> de <?= count($cotizaciones) ?> cotizaciones</span>
                    <span class="fw-semibold text-dark">Total cotizado: $<span id="totalVisible"><?= number_format($totalCotizado, 2) ?></span></span>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const b = document.getElementById('buscarCotizacion');
    if (!b) return;
    b.addEventListener('input', () => {
        const q = b.value.toLowerCase();
        let n = 0, total = 0;
        document.querySelectorAll('#tablaCotizaciones tbody tr').forEach(tr => {
            const ver = tr.textContent.toLowerCase().includes(q);
            tr.style.display = ver ? '' : 'none';
            if (ver) { n++; total += Number(tr.dataset.monto || 0); }
        });
        document.getElementById('contadorVisible').textContent = n;
        document.getElementById('totalVisible').textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });
})();
</script>
</body>
</html>
