<?php
// Reporte de ingresos imprimible: tarjetas del período y la pestaña que se esté viendo (por producto o por ingreso).
$e      = fn($v) => htmlspecialchars((string)$v);
$money  = fn($n) => '$' . number_format((float)$n, 2);
$meses  = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaLarga = fn(string $f) => (int)date('j', strtotime($f)) . ' de ' . $meses[date('n', strtotime($f)) - 1] . ' de ' . date('Y', strtotime($f));
$periodo = $desde === $hasta ? $fechaLarga($desde) : $fechaLarga($desde) . ' — ' . $fechaLarga($hasta);

$vista   = ($_GET['vista'] ?? '') === 'ingreso' ? 'ingreso' : 'producto';
$tipoSel = isset($tipos[$_GET['tipo'] ?? '']) ? $_GET['tipo'] : '';
// Todo valorado al precio de venta actual; ganancia proyectada = venta − costo del ingreso
$entro     = $resumen['compra']['venta'] + $resumen['transferencia_entrada']['venta'];
$ganancia  = $resumen['compra']['ganancia'] + $resumen['transferencia_entrada']['ganancia'];
$entroUnid = $resumen['compra']['unidades'] + $resumen['transferencia_entrada']['unidades'];

$articulos = $por_articulo;
usort($articulos, fn($x, $y) => strcmp((string)$x['codigo'], (string)$y['codigo']) ?: strcmp($x['nombre'], $y['nombre']));
$artUnidades = array_sum(array_column($articulos, 'cantidad'));
$artTotal    = array_sum(array_column($articulos, 'venta'));
$artGanancia = array_sum(array_column($articulos, 'ganancia'));

if ($tipoSel !== '') {
    $movimientos = array_values(array_filter($movimientos, fn($m) => $m['tipo'] === $tipoSel));
}
// En «Todos» las transferencias enviadas no se suman (son salidas)
$movTotal    = array_sum(array_map(fn($m) => ($tipoSel === '' && $m['tipo'] === 'transferencia_salida') ? 0 : (float)$m['venta'], $movimientos));
$movGanancia = array_sum(array_map(fn($m) => ($tipoSel === '' && $m['tipo'] === 'transferencia_salida') ? 0 : (float)$m['ganancia'], $movimientos));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos — <?= $e($sucursal_nombre) ?></title>
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
            <div class="rpt-titulo">Reporte de ingresos</div>
            <div class="rpt-periodo"><?= $periodo ?></div>
            <div class="rpt-info">Generado el <?= date('d/m/Y H:i') ?> por <?= $e($usuario['nombre']) ?></div>
        </div>
    </div>

    <!-- Resumen del período -->
    <div class="tarjetas" style="grid-template-columns: 1.25fr repeat(4, 1fr);">
        <div class="tarjeta total" style="--c:#16a34a">
            <div class="tarjeta-nombre">Total a precio de venta</div>
            <div class="tarjeta-monto"><?= $money($entro) ?></div>
            <div class="tarjeta-sub"><?= (int)$entroUnid ?> <?= (int)$entroUnid === 1 ? 'unidad' : 'unidades' ?> al inventario</div>
        </div>
        <div class="tarjeta" style="--c:#16a34a">
            <div class="tarjeta-nombre">Ganancia proyectada</div>
            <div class="tarjeta-monto"><?= $money($ganancia) ?></div>
            <div class="tarjeta-sub">Al vender toda la mercancía</div>
        </div>
        <?php foreach ($tipos as $k => $t): $r = $resumen[$k]; ?>
        <div class="tarjeta" style="--c:<?= $t['color'] ?>">
            <div class="tarjeta-nombre"><?= $t['plural'] ?></div>
            <div class="tarjeta-monto"><?= $money($r['venta']) ?></div>
            <div class="tarjeta-sub"><?= $r['cantidad'] ?> <?= $t['unidad'][$r['cantidad'] === 1 ? 0 : 1] ?> · <?= (int)$r['unidades'] ?> <?= (int)$r['unidades'] === 1 ? 'unidad' : 'unidades' ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($vista === 'ingreso'): ?>
    <!-- Por ingreso -->
    <div class="seccion">
        <span class="seccion-titulo"><?= $tipoSel === '' ? 'Ingresos' : $tipos[$tipoSel]['plural'] ?></span>
        <span class="seccion-info"><?= count($movimientos) ?> movimiento<?= count($movimientos) === 1 ? '' : 's' ?></span>
    </div>
    <?php if (empty($movimientos)): ?>
    <div class="vacio">No hubo ingresos en el período.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Proveedor</th>
                <th>Comprobante</th>
                <th>Tipo</th>
                <th class="r">Unidades</th>
                <th class="r">Total venta</th>
                <th class="r">Ganancia</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $m): ?>
            <tr>
                <td style="white-space:nowrap;"><?= date('d/m/Y g:i a', strtotime($m['created_at'])) ?></td>
                <td><?= $e($m['usuario_nombre']) ?></td>
                <td><?= $e(trim((string)$m['proveedor']) ?: '—') ?></td>
                <td class="fac"><?= $e($m['comprobante']) ?></td>
                <td><?= $tipos[$m['tipo']]['label'] ?></td>
                <td class="r"><?= (int)$m['unidades'] ?></td>
                <td class="r"><?= $money($m['venta']) ?></td>
                <td class="r"><?= $money($m['ganancia']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="r">Total<?= $tipoSel === '' ? ' (sin transferencias enviadas)' : '' ?></td>
                <td class="r"><?= $money($movTotal) ?></td>
                <td class="r"><?= $money($movGanancia) ?></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
    <?php else: ?>
    <!-- Por producto -->
    <div class="seccion">
        <span class="seccion-titulo">Productos ingresados</span>
        <span class="seccion-info"><?= count($articulos) ?> producto<?= count($articulos) === 1 ? '' : 's' ?> · <?= (int)$artUnidades ?> unidades</span>
    </div>
    <?php if (empty($articulos)): ?>
    <div class="vacio">No ingresaron productos en el período.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Ingreso · Registró</th>
                <th class="r">P. Venta</th>
                <th class="r">Cantidad</th>
                <th class="r">Total venta</th>
                <th class="r">Ganancia</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articulos as $i => $a): ?>
            <tr>
                <td class="num"><?= $i + 1 ?></td>
                <td class="cod"><?= $e($a['codigo'] ?: '—') ?></td>
                <td class="desc"><?= $e($a['nombre']) ?></td>
                <td class="fac">
                    <?php foreach ($a['ingresos'] as $in): ?>
                    <div><?= $e($in['radicado']) ?> <span>· <?= $e($in['usuario']) ?></span></div>
                    <?php endforeach; ?>
                </td>
                <td class="r"><?= $money($a['precio_venta']) ?></td>
                <td class="r"><?= (int)$a['cantidad'] ?> <?= $e($a['unidad'] ?? '') ?></td>
                <td class="r"><?= $money($a['venta']) ?></td>
                <td class="r"><?= $money($a['ganancia']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="r">Total de productos ingresados</td>
                <td class="r"><?= (int)$artUnidades ?></td>
                <td class="r"><?= $money($artTotal) ?></td>
                <td class="r"><?= $money($artGanancia) ?></td>
            </tr>
        </tfoot>
    </table>
    <div class="notas">Valores al precio de venta actual de cada producto. Ganancia proyectada = total venta − lo que costó la mercancía en el ingreso.<br>Compras y transferencias recibidas; las transferencias enviadas no se cuentan en esta lista.</div>
    <?php endif; ?>
    <?php endif; /* vista */ ?>

    <div class="rpt-footer">
        <span>PetControl &mdash; <?= $e($sucursal_nombre) ?></span>
        <span>Reporte de ingresos · <?= $periodo ?></span>
    </div>

</body>
<script>window.onload = function(){ window.print(); }</script>
</html>
