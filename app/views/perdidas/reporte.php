<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Reporte de Pérdidas</title>
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
    </style>
</head>
<body class="dashboard-body">
<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Reporte de Pérdidas</h5>
                <p class="topbar-date mb-0">
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
            <form method="GET" action="<?= BASE_URL ?>/reportes/perdidas" class="rpt-filter">
                <div>
                    <label>Desde</label>
                    <input type="date" class="form-control form-control-sm" name="desde" value="<?= $desde ?>">
                </div>
                <div>
                    <label>Hasta</label>
                    <input type="date" class="form-control form-control-sm" name="hasta" value="<?= $hasta ?>">
                </div>
                <button type="submit" class="btn btn-primary-custom btn-sm">
                    <i class="bi bi-search me-1"></i> Consultar
                </button>
                <a href="<?= BASE_URL ?>/reportes/perdidas" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Este mes
                </a>
                <a href="<?= BASE_URL ?>/reportes/perdidas/imprimir?desde=<?= $desde ?>&hasta=<?= $hasta ?>"
                   target="_blank" class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="bi bi-printer me-1"></i> Imprimir / PDF
                </a>
            </form>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger-soft">
                            <i class="bi bi-box-arrow-up-right text-danger"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $resumen['total_perdidas'] ?? 0 ?></div>
                            <div class="stat-label">Total registros</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-layers-fill text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= number_format($resumen['total_unidades'] ?? 0) ?></div>
                            <div class="stat-label">Unidades perdidas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-currency-dollar text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-value">$<?= number_format($resumen['valor_total'] ?? 0, 2) ?></div>
                            <div class="stat-label">Valor total</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary-soft">
                            <i class="bi bi-calendar-x-fill text-secondary"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $resumen['por_vencimiento'] ?? 0 ?></div>
                            <div class="stat-label">Por vencimiento</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Motivos resumen -->
            <?php if (!empty($perdidas)): ?>
            <div class="row g-3 mb-4">
                <?php foreach ($motivos as $key => $m): ?>
                <?php $cnt = $resumen['por_' . ($key === 'a_bodega' ? 'bodega' : ($key === 'propietario' ? 'propietario' : $key))] ?? 0; ?>
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                        <div class="card-body d-flex align-items-center gap-3 py-3">
                            <div class="stat-icon bg-<?= $m['color'] ?>-soft flex-shrink-0" style="width:40px;height:40px;font-size:1rem;">
                                <i class="bi bi-<?= $m['icon'] ?> text-<?= $m['color'] ?>"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-5"><?= $cnt ?></div>
                                <div class="text-muted small"><?= $m['label'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Tabla -->
            <div class="card border-0 shadow-sm" style="border-radius:.875rem;">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-list-ul text-danger"></i>
                        Detalle de pérdidas
                        <span class="badge bg-danger-soft text-danger fw-semibold" style="font-size:.75rem;">
                            <?= count($perdidas) ?> registros
                        </span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Motivo</th>
                                    <th>Responsable</th>
                                    <th class="text-center">Productos</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-end">Valor</th>
                                    <th>Notas</th>
                                    <th class="text-center">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($perdidas)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-box-arrow-up-right fs-2 d-block mb-2 opacity-25"></i>
                                        No hay pérdidas en el período seleccionado.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($perdidas as $i => $p): ?>
                                <?php $m = $motivos[$p['motivo']] ?? ['label' => $p['motivo'], 'icon' => 'dash', 'color' => 'secondary']; ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <span class="badge bg-<?= $m['color'] ?>-subtle text-<?= $m['color'] ?> border border-<?= $m['color'] ?>-subtle">
                                            <i class="bi bi-<?= $m['icon'] ?> me-1"></i><?= $m['label'] ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($p['responsable'] ?: ($p['responsable_nombre'] ?: '—')) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger-soft text-danger"><?= $p['total_lineas'] ?> prod.</span>
                                    </td>
                                    <td class="text-center fw-bold text-danger"><?= number_format($p['total_unidades'] ?? 0) ?> u.</td>
                                    <td class="text-end fw-bold">$<?= number_format($p['total'], 2) ?></td>
                                    <td class="text-muted small" style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= htmlspecialchars($p['notas'] ?: '—') ?>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                        <div style="font-size:.72rem;"><?= date('H:i', strtotime($p['created_at'])) ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($perdidas)): ?>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold ps-3">Total período</td>
                                    <td class="text-end fw-bold text-danger">$<?= number_format($resumen['valor_total'] ?? 0, 2) ?></td>
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
</body>
</html>
