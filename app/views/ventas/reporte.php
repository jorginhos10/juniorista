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
        /* Tarjetas de ingresos */
        .ing-card {
            --c: #16a34a;
            display: flex; align-items: center; gap: .85rem; height: 100%;
            background: #fff; border: 1px solid #e8eaf0; border-left: 4px solid var(--c);
            border-radius: .875rem; padding: 1rem 1.1rem; text-decoration: none; color: inherit;
            transition: box-shadow .15s, transform .15s;
        }
        a.ing-card:hover { box-shadow: 0 6px 18px rgba(15, 23, 42, .08); transform: translateY(-2px); color: inherit; }
        .ing-ico {
            width: 44px; height: 44px; border-radius: .75rem; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
            color: var(--c); background: color-mix(in srgb, var(--c) 14%, #fff);
        }
        .ing-monto  { font-size: 1.2rem; font-weight: 800; line-height: 1.15; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .ing-nombre { font-size: .82rem; font-weight: 600; color: #334155; }
        .ing-sub    { font-size: .72rem; color: #94a3b8; }
        .ing-total  { background: linear-gradient(135deg, #16a34a, #15803d); border-color: transparent; color: #fff; }
        .ing-total .ing-ico { background: rgba(255, 255, 255, .2); color: #fff; }
        .ing-total .ing-nombre, .ing-total .ing-sub { color: rgba(255, 255, 255, .85); }

        /* Las siete tarjetas en una fila: tamaño compacto que se adapta al ancho de la pantalla */
        .ing-fila { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .75rem; }
        .ing-fila .ing-card { padding: .8rem .85rem; gap: .65rem; }
        .ing-fila .ing-card > div:last-child { min-width: 0; }
        .ing-fila .ing-ico { width: 36px; height: 36px; border-radius: .6rem; font-size: 1rem; }
        .ing-fila .ing-monto { font-size: clamp(.82rem, 1.02vw, 1.1rem); }
        .ing-fila .ing-nombre { font-size: .76rem; line-height: 1.2; }
        .ing-fila .ing-sub { font-size: .68rem; line-height: 1.25; }
        @media (max-width: 1699.98px) {   /* menos ancho: el ícono pasa arriba del texto */
            .ing-fila .ing-card { flex-direction: column; align-items: flex-start; gap: .45rem; padding: .7rem .7rem; }
            .ing-fila .ing-ico { width: 30px; height: 30px; font-size: .9rem; }
        }
        @media (max-width: 1199.98px) { .ing-fila { grid-template-columns: repeat(4, minmax(0, 1fr)); } .ing-fila .ing-monto { font-size: 1rem; } }
        @media (max-width: 767.98px)  { .ing-fila { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

        /* Encabezados ordenables */
        .th-orden { cursor: pointer; user-select: none; white-space: nowrap; }
        .th-orden::after { content: "↑↓"; margin-left: .4rem; font-size: .72rem; letter-spacing: -2px; color: #cbd5e1; }
        .th-orden.orden-asc::after  { content: "↑"; color: #ea580c; }
        .th-orden.orden-desc::after { content: "↓"; color: #ea580c; }
        .th-orden:hover { background: #eef2f7; }
        #tablaArticulos tbody tr:nth-child(even) { background: #fafafa; }

        /* Radicado de la factura de cada producto vendido */
        .fac-chip {
            display: inline-flex; align-items: center; gap: .3rem; margin: .1rem .15rem .1rem 0; padding: .15rem .5rem;
            font-family: monospace; font-size: .78rem; font-weight: 600; white-space: nowrap; text-decoration: none;
            color: #4338ca; background: #eef2ff; border: 1px solid #e0e7ff; border-radius: 999px;
        }
        a.fac-chip:hover { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .fac-ver { border: 0; background: none; padding: 0 .2rem; font-size: .75rem; font-weight: 600; color: #64748b; }
        .fac-ver:hover { color: #4f46e5; text-decoration: underline; }

        /* Pestañas tipo interruptor */
        .vista-switch { display: inline-flex; gap: .25rem; padding: .3rem; background: #e6e9ef; border-radius: 999px;
                        box-shadow: inset 0 1px 3px rgba(15, 23, 42, .12); }
        .vista-switch button { display: inline-flex; align-items: center; gap: .45rem; border: 0; background: none; border-radius: 999px;
                               padding: .45rem 1.1rem; font-size: .88rem; font-weight: 600; color: #64748b; transition: background .15s, color .15s, box-shadow .15s; }
        .vista-switch button:hover { color: #334155; }
        .vista-switch button.active { background: #fff; color: #4f46e5; box-shadow: 0 2px 6px rgba(15, 23, 42, .18); }
        .vista-switch button span { font-size: .72rem; padding: .05rem .45rem; border-radius: 999px; background: rgba(100, 116, 139, .15); }
        .vista-switch button.active span { background: #eef2ff; }

        /* Por venta */
        .seg { display: inline-flex; background: #f1f5f9; border-radius: 10px; padding: 3px; flex-wrap: wrap; }
        .seg button { padding: .35rem .8rem; border: 0; background: none; border-radius: 8px; font-size: .82rem; font-weight: 600;
                      color: #475569; display: inline-flex; align-items: center; gap: .35rem; }
        .seg button i { color: var(--c, #475569); }
        .seg button.activo { background: #fff; color: #1e293b; box-shadow: 0 1px 3px rgba(15, 23, 42, .1); }
        tr.oculto-busq, tr.oculto-tipo { display: none; }
        #tablaVentas tbody tr:nth-child(even) { background: #fafafa; }
        .mov-tipo { display: inline-flex; align-items: center; gap: .35rem; font-size: .8rem; font-weight: 600; color: var(--c); white-space: nowrap; }
        .mov-detalle { max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .mov-estado { display: inline-block; padding: .2rem .7rem; border-radius: 999px; font-size: .75rem; font-weight: 700; color: #fff; background: #22c55e; }
        .mov-estado.anulada { background: #ef4444; }
        tr.mov-anulado td { color: #94a3b8; }
        tr.mov-anulado .mov-monto { text-decoration: line-through; }

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

            <?php
                $vista   = ($_GET['vista'] ?? '') === 'venta' ? 'venta' : 'producto';
                // Filtro por tipo de la pestaña «Por venta»
                $tipoSel = isset(FacturacionModel::TIPOS[$_GET['tipo'] ?? '']) ? $_GET['tipo'] : '';
            ?>
            <!-- Filtro de fechas -->
            <form method="GET" action="<?= BASE_URL ?>/reportes/ventas" class="rpt-filter">
                <input type="hidden" name="vista" id="vistaInput" value="<?= $vista ?>">
                <input type="hidden" name="tipo" id="tipoInput" value="<?= $tipoSel ?>">
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
                <a href="<?= BASE_URL ?>/reportes/ventas/imprimir?desde=<?= $desde ?>&hasta=<?= $hasta ?>&vista=<?= $vista ?>&tipo=<?= $tipoSel ?>"
                   target="_blank" id="btnImprimir" class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="bi bi-printer me-1"></i> Imprimir / PDF
                </a>
            </form>

            <!-- Ingresos del período: ventas, tratamientos (abonos), peluquería y laboratorios -->
            <?php
                $vista = ($_GET['vista'] ?? '') === 'venta' ? 'venta' : 'producto';
                $movimientos = $movimientos ?? [];
                $qPeriodo = http_build_query(['desde' => $desde, 'hasta' => $hasta]);
                $anuladas = (int)($resumen['ventas_anuladas'] ?? 0);
                // [nombre, ícono, color, monto, cantidad, unidad, enlace]
                $fuentes = [
                    ['Ventas',                'bag-fill',   '#4f46e5', $balance['ventas'],                (int)($resumen['ventas_completadas'] ?? 0), 'ventas',    BASE_URL . '/ventas/historial'],
                    ['Tratamientos (abonos)', 'capsule',    '#0891b2', $balance['tratamientos']['total'], $balance['tratamientos']['cantidad'],       'abonos',    BASE_URL . '/tratamiento'],
                    ['Peluquería',            'scissors',   '#db2777', $balance['peluqueria']['negocio'], $balance['peluqueria']['cantidad'],         'servicios', BASE_URL . '/peluqueria'],
                    ['Laboratorios',          'eyedropper', '#7c3aed', $balance['laboratorios']['total'], $balance['laboratorios']['cantidad'],       'exámenes',  BASE_URL . '/laboratorio'],
                ];
            ?>
            <!-- Una sola fila: ingresos, sus cuatro fuentes, gastos y lo que queda -->
            <div class="ing-fila mb-4">
                <div>
                    <div class="ing-card ing-total">
                        <div class="ing-ico"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <div class="ing-monto">$<?= number_format($balance['ingresos'], 2) ?></div>
                            <div class="ing-nombre">Total ingresos</div>
                            <div class="ing-sub">Todo lo cobrado</div>
                        </div>
                    </div>
                </div>
                <?php foreach ($fuentes as [$nom, $ico, $col, $monto, $cant, $unidad, $link]): ?>
                <div>
                    <a href="<?= $link ?>" class="ing-card" style="--c:<?= $col ?>">
                        <div class="ing-ico"><i class="bi bi-<?= $ico ?>"></i></div>
                        <div>
                            <div class="ing-monto">$<?= number_format($monto, 2) ?></div>
                            <div class="ing-nombre"><?= $nom ?></div>
                            <div class="ing-sub">
                                <?= (int)$cant ?> <?= (int)$cant === 1 ? ['ventas' => 'venta', 'abonos' => 'abono', 'servicios' => 'servicio', 'exámenes' => 'examen'][$unidad] : $unidad ?><?php if ($balance['ingresos'] > 0): ?> · <?= round($monto / $balance['ingresos'] * 100) ?>%<?php endif; ?>
                                <?php if ($nom === 'Ventas' && $anuladas > 0): ?> · <span class="text-danger"><?= $anuladas ?> anulada<?= $anuladas === 1 ? '' : 's' ?></span><?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>

                <!-- Menos los gastos del período: ingresos − gastos = lo que queda -->
                <?php $gastosTotal = (float)$balance['gastos']['total']; $neto = (float)$balance['utilidad']; $nGastos = (int)$balance['gastos']['cantidad']; ?>
                <div>
                    <a href="<?= BASE_URL ?>/gastos" class="ing-card" style="--c:#dc2626">
                        <div class="ing-ico"><i class="bi bi-wallet2"></i></div>
                        <div>
                            <div class="ing-monto text-danger">−$<?= number_format($gastosTotal, 2) ?></div>
                            <div class="ing-nombre">Gastos</div>
                            <div class="ing-sub"><?= $nGastos ?> <?= $nGastos === 1 ? 'gasto' : 'gastos' ?></div>
                        </div>
                    </a>
                </div>
                <div>
                    <div class="ing-card" style="--c:<?= $neto < 0 ? '#dc2626' : '#16a34a' ?>">
                        <div class="ing-ico"><i class="bi bi-calculator"></i></div>
                        <div>
                            <div class="ing-monto <?= $neto < 0 ? 'text-danger' : 'text-success' ?>"><?= $neto < 0 ? '−' : '' ?>$<?= number_format(abs($neto), 2) ?></div>
                            <div class="ing-nombre">Después de gastos</div>
                            <div class="ing-sub">Ingresos − gastos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ventas por artículo: una línea por producto con todas sus cantidades sumadas (columnas ordenables) -->
            <?php
            $artUnidades = array_sum(array_column($por_articulo ?? [], 'cantidad'));
            $artTotal    = array_sum(array_column($por_articulo ?? [], 'total'));
            $hayPrecioVariado = false;
            // Orden inicial por código, como en el listado de referencia
            $por_articulo = $por_articulo ?? [];
            usort($por_articulo, fn($x, $y) => strcmp((string)$x['codigo'], (string)$y['codigo']) ?: strcmp($x['nombre'], $y['nombre']));
            // El radicado enlaza a la factura solo si el usuario puede entrar a Facturación
            $verFactura = Permisos::puede('facturacion');
            ?>
            <!-- Pestañas: desglose por producto / todas las ventas y cobros -->
            <div class="vista-switch mb-3" role="tablist">
                <button type="button" role="tab" data-vista="producto" class="<?= $vista === 'producto' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam"></i> Por producto <span><?= count($por_articulo) ?></span>
                </button>
                <button type="button" role="tab" data-vista="venta" class="<?= $vista === 'venta' ? 'active' : '' ?>">
                    <i class="bi bi-receipt"></i> Por venta <span><?= count($movimientos) ?></span>
                </button>
            </div>

            <div class="vista-panel <?= $vista === 'producto' ? '' : 'd-none' ?>" id="vista-producto">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:.875rem;">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3 pb-2">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam text-primary"></i>
                        Productos vendidos
                        <span class="badge bg-primary-soft text-primary fw-semibold" style="font-size:.75rem;">
                            <?= count($por_articulo ?? []) ?> <?= count($por_articulo ?? []) === 1 ? 'producto' : 'productos' ?>
                        </span>
                    </h6>
                    <?php if (!empty($por_articulo)): ?>
                    <input type="text" class="form-control form-control-sm" data-buscar="tablaArticulos" placeholder="Buscar producto, código o factura…" style="width:250px;">
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($por_articulo)): ?>
                    <p class="text-muted text-center py-4 mb-0">No se vendieron productos en el período.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 tabla-orden" id="tablaArticulos">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 th-orden" data-tipo="num" style="width:70px;">#</th>
                                    <th class="th-orden orden-asc" data-tipo="txt">Código</th>
                                    <th class="th-orden" data-tipo="txt">Descripción</th>
                                    <th class="th-orden" data-tipo="txt">Factura</th>
                                    <th class="th-orden text-end" data-tipo="num">P. Venta</th>
                                    <th class="th-orden text-end" data-tipo="num">Cantidad</th>
                                    <th class="th-orden text-end pe-3" data-tipo="num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($por_articulo as $i => $a):
                                    $variado = abs((float)$a['precio_max'] - (float)$a['precio_min']) > 0.009;
                                    $hayPrecioVariado = $hayPrecioVariado || $variado;
                                ?>
                                <tr>
                                    <td class="ps-3 text-muted small" data-v="<?= $i + 1 ?>"><?= $i + 1 ?></td>
                                    <td class="small" style="font-family:monospace;" data-v="<?= htmlspecialchars(mb_strtolower((string)$a['codigo'])) ?>"><?= htmlspecialchars($a['codigo'] ?: '—') ?></td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($a['nombre'])) ?>">
                                        <div class="fw-semibold"><?= htmlspecialchars($a['nombre']) ?></div>
                                        <?php if ((int)$a['ventas'] > 1): ?>
                                        <div class="text-muted small">en <?= (int)$a['ventas'] ?> ventas</div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($a['facturas'][0]['radicado'] ?? '')) ?>">
                                        <?php foreach ($a['facturas'] as $n => $fa):
                                            $info = 'Vendida por ' . $fa['vendedor'] . ' · ' . date('d/m/Y g:i a', strtotime($fa['created_at'])) . ' · ' . $fa['cantidad'] . ' ' . ($a['unidad'] ?? ''); ?>
                                        <?php if ($n === 3): ?><span class="fac-mas d-none"><?php endif; ?>
                                        <?php if ($verFactura): ?>
                                        <a class="fac-chip" href="<?= BASE_URL ?>/facturacion/imprimir?tipo=venta&id=<?= $fa['id'] ?>" target="_blank"
                                           title="<?= htmlspecialchars($info) ?>"><i class="bi bi-receipt"></i><?= htmlspecialchars($fa['radicado']) ?></a>
                                        <?php else: ?>
                                        <span class="fac-chip" title="<?= htmlspecialchars($info) ?>"><i class="bi bi-receipt"></i><?= htmlspecialchars($fa['radicado']) ?></span>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (count($a['facturas']) > 3): ?></span>
                                        <button type="button" class="fac-ver" data-n="<?= count($a['facturas']) - 3 ?>">+<?= count($a['facturas']) - 3 ?> más</button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end" data-v="<?= (float)$a['precio_unitario'] ?>">
                                        $<?= number_format($a['precio_unitario'], 2) ?><?php if ($variado): ?>
                                        <span class="text-warning" title="Se vendió a precios distintos: de $<?= number_format($a['precio_min'], 2) ?> a $<?= number_format($a['precio_max'], 2) ?>. Se muestra el promedio.">*</span><?php endif; ?>
                                    </td>
                                    <td class="text-end fw-semibold" data-v="<?= (int)$a['cantidad'] ?>"><?= (int)$a['cantidad'] ?> <span class="text-muted small fw-normal"><?= htmlspecialchars($a['unidad'] ?? '') ?></span></td>
                                    <td class="text-end fw-bold pe-3" data-v="<?= (float)$a['total'] ?>">$<?= number_format($a['total'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <?php $_desc = (float)($resumen['total_descuentos'] ?? 0); ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="5" class="text-end"><?= $_desc > 0 ? 'Subtotal de productos vendidos' : 'Total de productos vendidos' ?></td>
                                    <td class="text-end"><?= (int)$artUnidades ?></td>
                                    <td class="text-end pe-3">$<?= number_format($artTotal, 2) ?></td>
                                </tr>
                                <?php if ($_desc > 0): /* el descuento general de las ventas se resta al final */ ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="6" class="text-end">Descuentos</td>
                                    <td class="text-end pe-3 text-danger">−$<?= number_format($_desc, 2) ?></td>
                                </tr>
                                <tr class="table-light fw-bold">
                                    <td colspan="6" class="text-end">Total vendido</td>
                                    <td class="text-end pe-3">$<?= number_format(max(0, $artTotal - $_desc), 2) ?></td>
                                </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                    </div>
                    <?php if ($hayPrecioVariado): ?>
                    <div class="text-muted small px-3 py-2">
                        <?php if ($hayPrecioVariado): ?><div><span class="text-warning fw-bold">*</span> Producto vendido a precios distintos en el periodo: se muestra el precio promedio.</div><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <script>
                    // Productos con más de tres facturas: el resto se despliega con «+N más»
                    document.querySelectorAll('#tablaArticulos .fac-ver').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const oculto = btn.previousElementSibling.classList.toggle('d-none');
                            btn.textContent = oculto ? '+' + btn.dataset.n + ' más' : 'ver menos';
                        });
                    });

                    </script>
                    <?php endif; ?>
                </div>
            </div>

            </div><!-- /vista-producto -->

            <!-- Por venta: cada venta y cada cobro del período, con quién lo recibió -->
            <?php
            $tiposMov   = FacturacionModel::TIPOS;
            $movTotal   = array_sum(array_map(fn($m) => $m['anulada'] ? 0 : (float)$m['monto'], $movimientos));
            $movAnulados = count(array_filter($movimientos, fn($m) => $m['anulada']));
            ?>
            <div class="vista-panel <?= $vista === 'venta' ? '' : 'd-none' ?>" id="vista-venta">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:.875rem;">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3 pb-2">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-receipt text-primary"></i>
                        Ventas y cobros
                        <span class="badge bg-primary-soft text-primary fw-semibold" style="font-size:.75rem;">
                            <?= count($movimientos) ?> <?= count($movimientos) === 1 ? 'movimiento' : 'movimientos' ?>
                        </span>
                    </h6>
                    <?php if (!empty($movimientos)): ?>
                    <input type="text" class="form-control form-control-sm" data-buscar="tablaVentas" placeholder="Buscar usuario, cliente, comprobante…" style="width:270px;">
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($movimientos)): ?>
                    <p class="text-muted text-center py-4 mb-0">No hubo ventas ni cobros en el período.</p>
                    <?php else: ?>
                    <!-- Filtro por tipo -->
                    <div class="px-3 pb-3">
                        <div class="small fw-semibold text-muted mb-1">Tipo</div>
                        <div class="seg" id="filtroTipo">
                            <button type="button" data-tipo="" class="<?= $tipoSel === '' ? 'activo' : '' ?>">Todos</button>
                            <?php foreach ($tiposMov as $k => $t): ?>
                            <button type="button" data-tipo="<?= $k ?>" class="<?= $tipoSel === $k ? 'activo' : '' ?>" style="--c:<?= $t['color'] ?>">
                                <i class="bi bi-<?= $t['icon'] ?>"></i><?= $k === 'venta' ? 'Ventas' : ($k === 'tratamiento' ? 'Tratamientos (abonos)' : ($k === 'laboratorio' ? 'Laboratorios' : $t['label'])) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 tabla-orden" id="tablaVentas">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 th-orden orden-desc" data-tipo="txt">Fecha</th>
                                    <th class="th-orden" data-tipo="txt">Usuario</th>
                                    <th class="th-orden" data-tipo="txt">Cliente</th>
                                    <th class="th-orden" data-tipo="txt">Comprobante</th>
                                    <th class="th-orden" data-tipo="txt">Tipo</th>
                                    <th class="th-orden text-end" data-tipo="num">Total</th>
                                    <th class="th-orden pe-3" data-tipo="txt">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientos as $m):
                                    $t = $tiposMov[$m['tipo']];
                                    if ($m['tipo'] === 'venta') {
                                        $quien = trim((string)$m['cliente']) ?: 'Venta rápida';
                                    } else {
                                        $quien = $m['mascota'] . ($m['especie'] ? ' (' . ucfirst($m['especie']) . ')' : '');
                                    }
                                    $usuarioMov = $m['usuario_nombre'] ?: 'Sin usuario';
                                ?>
                                <tr class="<?= $m['anulada'] ? 'mov-anulado' : '' ?>" data-tipo="<?= $m['tipo'] ?>" data-monto="<?= $m['anulada'] ? 0 : (float)$m['monto'] ?>">
                                    <td class="ps-3 text-nowrap" data-v="<?= htmlspecialchars($m['creado']) ?>">
                                        <?= date('d/m/Y', strtotime($m['fecha'])) ?>
                                        <div class="text-muted small"><?= date('g:i a', strtotime($m['creado'])) ?></div>
                                    </td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($usuarioMov)) ?>"><?= htmlspecialchars($usuarioMov) ?></td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($quien)) ?>"><?= htmlspecialchars($quien) ?></td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($m['comprobante'])) ?>">
                                        <?php if ($verFactura): ?>
                                        <a class="fac-chip" href="<?= BASE_URL ?>/facturacion/imprimir?tipo=<?= $m['tipo'] ?>&id=<?= (int)$m['id'] ?>" target="_blank"
                                           title="Recibido por <?= htmlspecialchars($usuarioMov) ?> · clic para ver la factura"><i class="bi bi-receipt"></i><?= htmlspecialchars($m['comprobante']) ?></a>
                                        <?php else: ?>
                                        <span class="fac-chip" title="Recibido por <?= htmlspecialchars($usuarioMov) ?>"><i class="bi bi-receipt"></i><?= htmlspecialchars($m['comprobante']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($t['label'])) ?>">
                                        <span class="mov-tipo" style="--c:<?= $t['color'] ?>"><i class="bi bi-<?= $t['icon'] ?>"></i><?= $t['label'] ?></span>
                                        <?php if ($m['detalle'] !== ''): ?>
                                        <div class="text-muted small mov-detalle" title="<?= htmlspecialchars($m['detalle']) ?>"><?= htmlspecialchars($m['detalle']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold mov-monto" data-v="<?= (float)$m['monto'] ?>">$<?= number_format($m['monto'], 2) ?></td>
                                    <td class="pe-3" data-v="<?= $m['anulada'] ? 'anulada' : 'aceptada' ?>">
                                        <span class="mov-estado <?= $m['anulada'] ? 'anulada' : '' ?>"><?= $m['anulada'] ? 'Anulada' : 'Aceptada' ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="5" class="text-end">Total cobrado<?= $movAnulados ? ' <span class="text-muted fw-normal small">(sin contar ' . $movAnulados . ' anulada' . ($movAnulados === 1 ? '' : 's') . ')</span>' : '' ?></td>
                                    <td class="text-end" id="totalVentas">$<?= number_format($movTotal, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="text-muted text-center py-4 mb-0 d-none" id="sinVentas">No hay movimientos con ese filtro.</p>
                    <?php endif; ?>
                </div>
            </div>
            </div><!-- /vista-venta -->

            <script>
            // Pestañas «Por producto» / «Por venta»: cambian sin recargar y se conservan al consultar o imprimir
            document.querySelectorAll('.vista-switch button').forEach(btn => {
                btn.addEventListener('click', () => {
                    const vista = btn.dataset.vista;
                    document.querySelectorAll('.vista-switch button').forEach(b => b.classList.toggle('active', b === btn));
                    document.querySelectorAll('.vista-panel').forEach(p => p.classList.toggle('d-none', p.id !== 'vista-' + vista));
                    document.getElementById('vistaInput').value = vista;
                    const imp = document.getElementById('btnImprimir');
                    imp.href = imp.href.replace(/vista=\w+/, 'vista=' + vista);
                    const url = new URL(location.href);
                    url.searchParams.set('vista', vista);
                    history.replaceState(null, '', url);
                });
            });

            // Ordenar por columna: un clic ascendente, otro descendente
            document.querySelectorAll('table.tabla-orden').forEach(tabla => {
                const ths = tabla.querySelectorAll('.th-orden');
                ths.forEach(th => {
                    th.addEventListener('click', () => {
                        const asc = !th.classList.contains('orden-asc');
                        ths.forEach(x => x.classList.remove('orden-asc', 'orden-desc'));
                        th.classList.add(asc ? 'orden-asc' : 'orden-desc');
                        const num = th.dataset.tipo === 'num', col = th.cellIndex;
                        const tbody = tabla.tBodies[0];
                        [...tbody.rows].sort((a, b) => {
                            const x = a.cells[col].dataset.v, y = b.cells[col].dataset.v;
                            const r = num ? Number(x) - Number(y) : (x < y ? -1 : x > y ? 1 : 0);   // como texto: "0009" va antes que "11913", igual que el orden inicial
                            return asc ? r : -r;
                        }).forEach(tr => tbody.appendChild(tr));
                    });
                });
            });

            // Buscador de cada tabla
            document.querySelectorAll('[data-buscar]').forEach(inp => {
                inp.addEventListener('input', () => {
                    const q = inp.value.toLowerCase();
                    document.querySelectorAll('#' + inp.dataset.buscar + ' tbody tr').forEach(tr => {
                        tr.classList.toggle('oculto-busq', !tr.textContent.toLowerCase().includes(q));
                    });
                    if (inp.dataset.buscar === 'tablaVentas') totalizarVentas();
                });
            });

            // «Por venta»: filtro por tipo (ventas, peluquería, laboratorios, abonos de tratamientos)
            function totalizarVentas() {
                const filas = [...document.querySelectorAll('#tablaVentas tbody tr')].filter(tr => !tr.matches('.oculto-busq, .oculto-tipo'));
                const total = filas.reduce((t, tr) => t + Number(tr.dataset.monto), 0);
                document.getElementById('totalVentas').textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('sinVentas').classList.toggle('d-none', filas.length > 0);
            }
            function filtrarTipo(tipo) {
                document.querySelectorAll('#filtroTipo button').forEach(b => b.classList.toggle('activo', b.dataset.tipo === tipo));
                document.querySelectorAll('#tablaVentas tbody tr').forEach(tr => tr.classList.toggle('oculto-tipo', tipo !== '' && tr.dataset.tipo !== tipo));
                totalizarVentas();
                document.getElementById('tipoInput').value = tipo;
                const imp = document.getElementById('btnImprimir');
                imp.href = imp.href.replace(/tipo=\w*/, 'tipo=' + tipo);
                const url = new URL(location.href);
                tipo ? url.searchParams.set('tipo', tipo) : url.searchParams.delete('tipo');
                history.replaceState(null, '', url);
            }
            if (document.getElementById('filtroTipo')) {
                document.querySelectorAll('#filtroTipo button').forEach(b => b.addEventListener('click', () => filtrarTipo(b.dataset.tipo)));
                filtrarTipo(document.getElementById('tipoInput').value);
            }
            </script>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
