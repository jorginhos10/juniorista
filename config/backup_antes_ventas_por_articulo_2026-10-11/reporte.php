<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Reporte de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .rpt-filter {
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .rpt-filter label { font-weight: 600; font-size: .82rem; color: #374151; margin-bottom: .3rem; }

        .top-list { list-style: none; padding: 0; margin: 0; }
        .top-list li {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem 0; border-bottom: 1px solid #f3f4f6;
        }
        .top-list li:last-child { border-bottom: none; }
        .top-rank {
            width: 26px; height: 26px; border-radius: 50%;
            background: #eef2ff; color: #4f46e5;
            font-size: .72rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .top-rank.gold   { background: #fef9c3; color: #854d0e; }
        .top-rank.silver { background: #f1f5f9; color: #475569; }
        .top-rank.bronze { background: #fef3c7; color: #92400e; }
        .top-bar-wrap { flex: 1; }
        .top-bar-label { font-size: .8rem; font-weight: 600; }
        .top-bar-sub   { font-size: .72rem; color: #9ca3af; }
        .top-bar-track {
            height: 6px; background: #f3f4f6; border-radius: 99px; margin-top: .3rem;
        }
        .top-bar-fill {
            height: 100%; border-radius: 99px;
            background: linear-gradient(90deg, #4f46e5, #818cf8);
            transition: width .6s ease;
        }
        .top-val { font-weight: 700; font-size: .82rem; color: #1f2937; white-space: nowrap; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Reporte de Ventas</h5>
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

            <!-- Filtro de fechas -->
            <form method="GET" action="<?= BASE_URL ?>/reportes/ventas" class="rpt-filter">
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
                <button type="submit" class="btn btn-primary-custom btn-sm">
                    <i class="bi bi-search me-1"></i> Consultar
                </button>
                <a href="<?= BASE_URL ?>/reportes/ventas" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Hoy
                </a>
                <a href="<?= BASE_URL ?>/reportes/ventas/imprimir?desde=<?= $desde ?>&hasta=<?= $hasta ?>"
                   target="_blank" class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="bi bi-printer me-1"></i> Imprimir / PDF
                </a>
            </form>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-bag-check-fill text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $resumen['ventas_completadas'] ?? 0 ?></div>
                            <div class="stat-label">Ventas completadas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-currency-dollar text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-value">$<?= number_format($resumen['total_ingresos'] ?? 0, 2) ?></div>
                            <div class="stat-label">Total ingresos</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-graph-up text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value">$<?= number_format($resumen['promedio_venta'] ?? 0, 2) ?></div>
                            <div class="stat-label">Promedio por venta</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger-soft">
                            <i class="bi bi-bag-x-fill text-danger"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $resumen['ventas_anuladas'] ?? 0 ?></div>
                            <div class="stat-label">Anuladas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance: ventas + peluquería + tratamientos − gastos -->
            <?php
                $qPeriodo = http_build_query(['desde' => $desde, 'hasta' => $hasta]);
                $fuentes = [
                    ['Ventas',       'bag-fill',  '#4f46e5', $balance['ventas'],                $resumen['ventas_completadas'] ?? 0, 'ventas',      BASE_URL . '/ventas/historial'],
                    ['Peluquería',   'scissors',  '#db2777', $balance['peluqueria']['total'],   $balance['peluqueria']['cantidad'],   'servicios',   BASE_URL . '/peluqueria'],
                    ['Tratamientos (abonos)', 'capsule', '#0891b2', $balance['tratamientos']['total'], $balance['tratamientos']['cantidad'], 'abonos', BASE_URL . '/tratamiento'],
                ];
                $utilidad = $balance['utilidad'];
            ?>
            <div class="card border-0 shadow-sm mb-4" style="border-radius:.875rem;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-calculator-fill text-primary"></i>
                        Balance del período
                        <span class="text-muted fw-normal small ms-1">ventas + peluquería + abonos de tratamientos − gastos</span>
                    </h6>
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <table class="table table-sm align-middle mb-0 balance-tabla">
                                <tbody>
                                    <?php foreach ($fuentes as [$nom, $ico, $col, $monto, $cant, $unidad, $link]): ?>
                                    <tr>
                                        <td style="width:28px"><i class="bi bi-<?= $ico ?>" style="color:<?= $col ?>"></i></td>
                                        <td>
                                            <a href="<?= $link ?>" class="text-decoration-none text-body fw-semibold"><?= $nom ?></a>
                                            <span class="text-muted small ms-1"><?= (int)$cant ?> <?= $unidad ?></span>
                                        </td>
                                        <td class="text-end text-success fw-semibold">+ $<?= number_format($monto, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-light">
                                        <td></td>
                                        <td class="fw-bold">Total ingresos</td>
                                        <td class="text-end fw-bold">$<?= number_format($balance['ingresos'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-wallet2 text-danger"></i></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/gastos?<?= $qPeriodo ?>" class="text-decoration-none text-body fw-semibold">Gastos</a>
                                            <span class="text-muted small ms-1"><?= $balance['gastos']['cantidad'] ?> registros</span>
                                        </td>
                                        <td class="text-end text-danger fw-semibold">− $<?= number_format($balance['gastos']['total'], 2) ?></td>
                                    </tr>
                                    <tr class="<?= $utilidad >= 0 ? 'table-success' : 'table-danger' ?>">
                                        <td><i class="bi bi-graph-<?= $utilidad >= 0 ? 'up' : 'down' ?>-arrow"></i></td>
                                        <td class="fw-bold"><?= $utilidad >= 0 ? 'Utilidad neta' : 'Pérdida neta' ?></td>
                                        <td class="text-end fw-bold fs-6"><?= $utilidad < 0 ? '−' : '' ?>$<?= number_format(abs($utilidad), 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-5">
                            <div class="small fw-semibold text-muted mb-2">Composición de los ingresos</div>
                            <?php if ($balance['ingresos'] > 0): ?>
                            <div class="d-flex rounded overflow-hidden mb-2" style="height:14px">
                                <?php foreach ($fuentes as [$nom, , $col, $monto]): if ($monto <= 0) continue; ?>
                                <div style="width:<?= round($monto / $balance['ingresos'] * 100, 2) ?>%; background:<?= $col ?>" title="<?= $nom ?>"></div>
                                <?php endforeach; ?>
                            </div>
                            <?php foreach ($fuentes as [$nom, , $col, $monto]): ?>
                            <div class="d-flex justify-content-between small py-1">
                                <span><span class="d-inline-block rounded-circle me-2" style="width:9px;height:9px;background:<?= $col ?>"></span><?= $nom ?></span>
                                <span class="text-muted"><?= round($monto / $balance['ingresos'] * 100) ?>%</span>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <p class="text-muted small mb-0">Sin ingresos en este período.</p>
                            <?php endif; ?>
                            <?php if ($balance['ingresos'] > 0): ?>
                            <div class="small text-muted mt-2 pt-2 border-top">
                                Los gastos representan el <strong><?= round($balance['gastos']['total'] / $balance['ingresos'] * 100) ?>%</strong> de los ingresos.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top productos y vendedores -->
            <div class="row g-3 mb-4">

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:.875rem;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-box-seam-fill text-primary"></i>
                                Top productos vendidos
                            </h6>
                            <?php if (empty($top_productos)): ?>
                            <p class="text-muted small text-center py-3">Sin datos en este período.</p>
                            <?php else: ?>
                            <?php
                            $maxUnidades = max(array_column($top_productos, 'total_unidades')) ?: 1;
                            $rankColors  = ['gold', 'silver', 'bronze', '', ''];
                            ?>
                            <ul class="top-list">
                                <?php foreach ($top_productos as $idx => $p): ?>
                                <li>
                                    <div class="top-rank <?= $rankColors[$idx] ?? '' ?>"><?= $idx + 1 ?></div>
                                    <div class="top-bar-wrap">
                                        <div class="top-bar-label"><?= htmlspecialchars($p['nombre']) ?></div>
                                        <div class="top-bar-sub"><?= number_format($p['total_unidades']) ?> unidades</div>
                                        <div class="top-bar-track">
                                            <div class="top-bar-fill" style="width:<?= round($p['total_unidades'] / $maxUnidades * 100) ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="top-val">$<?= number_format($p['total_ingresos'], 2) ?></div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:.875rem;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-person-fill text-success"></i>
                                Top vendedores
                            </h6>
                            <?php if (empty($top_vendedores)): ?>
                            <p class="text-muted small text-center py-3">Sin datos en este período.</p>
                            <?php else: ?>
                            <?php $maxIngresos = max(array_column($top_vendedores, 'total_ingresos')) ?: 1; ?>
                            <ul class="top-list">
                                <?php foreach ($top_vendedores as $idx => $v): ?>
                                <li>
                                    <div class="top-rank <?= $rankColors[$idx] ?? '' ?>"><?= $idx + 1 ?></div>
                                    <div class="top-bar-wrap">
                                        <div class="top-bar-label"><?= htmlspecialchars($v['nombre']) ?></div>
                                        <div class="top-bar-sub"><?= $v['total_ventas'] ?> ventas</div>
                                        <div class="top-bar-track">
                                            <div class="top-bar-fill" style="width:<?= round($v['total_ingresos'] / $maxIngresos * 100) ?>%; background:linear-gradient(90deg,#10b981,#6ee7b7);"></div>
                                        </div>
                                    </div>
                                    <div class="top-val">$<?= number_format($v['total_ingresos'], 2) ?></div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tabla detalle -->
            <div class="card border-0 shadow-sm" style="border-radius:.875rem;">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-list-ul text-primary"></i>
                        Detalle de ventas
                        <span class="badge bg-primary-soft text-primary fw-semibold" style="font-size:.75rem;">
                            <?= count($ventas) ?> registros
                        </span>
                    </h6>
                    <?php if (!empty($resumen['total_descuentos'])): ?>
                    <span class="text-muted small">
                        Descuentos aplicados: <strong>$<?= number_format($resumen['total_descuentos'], 2) ?></strong>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th class="text-center">Productos</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Descuento</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Fecha y hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ventas)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-bar-chart fs-2 d-block mb-2 opacity-25"></i>
                                        No hay ventas en el período seleccionado.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($ventas as $i => $v): ?>
                                <?php $anulada = $v['estado'] === 'anulada'; ?>
                                <tr class="<?= $anulada ? 'fila-bloqueada' : '' ?>">
                                    <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="cliente-avatar" style="width:28px;height:28px;font-size:.7rem;">
                                                <?= strtoupper(substr($v['cliente_nombre'] ?: 'G', 0, 1)) ?>
                                            </div>
                                            <span class="small fw-semibold">
                                                <?= htmlspecialchars($v['cliente_nombre'] ?: 'Cliente general') ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="cliente-avatar" style="width:24px;height:24px;font-size:.65rem;background:var(--primary-color);">
                                                <?= strtoupper(substr($v['vendedor_nombre'] ?? 'S', 0, 1)) ?>
                                            </div>
                                            <span class="small text-muted">
                                                <?= htmlspecialchars($v['vendedor_nombre'] ?? 'Sistema') ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $anulada ? 'secondary' : 'success' ?>-soft text-<?= $anulada ? 'secondary' : 'success' ?>">
                                            <?= $v['total_lineas'] ?> prod.
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
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($ventas)): ?>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="6" class="text-end fw-bold ps-3">Total período</td>
                                    <td class="text-end fw-bold text-success">
                                        $<?= number_format($resumen['total_ingresos'] ?? 0, 2) ?>
                                    </td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
