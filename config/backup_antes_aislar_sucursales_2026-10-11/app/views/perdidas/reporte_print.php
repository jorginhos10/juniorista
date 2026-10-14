<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pérdidas — <?= htmlspecialchars($sucursal_nombre) ?></title>
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
            border-bottom: 2px solid #dc2626;
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
        .rpt-meta-suc   { font-size: 12px; color: #dc2626; font-weight: 600; margin: 2px 0; }
        .rpt-meta-info  { font-size: 10px; color: #9ca3af; }

        /* ── Período ── */
        .rpt-periodo {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
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
            margin-bottom: 20px;
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
        .rpt-box.red    { border-color: #fecaca; background: #fef2f2; }
        .rpt-box.red    .rpt-box-value { color: #dc2626; }
        .rpt-box.orange { border-color: #fed7aa; background: #fff7ed; }
        .rpt-box.orange .rpt-box-value { color: #c2410c; }
        .rpt-box.blue   { border-color: #bfdbfe; background: #eff6ff; }
        .rpt-box.blue   .rpt-box-value { color: #1d4ed8; }

        /* ── Motivos ── */
        .rpt-motivos {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .rpt-motivo-box {
            border: 1px solid #e8eaf0;
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
        }
        .rpt-motivo-value { font-size: 18px; font-weight: 800; color: #1e1b4b; }
        .rpt-motivo-label { font-size: 9px; color: #6b7280; text-transform: uppercase;
                            letter-spacing: .05em; margin-top: 2px; }
        .motivo-perdida     { border-color: #fecaca; background: #fef2f2; }
        .motivo-vencimiento { border-color: #fde68a; background: #fffbeb; }
        .motivo-bodega      { border-color: #bae6fd; background: #f0f9ff; }
        .motivo-propietario { border-color: #e5e7eb; background: #f9fafb; }

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
            background: #7f1d1d;
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
        tbody td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; }
        tbody td.r { text-align: right; }
        tbody td.c { text-align: center; }
        .badge-motivo {
            border-radius: 4px; padding: 2px 6px; font-size: 10px; font-weight: 700;
        }
        .badge-perdida     { background: #fef2f2; color: #dc2626; }
        .badge-vencimiento { background: #fffbeb; color: #92400e; }
        .badge-a_bodega    { background: #f0f9ff; color: #1d4ed8; }
        .badge-propietario { background: #f9fafb; color: #374151; }
        tfoot td {
            padding: 9px 10px;
            font-weight: 700;
            background: #fef2f2;
            border-top: 2px solid #dc2626;
            font-size: 12px;
        }
        tfoot td.r { text-align: right; color: #dc2626; }

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
            <div class="rpt-meta-title">Reporte de Pérdidas</div>
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

    <!-- Resumen general -->
    <div class="rpt-summary">
        <div class="rpt-box red">
            <div class="rpt-box-icon">&#9651;</div>
            <div class="rpt-box-value"><?= $resumen['total_perdidas'] ?? 0 ?></div>
            <div class="rpt-box-label">Total registros</div>
        </div>
        <div class="rpt-box orange">
            <div class="rpt-box-icon">&#9632;</div>
            <div class="rpt-box-value"><?= number_format($resumen['total_unidades'] ?? 0) ?></div>
            <div class="rpt-box-label">Unidades perdidas</div>
        </div>
        <div class="rpt-box blue">
            <div class="rpt-box-icon">$</div>
            <div class="rpt-box-value">$<?= number_format($resumen['valor_total'] ?? 0, 2) ?></div>
            <div class="rpt-box-label">Valor total</div>
        </div>
    </div>

    <!-- Desglose por motivo -->
    <div class="rpt-motivos">
        <div class="rpt-motivo-box motivo-perdida">
            <div class="rpt-motivo-value"><?= $resumen['por_perdida'] ?? 0 ?></div>
            <div class="rpt-motivo-label">Pérdida</div>
        </div>
        <div class="rpt-motivo-box motivo-vencimiento">
            <div class="rpt-motivo-value"><?= $resumen['por_vencimiento'] ?? 0 ?></div>
            <div class="rpt-motivo-label">Vencimiento</div>
        </div>
        <div class="rpt-motivo-box motivo-bodega">
            <div class="rpt-motivo-value"><?= $resumen['por_bodega'] ?? 0 ?></div>
            <div class="rpt-motivo-label">A Bodega</div>
        </div>
        <div class="rpt-motivo-box motivo-propietario">
            <div class="rpt-motivo-value"><?= $resumen['por_propietario'] ?? 0 ?></div>
            <div class="rpt-motivo-label">Propietario</div>
        </div>
    </div>

    <!-- Detalle -->
    <div class="rpt-table-title">Detalle de pérdidas</div>

    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th style="width:110px;">Motivo</th>
                <th>Responsable</th>
                <th class="c" style="width:60px;">Prods.</th>
                <th class="c" style="width:70px;">Unidades</th>
                <th style="max-width:140px;">Notas</th>
                <th class="r" style="width:90px;">Valor</th>
                <th class="c" style="width:90px;">Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($perdidas)): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:20px; color:#9ca3af;">
                    No hay pérdidas en el período seleccionado.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($perdidas as $i => $p): ?>
            <?php $m = $motivos[$p['motivo']] ?? ['label' => $p['motivo']]; ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
                    <span class="badge-motivo badge-<?= htmlspecialchars($p['motivo']) ?>">
                        <?= $m['label'] ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($p['responsable'] ?: ($p['responsable_nombre'] ?? '—')) ?></td>
                <td class="c"><?= $p['total_lineas'] ?></td>
                <td class="c"><?= number_format($p['total_unidades'] ?? 0) ?></td>
                <td style="max-width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= htmlspecialchars($p['notas'] ?: '—') ?>
                </td>
                <td class="r" style="font-weight:700;">$<?= number_format($p['total'], 2) ?></td>
                <td class="c">
                    <?= date('d/m/Y', strtotime($p['created_at'])) ?><br>
                    <span style="color:#9ca3af;"><?= date('H:i', strtotime($p['created_at'])) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($perdidas)): ?>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align:right;">Total del período (<?= $resumen['total_perdidas'] ?? 0 ?> registros)</td>
                <td class="r">$<?= number_format($resumen['valor_total'] ?? 0, 2) ?></td>
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
