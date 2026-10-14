<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Reporte de Ingresos</title>
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
                <h5 class="topbar-title mb-0">Reporte de Ingresos</h5>
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
                $vista   = ($_GET['vista'] ?? '') === 'ingreso' ? 'venta' : 'producto';   // 'venta' = panel de movimientos (mismos estilos que el reporte de ventas)
                $tipoSel = isset($tipos[$_GET['tipo'] ?? '']) ? $_GET['tipo'] : '';
                $vistaUrl = $vista === 'venta' ? 'ingreso' : 'producto';
                $verIngreso = Permisos::puede('ingresos');   // el radicado enlaza al PDF del ingreso
            ?>
            <!-- Filtro de fechas -->
            <form method="GET" action="<?= BASE_URL ?>/reportes/ingresos" class="rpt-filter">
                <input type="hidden" name="vista" id="vistaInput" value="<?= $vistaUrl ?>">
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
                <a href="<?= BASE_URL ?>/reportes/ingresos" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Hoy
                </a>
                <a href="<?= BASE_URL ?>/reportes/ingresos/imprimir?desde=<?= $desde ?>&hasta=<?= $hasta ?>&vista=<?= $vistaUrl ?>&tipo=<?= $tipoSel ?>"
                   target="_blank" id="btnImprimir" class="btn btn-outline-secondary btn-sm ms-auto">
                    <i class="bi bi-printer me-1"></i> Imprimir / PDF
                </a>
            </form>

            <!-- Resumen del período: lo que entró (compras + transferencias recibidas) y lo que salió por transferencia -->
            <?php
                // Todo valorado al precio de venta actual; la ganancia proyectada es esa venta menos lo que costó la mercancía
                $entro     = $resumen['compra']['venta'] + $resumen['transferencia_entrada']['venta'];
                $entroUnid = $resumen['compra']['unidades'] + $resumen['transferencia_entrada']['unidades'];
                $ganancia  = $resumen['compra']['ganancia'] + $resumen['transferencia_entrada']['ganancia'];
            ?>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4 col-xl">
                    <div class="ing-card ing-total">
                        <div class="ing-ico"><i class="bi bi-box-seam"></i></div>
                        <div>
                            <div class="ing-monto">$<?= number_format($entro, 2) ?></div>
                            <div class="ing-nombre">Total ingresado a precio de venta</div>
                            <div class="ing-sub"><?= (int)$entroUnid ?> <?= (int)$entroUnid === 1 ? 'unidad' : 'unidades' ?> al inventario</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="ing-card" style="--c:#16a34a">
                        <div class="ing-ico"><i class="bi bi-graph-up-arrow"></i></div>
                        <div>
                            <div class="ing-monto">$<?= number_format($ganancia, 2) ?></div>
                            <div class="ing-nombre">Ganancia proyectada</div>
                            <div class="ing-sub">Al vender toda la mercancía<?= $entro > 0 ? ' · ' . round($ganancia / $entro * 100) . '% de margen' : '' ?></div>
                        </div>
                    </div>
                </div>
                <?php foreach ($tipos as $k => $t): $r = $resumen[$k]; ?>
                <div class="col-6 col-md-4 col-xl">
                    <div class="ing-card" style="--c:<?= $t['color'] ?>">
                        <div class="ing-ico"><i class="bi bi-<?= $t['icon'] ?>"></i></div>
                        <div>
                            <div class="ing-monto">$<?= number_format($r['venta'], 2) ?></div>
                            <div class="ing-nombre"><?= $t['plural'] ?></div>
                            <div class="ing-sub"><?= $r['cantidad'] ?> <?= $t['unidad'][$r['cantidad'] === 1 ? 0 : 1] ?> · <?= (int)$r['unidades'] ?> <?= (int)$r['unidades'] === 1 ? 'unidad' : 'unidades' ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php
            $artUnidades = array_sum(array_column($por_articulo, 'cantidad'));
            $artTotal    = array_sum(array_column($por_articulo, 'venta'));
            $artGanancia = array_sum(array_column($por_articulo, 'ganancia'));
            usort($por_articulo, fn($x, $y) => strcmp((string)$x['codigo'], (string)$y['codigo']) ?: strcmp($x['nombre'], $y['nombre']));
            ?>
            <!-- Pestañas: desglose por producto / todos los ingresos -->
            <div class="vista-switch mb-3" role="tablist">
                <button type="button" role="tab" data-vista="producto" class="<?= $vista === 'producto' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam"></i> Por producto <span><?= count($por_articulo) ?></span>
                </button>
                <button type="button" role="tab" data-vista="ingreso" class="<?= $vista === 'venta' ? 'active' : '' ?>">
                    <i class="bi bi-receipt"></i> Por ingreso <span><?= count($movimientos) ?></span>
                </button>
            </div>

            <div class="vista-panel <?= $vista === 'producto' ? '' : 'd-none' ?>" id="vista-producto">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:.875rem;">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3 pb-2">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam text-primary"></i>
                        Productos ingresados
                        <span class="badge bg-primary-soft text-primary fw-semibold" style="font-size:.75rem;">
                            <?= count($por_articulo) ?> <?= count($por_articulo) === 1 ? 'producto' : 'productos' ?>
                        </span>
                    </h6>
                    <?php if (!empty($por_articulo)): ?>
                    <input type="text" class="form-control form-control-sm" data-buscar="tablaArticulos" placeholder="Buscar producto, código o ingreso…" style="width:250px;">
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($por_articulo)): ?>
                    <p class="text-muted text-center py-4 mb-0">No ingresaron productos en el período.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 tabla-orden" id="tablaArticulos">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 th-orden" data-tipo="num" style="width:70px;">#</th>
                                    <th class="th-orden orden-asc" data-tipo="txt">Código</th>
                                    <th class="th-orden" data-tipo="txt">Descripción</th>
                                    <th class="th-orden" data-tipo="txt">Ingreso</th>
                                    <th class="th-orden text-end" data-tipo="num">P. Venta</th>
                                    <th class="th-orden text-end" data-tipo="num">Cantidad</th>
                                    <th class="th-orden text-end" data-tipo="num">Total venta</th>
                                    <th class="th-orden text-end pe-3" data-tipo="num">Ganancia proyectada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($por_articulo as $i => $a): ?>
                                <tr>
                                    <td class="ps-3 text-muted small" data-v="<?= $i + 1 ?>"><?= $i + 1 ?></td>
                                    <td class="small" style="font-family:monospace;" data-v="<?= htmlspecialchars(mb_strtolower((string)$a['codigo'])) ?>"><?= htmlspecialchars($a['codigo'] ?: '—') ?></td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($a['nombre'])) ?>">
                                        <div class="fw-semibold"><?= htmlspecialchars($a['nombre']) ?></div>
                                        <?php if (count($a['ingresos']) > 1): ?>
                                        <div class="text-muted small">en <?= count($a['ingresos']) ?> ingresos</div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($a['ingresos'][0]['radicado'] ?? '')) ?>">
                                        <?php foreach ($a['ingresos'] as $n => $in):
                                            $info = 'Registrado por ' . $in['usuario'] . ' · ' . date('d/m/Y g:i a', strtotime($in['created_at'])) . ' · ' . $in['cantidad'] . ' ' . ($a['unidad'] ?? ''); ?>
                                        <?php if ($n === 3): ?><span class="fac-mas d-none"><?php endif; ?>
                                        <?php if ($verIngreso): ?>
                                        <a class="fac-chip" href="<?= BASE_URL ?>/ingresos/pdf?id=<?= $in['id'] ?>" target="_blank"
                                           title="<?= htmlspecialchars($info) ?>"><i class="bi bi-receipt"></i><?= htmlspecialchars($in['radicado']) ?></a>
                                        <?php else: ?>
                                        <span class="fac-chip" title="<?= htmlspecialchars($info) ?>"><i class="bi bi-receipt"></i><?= htmlspecialchars($in['radicado']) ?></span>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (count($a['ingresos']) > 3): ?></span>
                                        <button type="button" class="fac-ver" data-n="<?= count($a['ingresos']) - 3 ?>">+<?= count($a['ingresos']) - 3 ?> más</button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end" data-v="<?= (float)$a['precio_venta'] ?>">
                                        <?php if ((float)$a['precio_venta'] > 0): ?>$<?= number_format($a['precio_venta'], 2) ?><?php else: ?><span class="text-warning" title="Este producto no tiene precio de venta en Almacén">Sin precio</span><?php endif; ?>
                                    </td>
                                    <td class="text-end fw-semibold" data-v="<?= (int)$a['cantidad'] ?>"><?= (int)$a['cantidad'] ?> <span class="text-muted small fw-normal"><?= htmlspecialchars($a['unidad'] ?? '') ?></span></td>
                                    <td class="text-end fw-bold" data-v="<?= (float)$a['venta'] ?>">$<?= number_format($a['venta'], 2) ?></td>
                                    <td class="text-end fw-bold pe-3 <?= $a['ganancia'] < 0 ? 'text-danger' : 'text-success' ?>" data-v="<?= (float)$a['ganancia'] ?>">$<?= number_format($a['ganancia'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="5" class="text-end">Total de productos ingresados</td>
                                    <td class="text-end"><?= (int)$artUnidades ?></td>
                                    <td class="text-end">$<?= number_format($artTotal, 2) ?></td>
                                    <td class="text-end pe-3 <?= $artGanancia < 0 ? 'text-danger' : 'text-success' ?>">$<?= number_format($artGanancia, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="text-muted small px-3 py-2">
                        Valores al precio de venta actual de cada producto. Ganancia proyectada = total venta − lo que costó la mercancía en el ingreso.
                        Incluye compras y transferencias recibidas; las transferencias enviadas no se cuentan aquí.
                    </div>
                    <script>
                    // Productos con más de tres ingresos: el resto se despliega con «+N más»
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

            <!-- Por ingreso: cada compra o transferencia del período, con quién la registró -->
            <?php $movTotal = $entro; ?>
            <div class="vista-panel <?= $vista === 'venta' ? '' : 'd-none' ?>" id="vista-ingreso">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:.875rem;">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3 pb-2">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-receipt text-primary"></i>
                        Ingresos
                        <span class="badge bg-primary-soft text-primary fw-semibold" style="font-size:.75rem;">
                            <?= count($movimientos) ?> <?= count($movimientos) === 1 ? 'movimiento' : 'movimientos' ?>
                        </span>
                    </h6>
                    <?php if (!empty($movimientos)): ?>
                    <input type="text" class="form-control form-control-sm" data-buscar="tablaVentas" placeholder="Buscar usuario, proveedor, comprobante…" style="width:270px;">
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($movimientos)): ?>
                    <p class="text-muted text-center py-4 mb-0">No hubo ingresos en el período.</p>
                    <?php else: ?>
                    <!-- Filtro por tipo -->
                    <div class="px-3 pb-3">
                        <div class="small fw-semibold text-muted mb-1">Tipo</div>
                        <div class="seg" id="filtroTipo">
                            <button type="button" data-tipo="" class="<?= $tipoSel === '' ? 'activo' : '' ?>">Todos</button>
                            <?php foreach ($tipos as $k => $t): ?>
                            <button type="button" data-tipo="<?= $k ?>" class="<?= $tipoSel === $k ? 'activo' : '' ?>" style="--c:<?= $t['color'] ?>">
                                <i class="bi bi-<?= $t['icon'] ?>"></i><?= $t['plural'] ?>
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
                                    <th class="th-orden" data-tipo="txt">Proveedor</th>
                                    <th class="th-orden" data-tipo="txt">Comprobante</th>
                                    <th class="th-orden" data-tipo="txt">Tipo</th>
                                    <th class="th-orden text-end" data-tipo="num">Unidades</th>
                                    <th class="th-orden text-end" data-tipo="num">Total venta</th>
                                    <th class="th-orden text-end pe-3" data-tipo="num">Ganancia proyectada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientos as $m):
                                    $t = $tipos[$m['tipo']];
                                    $proveedor = trim((string)$m['proveedor']) ?: ($m['tipo'] === 'compra' ? 'Sin proveedor' : '—');
                                ?>
                                <tr data-tipo="<?= $m['tipo'] ?>" data-monto="<?= (float)$m['venta'] ?>" data-ganancia="<?= (float)$m['ganancia'] ?>">
                                    <td class="ps-3 text-nowrap" data-v="<?= htmlspecialchars($m['created_at']) ?>">
                                        <?= date('d/m/Y', strtotime($m['created_at'])) ?>
                                        <div class="text-muted small"><?= date('g:i a', strtotime($m['created_at'])) ?></div>
                                    </td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($m['usuario_nombre'])) ?>"><?= htmlspecialchars($m['usuario_nombre']) ?></td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($proveedor)) ?>"><?= htmlspecialchars($proveedor) ?></td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($m['comprobante'])) ?>">
                                        <?php if ($verIngreso): ?>
                                        <a class="fac-chip" href="<?= BASE_URL ?>/ingresos/pdf?id=<?= (int)$m['id'] ?>" target="_blank"
                                           title="Registrado por <?= htmlspecialchars($m['usuario_nombre']) ?> · clic para ver el ingreso"><i class="bi bi-receipt"></i><?= htmlspecialchars($m['comprobante']) ?></a>
                                        <?php else: ?>
                                        <span class="fac-chip" title="Registrado por <?= htmlspecialchars($m['usuario_nombre']) ?>"><i class="bi bi-receipt"></i><?= htmlspecialchars($m['comprobante']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-v="<?= htmlspecialchars(mb_strtolower($t['label'])) ?>">
                                        <span class="mov-tipo" style="--c:<?= $t['color'] ?>"><i class="bi bi-<?= $t['icon'] ?>"></i><?= $t['label'] ?></span>
                                        <?php if (trim((string)$m['notas']) !== ''): ?>
                                        <div class="text-muted small mov-detalle" title="<?= htmlspecialchars($m['notas']) ?>"><?= htmlspecialchars($m['notas']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end" data-v="<?= (int)$m['unidades'] ?>">
                                        <?= (int)$m['unidades'] ?>
                                        <div class="text-muted small"><?= (int)$m['lineas'] ?> <?= (int)$m['lineas'] === 1 ? 'producto' : 'productos' ?></div>
                                    </td>
                                    <td class="text-end fw-bold mov-monto" data-v="<?= (float)$m['venta'] ?>">$<?= number_format($m['venta'], 2) ?></td>
                                    <td class="text-end fw-bold pe-3 <?= $m['ganancia'] < 0 ? 'text-danger' : 'text-success' ?>" data-v="<?= (float)$m['ganancia'] ?>">$<?= number_format($m['ganancia'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="6" class="text-end">Total</td>
                                    <td class="text-end" id="totalVentas">$<?= number_format($movTotal, 2) ?></td>
                                    <td class="text-end pe-3 text-success" id="totalGanancia">$<?= number_format($ganancia, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="text-muted text-center py-4 mb-0 d-none" id="sinVentas">No hay movimientos con ese filtro.</p>
                    <div class="text-muted small px-3 py-2">Valores al precio de venta actual. En «Todos», el total suma compras y transferencias recibidas; las enviadas son salidas y no se suman.</div>
                    <?php endif; ?>
                </div>
            </div>
            </div><!-- /vista-ingreso -->

            <script>
            // Pestañas «Por producto» / «Por ingreso»: cambian sin recargar y se conservan al consultar o imprimir
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
                // las transferencias enviadas son salidas: solo se suman cuando se filtra por ellas
                const soloSalidas = document.getElementById('tipoInput').value === 'transferencia_salida';
                const total = filas.reduce((t, tr) => t + (soloSalidas || tr.dataset.tipo !== 'transferencia_salida' ? Number(tr.dataset.monto) : 0), 0);
                document.getElementById('totalVentas').textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const ganancia = filas.reduce((t, tr) => t + (soloSalidas || tr.dataset.tipo !== 'transferencia_salida' ? Number(tr.dataset.ganancia) : 0), 0);
                document.getElementById('totalGanancia').textContent = '$' + ganancia.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('sinVentas').classList.toggle('d-none', filas.length > 0);
            }
            function filtrarTipo(tipo) {
                document.getElementById('tipoInput').value = tipo;
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
