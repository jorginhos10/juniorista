<?php
// Reporte de ventas imprimible: tarjetas de ingresos (total, ventas, tratamientos, peluquería, laboratorios)
// y la lista de productos vendidos.
$e      = fn($v) => htmlspecialchars((string)$v);
$money  = fn($n) => '$' . number_format((float)$n, 2);
$meses  = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaLarga = fn(string $f) => (int)date('j', strtotime($f)) . ' de ' . $meses[date('n', strtotime($f)) - 1] . ' de ' . date('Y', strtotime($f));
$periodo = $desde === $hasta ? $fechaLarga($desde) : $fechaLarga($desde) . ' — ' . $fechaLarga($hasta);

$anuladas = (int)($resumen['ventas_anuladas'] ?? 0);
// [nombre, color, monto, cantidad, unidad]
$fuentes = [
    ['Ventas',       '#4f46e5', $balance['ventas'],                (int)($resumen['ventas_completadas'] ?? 0), 'ventas'],
    ['Tratamientos', '#0891b2', $balance['tratamientos']['total'], $balance['tratamientos']['cantidad'],       'abonos'],
    ['Peluquería',   '#db2777', $balance['peluqueria']['negocio'], $balance['peluqueria']['cantidad'],         'servicios'],
    ['Laboratorios', '#7c3aed', $balance['laboratorios']['negocio'], $balance['laboratorios']['cantidad'],     'exámenes'],
];
$totalIngresos = (float)$balance['ingresos'];

// Productos vendidos, ordenados por código como en pantalla
$articulos = $por_articulo ?? [];
usort($articulos, fn($x, $y) => strcmp((string)$x['codigo'], (string)$y['codigo']) ?: strcmp($x['nombre'], $y['nombre']));
$artUnidades = array_sum(array_column($articulos, 'cantidad'));
$artTotal    = array_sum(array_column($articulos, 'total'));
$descuentos  = (float)($resumen['total_descuentos'] ?? 0);
$hayPrecioVariado = false;
$vista = ($_GET['vista'] ?? '') === 'venta' ? 'venta' : 'producto';
$movimientos = $movimientos ?? [];
$tipoSel = isset(FacturacionModel::TIPOS[$_GET['tipo'] ?? '']) ? $_GET['tipo'] : '';
if ($tipoSel !== '') {
    $movimientos = array_values(array_filter($movimientos, fn($m) => $m['tipo'] === $tipoSel));
}
$tituloMov = ['' => 'Ventas y cobros', 'venta' => 'Ventas', 'peluqueria' => 'Peluquería', 'laboratorio' => 'Laboratorios', 'tratamiento' => 'Abonos de tratamientos'][$tipoSel];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas — <?= $e($sucursal_nombre) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; padding: 32px 40px; }

        /* ── Encabezado ── */
        .rpt-header { display: flex; justify-content: space-between; align-items: center; gap: 16px;
                      padding-bottom: 16px; border-bottom: 2px solid #4f46e5; margin-bottom: 18px; }
        .rpt-marca { display: flex; align-items: center; gap: 12px; }
        .rpt-logo  { width: 56px; height: 56px; object-fit: contain; flex-shrink: 0; }
        .rpt-logo-icono { width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0; color: #fff; font-size: 22px;
                          background: linear-gradient(135deg, #1e1b4b, #4f46e5); display: flex; align-items: center; justify-content: center;
                          -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .rpt-sucursal { font-size: 17px; font-weight: 800; color: #1e1b4b; line-height: 1.2; }
        .rpt-sub      { font-size: 10px; color: #6b7280; letter-spacing: .05em; text-transform: uppercase; }
        .rpt-meta { text-align: right; }
        .rpt-titulo  { font-size: 18px; font-weight: 800; color: #1e1b4b; }
        .rpt-periodo { font-size: 12px; font-weight: 600; color: #4f46e5; margin: 2px 0; }
        .rpt-info    { font-size: 10px; color: #9ca3af; }

        /* ── Tarjetas de ingresos ── */
        .tarjetas { display: grid; grid-template-columns: 1.25fr repeat(4, 1fr); gap: 10px; margin-bottom: 22px; }
        .tarjeta { border: 1px solid #e5e7eb; border-top: 4px solid var(--c); border-radius: 10px; padding: 12px 12px 10px;
                   -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .tarjeta-nombre { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }
        .tarjeta-monto  { font-size: 17px; font-weight: 800; color: #111827; margin: 4px 0 2px; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .tarjeta-sub    { font-size: 10px; color: #9ca3af; }
        .tarjeta.total  { background: #f0fdf4; border-color: #bbf7d0; border-top-color: #16a34a; }
        .tarjeta.total .tarjeta-monto { color: #15803d; font-size: 19px; }

        /* Siete tarjetas en una fila (ingresos, fuentes, gastos y lo que queda) */
        .tarjetas.siete { grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; }
        .tarjetas.siete .tarjeta { padding: 8px 7px 7px; border-radius: 8px; }
        .tarjetas.siete .tarjeta-nombre { font-size: 7.5px; letter-spacing: .03em; }
        .tarjetas.siete .tarjeta-monto, .tarjetas.siete .tarjeta.total .tarjeta-monto { font-size: 11px; }
        .tarjetas.siete .tarjeta-sub { font-size: 8px; }

        /* ── Productos vendidos ── */
        .seccion { display: flex; justify-content: space-between; align-items: baseline; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb; }
        .seccion-titulo { font-size: 12px; font-weight: 800; color: #1e1b4b; text-transform: uppercase; letter-spacing: .06em; }
        .seccion-info   { font-size: 10px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }   /* el encabezado se repite en cada página */
        th { text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280;
             padding: 8px; background: #f4f4f5; border-bottom: 1px solid #e5e7eb;
             -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        tbody tr:nth-child(even) td { background: #fafafa; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        tr { page-break-inside: avoid; }
        th.r, td.r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .num { color: #9ca3af; width: 34px; }
        .cod { font-family: Consolas, monospace; font-size: 11px; color: #4b5563; }
        .desc { font-weight: 600; }
        .fac { font-family: Consolas, monospace; font-size: 10.5px; color: #4338ca; white-space: nowrap; }
        .fac span { font-family: 'Segoe UI', Arial, sans-serif; color: #6b7280; }
        tfoot td { font-weight: 800; border-top: 2px solid #1e1b4b; border-bottom: none; background: #fff; font-size: 12.5px; }
        tr.anulado td { color: #9ca3af; }
        tr.anulado .monto { text-decoration: line-through; }
        .vacio { text-align: center; color: #9ca3af; padding: 28px; border: 1px dashed #e5e7eb; border-radius: 8px; margin-top: 12px; }
        .notas { font-size: 10px; color: #6b7280; margin-top: 8px; line-height: 1.5; }

        .rpt-footer { display: flex; justify-content: space-between; font-size: 10px; color: #9ca3af;
                      margin-top: 24px; padding-top: 8px; border-top: 1px solid #e5e7eb; }

        .no-print { position: fixed; top: 16px; right: 16px; display: flex; gap: 8px; }
        .no-print button { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            @page { margin: 14mm 14mm 12mm 14mm; size: A4; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="background:#4f46e5;color:#fff">Imprimir</button>
        <button onclick="window.close()" style="background:#e5e7eb">Cerrar</button>
    </div>

    <!-- Encabezado -->
    <div class="rpt-header">
        <div class="rpt-marca">
            <?php if (!empty($sucursal_logo)): ?>
            <img class="rpt-logo" src="<?= BASE_URL ?>/<?= $e($sucursal_logo) ?>" alt="">
            <?php else: ?>
            <div class="rpt-logo-icono">&#9829;</div>
            <?php endif; ?>
            <div>
                <div class="rpt-sucursal"><?= $e($sucursal_nombre ?: 'PetControl') ?></div>
                <div class="rpt-sub">PetControl · Sistema de gestión veterinaria</div>
            </div>
        </div>
        <div class="rpt-meta">
            <div class="rpt-titulo">Reporte de ventas</div>
            <div class="rpt-periodo"><?= $periodo ?></div>
            <div class="rpt-info">Generado el <?= date('d/m/Y H:i') ?> por <?= $e($usuario['nombre']) ?></div>
        </div>
    </div>

    <!-- Ingresos: total, ventas, tratamientos, peluquería y laboratorios -->
    <?php $gastosTotal = (float)$balance['gastos']['total']; $neto = (float)$balance['utilidad']; $nGastos = (int)$balance['gastos']['cantidad']; ?>
    <div class="tarjetas siete">
        <div class="tarjeta total" style="--c:#16a34a">
            <div class="tarjeta-nombre">Total ingresos</div>
            <div class="tarjeta-monto"><?= $money($totalIngresos) ?></div>
            <div class="tarjeta-sub">Todo lo cobrado</div>
        </div>
        <?php foreach ($fuentes as [$nombre, $color, $monto, $cantidad, $unidad]): ?>
        <div class="tarjeta" style="--c:<?= $color ?>">
            <div class="tarjeta-nombre"><?= $nombre ?></div>
            <div class="tarjeta-monto"><?= $money($monto) ?></div>
            <div class="tarjeta-sub">
                <?= (int)$cantidad ?> <?= (int)$cantidad === 1 ? ['ventas' => 'venta', 'abonos' => 'abono', 'servicios' => 'servicio', 'exámenes' => 'examen'][$unidad] : $unidad ?><?= $totalIngresos > 0 ? ' · ' . round($monto / $totalIngresos * 100) . '%' : '' ?>
                <?= $nombre === 'Ventas' && $anuladas > 0 ? '<br>' . $anuladas . ' anulada' . ($anuladas === 1 ? '' : 's') : '' ?>
            </div>
        </div>
        <?php endforeach; ?>
        <!-- Menos los gastos del período -->
        <div class="tarjeta" style="--c:#dc2626">
            <div class="tarjeta-nombre">Gastos</div>
            <div class="tarjeta-monto" style="color:#b91c1c;">−<?= $money($gastosTotal) ?></div>
            <div class="tarjeta-sub"><?= $nGastos ?> <?= $nGastos === 1 ? 'gasto' : 'gastos' ?></div>
        </div>
        <div class="tarjeta" style="--c:<?= $neto < 0 ? '#dc2626' : '#16a34a' ?>">
            <div class="tarjeta-nombre">Después de gastos</div>
            <div class="tarjeta-monto" style="color:<?= $neto < 0 ? '#b91c1c' : '#15803d' ?>;"><?= $neto < 0 ? '−' : '' ?><?= $money(abs($neto)) ?></div>
            <div class="tarjeta-sub">Ingresos − gastos</div>
        </div>
    </div>

    <?php if ($vista === 'venta'):
        $movTotal = array_sum(array_map(fn($m) => $m['anulada'] ? 0 : (float)$m['monto'], $movimientos)); ?>
    <!-- Por venta: cada venta y cada cobro, con quién lo recibió -->
    <div class="seccion">
        <span class="seccion-titulo"><?= $tituloMov ?></span>
        <span class="seccion-info"><?= count($movimientos) ?> movimiento<?= count($movimientos) === 1 ? '' : 's' ?></span>
    </div>
    <?php if (empty($movimientos)): ?>
    <div class="vacio">No hubo ventas ni cobros en el período.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Cliente</th>
                <th>Comprobante</th>
                <th>Tipo</th>
                <th class="r">Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $m):
                $quien = $m['tipo'] === 'venta'
                    ? (trim((string)$m['cliente']) ?: 'Venta rápida')
                    : $m['mascota'] . ($m['especie'] ? ' (' . ucfirst($m['especie']) . ')' : ''); ?>
            <tr<?= $m['anulada'] ? ' class="anulado"' : '' ?>>
                <td style="white-space:nowrap;"><?= date('d/m/Y g:i a', strtotime($m['creado'])) ?></td>
                <td><?= $e($m['usuario_nombre'] ?: 'Sin usuario') ?></td>
                <td><?= $e($quien) ?></td>
                <td class="fac"><?= $e($m['comprobante']) ?></td>
                <td><?= FacturacionModel::TIPOS[$m['tipo']]['label'] ?></td>
                <td class="r monto"><?= $money($m['monto']) ?></td>
                <td><?= $m['anulada'] ? 'Anulada' : 'Aceptada' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="r">Total cobrado (sin anuladas)</td>
                <td class="r"><?= $money($movTotal) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
    <?php else: ?>
    <!-- Productos vendidos -->
    <div class="seccion">
        <span class="seccion-titulo">Productos vendidos</span>
        <span class="seccion-info"><?= count($articulos) ?> producto<?= count($articulos) === 1 ? '' : 's' ?> · <?= (int)$artUnidades ?> unidades</span>
    </div>

    <?php if (empty($articulos)): ?>
    <div class="vacio">No se vendieron productos en el período.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Factura · Vendió</th>
                <th class="r">P. Venta</th>
                <th class="r">Cantidad</th>
                <th class="r">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articulos as $i => $a):
                $variado = abs((float)$a['precio_max'] - (float)$a['precio_min']) > 0.009;
                $hayPrecioVariado = $hayPrecioVariado || $variado; ?>
            <tr>
                <td class="num"><?= $i + 1 ?></td>
                <td class="cod"><?= $e($a['codigo'] ?: '—') ?></td>
                <td class="desc"><?= $e($a['nombre']) ?></td>
                <td class="fac">
                    <?php foreach ($a['facturas'] ?? [] as $fa): ?>
                    <div><?= $e($fa['radicado']) ?> <span>· <?= $e($fa['vendedor']) ?></span></div>
                    <?php endforeach; ?>
                </td>
                <td class="r"><?= $money($a['precio_unitario']) ?><?= $variado ? ' *' : '' ?></td>
                <td class="r"><?= (int)$a['cantidad'] ?> <?= $e($a['unidad'] ?? '') ?></td>
                <td class="r"><?= $money($a['total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="r"><?= $descuentos > 0 ? 'Subtotal de productos vendidos' : 'Total de productos vendidos' ?></td>
                <td class="r"><?= (int)$artUnidades ?></td>
                <td class="r"><?= $money($artTotal) ?></td>
            </tr>
            <?php if ($descuentos > 0): /* el descuento general de las ventas se resta al final */ ?>
            <tr>
                <td colspan="6" class="r">Descuentos</td>
                <td class="r" style="color:#b91c1c;">−<?= $money($descuentos) ?></td>
            </tr>
            <tr>
                <td colspan="6" class="r">Total vendido</td>
                <td class="r"><?= $money(max(0, $artTotal - $descuentos)) ?></td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>
    <?php if ($hayPrecioVariado): ?>
    <div class="notas">
        * Producto vendido a precios distintos en el período: se muestra el precio promedio.
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php endif; /* vista */ ?>

    <div class="rpt-footer">
        <span>PetControl &mdash; <?= $e($sucursal_nombre) ?></span>
        <span>Reporte de ventas · <?= $periodo ?></span>
    </div>

</body>
<script>window.onload = function(){ window.print(); }</script>
</html>
