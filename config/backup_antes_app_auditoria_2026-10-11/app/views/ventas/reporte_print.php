<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas — <?= htmlspecialchars($sucursal_nombre) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            background: #fff;
            padding: 32px 40px;
        }

        /* ── Encabezado ── */
        .rpt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 18px;
            border-bottom: 2px solid #4f46e5;
            margin-bottom: 20px;
        }
        .rpt-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .rpt-logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, #1e1b4b, #4f46e5);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 20px;
        }
        .rpt-logo-name  { font-size: 20px; font-weight: 800; color: #1e1b4b; }
        .rpt-logo-sub   { font-size: 10px; color: #6b7280; letter-spacing: .05em; text-transform: uppercase; }
        .rpt-meta       { text-align: right; }
        .rpt-meta-title { font-size: 16px; font-weight: 700; color: #1e1b4b; }
        .rpt-meta-suc   { font-size: 12px; color: #4f46e5; font-weight: 600; margin: 2px 0; }
        .rpt-meta-info  { font-size: 10px; color: #9ca3af; }

        /* ── Período ── */
        .rpt-periodo {
            background: #f5f3ff;
            border-left: 4px solid #4f46e5;
            border-radius: 0 6px 6px 0;
            padding: 10px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .rpt-periodo-label { font-size: 10px; font-weight: 700; text-transform: uppercase;
                             letter-spacing: .06em; color: #6b7280; }
        .rpt-periodo-value { font-size: 13px; font-weight: 700; color: #1e1b4b; }

        /* ── Resumen ── */
        .rpt-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .rpt-box {
            border: 1px solid #e8eaf0;
            border-radius: 8px;
            padding: 14px 16px;
            text-align: center;
        }
        .rpt-box-icon  { font-size: 20px; margin-bottom: 6px; }
        .rpt-box-value { font-size: 22px; font-weight: 800; color: #1e1b4b; line-height: 1.1; }
        .rpt-box-label { font-size: 10px; color: #9ca3af; font-weight: 600;
                         text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }
        .rpt-box.green { border-color: #bbf7d0; background: #f0fdf4; }
        .rpt-box.green .rpt-box-value { color: #15803d; }
        .rpt-box.blue  { border-color: #bfdbfe; background: #eff6ff; }
        .rpt-box.blue  .rpt-box-value { color: #1d4ed8; }
        .rpt-box.red   { border-color: #fecaca; background: #fef2f2; }
        .rpt-box.red   .rpt-box-value { color: #dc2626; }

        /* ── Balance ── */
        .rpt-balance { width: 60%; margin: 0 0 24px auto; border-collapse: collapse; }
        .rpt-balance td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; font-size: 12px; }
        .rpt-balance .r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .rpt-balance .mas   { color: #15803d; }
        .rpt-balance .menos { color: #dc2626; }
        .rpt-balance .sub td   { font-weight: 700; background: #f9fafb; }
        .rpt-balance .final td { font-weight: 800; font-size: 14px; border-top: 2px solid #1e1b4b; border-bottom: none; }
        .rpt-balance .muted { color: #9ca3af; font-size: 10px; }

        /* ── Tabla ── */
        .rpt-table-title {
            font-size: 12px; font-weight: 700; color: #1e1b4b;
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e8eaf0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        thead th {
            background: #1e1b4b;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        thead th.r { text-align: right; }
        thead th.c { text-align: center; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr.anulada { background: #fef2f2; color: #9ca3af; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; }
        tbody td.r { text-align: right; }
        tbody td.c { text-align: center; }
        .badge-ok  { background: #dcfce7; color: #15803d; border-radius: 4px; padding: 2px 7px; font-size: 10px; font-weight: 700; }
        .badge-off { background: #fee2e2; color: #dc2626; border-radius: 4px; padding: 2px 7px; font-size: 10px; font-weight: 700; }
        tfoot td {
            padding: 9px 10px;
            font-weight: 700;
            background: #f5f3ff;
            border-top: 2px solid #4f46e5;
            font-size: 12px;
        }
        tfoot td.r { text-align: right; color: #15803d; }

        /* ── Pie de página ── */
        .rpt-footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e8eaf0;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #9ca3af;
        }

        @media print {
            body { padding: 0; }
            @page { margin: 15mm 15mm 12mm 15mm; size: A4; }
        }
    </style>
</head>
<body>

    <!-- Encabezado -->
    <div class="rpt-header">
        <div class="rpt-logo">
            <div class="rpt-logo-icon">&#9829;</div>
            <div>
                <div class="rpt-logo-name">PetControl</div>
                <div class="rpt-logo-sub">Sistema de gestión veterinaria</div>
            </div>
        </div>
        <div class="rpt-meta">
            <div class="rpt-meta-title">Reporte de Ventas</div>
            <div class="rpt-meta-suc"><?= htmlspecialchars($sucursal_nombre) ?></div>
            <div class="rpt-meta-info">Generado: <?= date('d/m/Y H:i') ?> &bull; Por: <?= htmlspecialchars($usuario['nombre']) ?></div>
        </div>
    </div>

    <!-- Período -->
    <div class="rpt-periodo">
        <div>
            <div class="rpt-periodo-label">Período del reporte</div>
            <div class="rpt-periodo-value">
                <?php if ($desde === $hasta): ?>
                    <?= date('d \d\e F \d\e Y', strtotime($desde)) ?>
                <?php else: ?>
                    <?= date('d/m/Y', strtotime($desde)) ?> &nbsp;→&nbsp; <?= date('d/m/Y', strtotime($hasta)) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Resumen -->
    <div class="rpt-summary">
        <div class="rpt-box green">
            <div class="rpt-box-icon">&#10003;</div>
            <div class="rpt-box-value"><?= $resumen['ventas_completadas'] ?? 0 ?></div>
            <div class="rpt-box-label">Ventas completadas</div>
        </div>
        <div class="rpt-box blue">
            <div class="rpt-box-icon">$</div>
            <div class="rpt-box-value">$<?= number_format($resumen['total_ingresos'] ?? 0, 2) ?></div>
            <div class="rpt-box-label">Total vendido</div>
        </div>
        <div class="rpt-box red">
            <div class="rpt-box-icon">&#10005;</div>
            <div class="rpt-box-value"><?= $resumen['ventas_anuladas'] ?? 0 ?></div>
            <div class="rpt-box-label">Ventas canceladas</div>
        </div>
    </div>

    <!-- Detalle -->
    <!-- Balance -->
    <div class="rpt-table-title">Balance del período</div>
    <table class="rpt-balance">
        <tr>
            <td>Ventas <span class="muted">(<?= $resumen['ventas_completadas'] ?? 0 ?> ventas)</span></td>
            <td class="r mas">+ $<?= number_format($balance['ventas'], 2) ?></td>
        </tr>
        <tr>
            <td>Peluquería <span class="muted">(<?= $balance['peluqueria']['cantidad'] ?> servicios)</span></td>
            <td class="r mas">+ $<?= number_format($balance['peluqueria']['total'], 2) ?></td>
        </tr>
        <tr>
            <td>Tratamientos — abonos recibidos <span class="muted">(<?= $balance['tratamientos']['cantidad'] ?> abonos)</span></td>
            <td class="r mas">+ $<?= number_format($balance['tratamientos']['total'], 2) ?></td>
        </tr>
        <tr class="sub">
            <td>Total ingresos</td>
            <td class="r">$<?= number_format($balance['ingresos'], 2) ?></td>
        </tr>
        <tr>
            <td>Gastos <span class="muted">(<?= $balance['gastos']['cantidad'] ?> registros)</span></td>
            <td class="r menos">− $<?= number_format($balance['gastos']['total'], 2) ?></td>
        </tr>
        <tr class="final">
            <td><?= $balance['utilidad'] >= 0 ? 'Utilidad neta' : 'Pérdida neta' ?></td>
            <td class="r <?= $balance['utilidad'] >= 0 ? 'mas' : 'menos' ?>"><?= $balance['utilidad'] < 0 ? '−' : '' ?>$<?= number_format(abs($balance['utilidad']), 2) ?></td>
        </tr>
    </table>

    <div class="rpt-table-title">Detalle de ventas</div>

    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th class="c" style="width:60px;">Prods.</th>
                <th class="c" style="width:80px;">Estado</th>
                <th class="r" style="width:80px;">Descuento</th>
                <th class="r" style="width:90px;">Total</th>
                <th class="c" style="width:110px;">Fecha y hora</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ventas)): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:20px; color:#9ca3af;">
                    No hay ventas en el período seleccionado.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($ventas as $i => $v): ?>
            <?php $anulada = $v['estado'] === 'anulada'; ?>
            <tr class="<?= $anulada ? 'anulada' : '' ?>">
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($v['cliente_nombre'] ?: 'Cliente general') ?></td>
                <td><?= htmlspecialchars($v['vendedor_nombre'] ?? 'Sistema') ?></td>
                <td class="c"><?= $v['total_lineas'] ?></td>
                <td class="c">
                    <?php if ($anulada): ?>
                    <span class="badge-off">Cancelada</span>
                    <?php else: ?>
                    <span class="badge-ok">Completada</span>
                    <?php endif; ?>
                </td>
                <td class="r"><?= $v['descuento'] > 0 ? '-$' . number_format($v['descuento'], 2) : '—' ?></td>
                <td class="r" style="<?= $anulada ? 'text-decoration:line-through;' : 'font-weight:700;' ?>">
                    $<?= number_format($v['total'], 2) ?>
                </td>
                <td class="c">
                    <?= date('d/m/Y', strtotime($v['created_at'])) ?><br>
                    <span style="color:#9ca3af;"><?= date('H:i', strtotime($v['created_at'])) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($ventas)): ?>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align:right;">Total del período (<?= $resumen['ventas_completadas'] ?? 0 ?> ventas)</td>
                <td class="r">$<?= number_format($resumen['total_ingresos'] ?? 0, 2) ?></td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <!-- Pie -->
    <div class="rpt-footer">
        <span>PetControl &mdash; <?= htmlspecialchars($sucursal_nombre) ?></span>
        <span>Reporte generado el <?= date('d/m/Y \a \l\a\s H:i') ?></span>
    </div>

</body>
<script>window.onload = function(){ window.print(); }</script>
</html>
