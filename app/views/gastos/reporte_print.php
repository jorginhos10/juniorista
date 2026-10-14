<?php
$totalPeriodo = array_sum(array_column($gastos, 'monto'));
$balance      = $ventasPeriodo - $totalPeriodo;
$money = fn($n) => '$' . number_format((float)$n, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Gastos — <?= htmlspecialchars($sucursal_nombre) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; padding: 32px 40px; }

        .rpt-header { display: flex; justify-content: space-between; align-items: flex-start;
                      padding-bottom: 18px; border-bottom: 2px solid #ea580c; margin-bottom: 20px; }
        .rpt-logo { display: flex; align-items: center; gap: 10px; }
        .rpt-logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, #1e1b4b, #4f46e5);
                         border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; }
        .rpt-logo-name { font-size: 20px; font-weight: 800; color: #1e1b4b; }
        .rpt-logo-sub  { font-size: 10px; color: #6b7280; letter-spacing: .05em; text-transform: uppercase; }
        .rpt-meta { text-align: right; }
        .rpt-meta-title { font-size: 16px; font-weight: 700; color: #1e1b4b; }
        .rpt-meta-suc   { font-size: 12px; color: #ea580c; font-weight: 600; margin: 2px 0; }
        .rpt-meta-info  { font-size: 10px; color: #9ca3af; }

        .rpt-periodo { background: #fff7ed; border-left: 4px solid #ea580c; border-radius: 0 6px 6px 0;
                       padding: 10px 16px; margin-bottom: 20px; display: flex; gap: 24px; flex-wrap: wrap; }
        .rpt-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }
        .rpt-value { font-size: 13px; font-weight: 700; color: #1e1b4b; }

        .rpt-resumen { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 22px; }
        .rpt-box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 14px; }
        .rpt-box .v { font-size: 18px; font-weight: 800; margin-top: 2px; }

        h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        th { background: #f9fafb; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .05em;
             color: #6b7280; padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
        td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        tfoot td { font-weight: 800; border-top: 2px solid #e5e7eb; border-bottom: none; }
        .muted { color: #9ca3af; font-size: 10px; }
        .neg { color: #dc2626; } .pos { color: #16a34a; }

        .rpt-firma { display: flex; justify-content: space-around; margin-top: 50px; }
        .rpt-firma div { width: 200px; border-top: 1px solid #9ca3af; text-align: center; padding-top: 6px; font-size: 10px; color: #6b7280; }

        .no-print { position: fixed; top: 16px; right: 16px; display: flex; gap: 8px; }
        .no-print button { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="background:#4f46e5;color:#fff">Imprimir</button>
    <button onclick="window.close()" style="background:#e5e7eb">Cerrar</button>
</div>

<div class="rpt-header">
    <div class="rpt-logo">
        <div class="rpt-logo-icon">♥</div>
        <div>
            <div class="rpt-logo-name">PetControl</div>
            <div class="rpt-logo-sub">Sistema veterinario</div>
        </div>
    </div>
    <div class="rpt-meta">
        <div class="rpt-meta-title">Reporte de Gastos</div>
        <div class="rpt-meta-suc"><?= htmlspecialchars($sucursal_nombre) ?></div>
        <div class="rpt-meta-info">Generado el <?= date('d/m/Y H:i') ?> por <?= htmlspecialchars($usuario['nombre']) ?></div>
    </div>
</div>

<div class="rpt-periodo">
    <div>
        <div class="rpt-label">Período</div>
        <div class="rpt-value"><?= date('d/m/Y', strtotime($desde)) ?> — <?= date('d/m/Y', strtotime($hasta)) ?></div>
    </div>
</div>

<div class="rpt-resumen">
    <div class="rpt-box">
        <div class="rpt-label">Total gastos</div>
        <div class="v neg"><?= $money($totalPeriodo) ?></div>
    </div>
    <div class="rpt-box">
        <div class="rpt-label">Ingresos (ventas, peluquería, laboratorios y abonos)</div>
        <div class="v"><?= $money($ventasPeriodo) ?></div>
    </div>
    <div class="rpt-box">
        <div class="rpt-label">Balance (ingresos − gastos)</div>
        <div class="v <?= $balance >= 0 ? 'pos' : 'neg' ?>"><?= ($balance < 0 ? '-' : '') . $money(abs($balance)) ?></div>
    </div>
</div>

<h3>Detalle de gastos</h3>
<table>
    <thead>
        <tr><th>Fecha</th><th>Descripción</th><th>Registrado por</th><th class="r">Monto</th></tr>
    </thead>
    <tbody>
        <?php if (empty($gastos)): ?>
        <tr><td colspan="4" class="muted" style="text-align:center">Sin gastos en el período.</td></tr>
        <?php endif; ?>
        <?php foreach ($gastos as $g): ?>
        <tr>
            <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($g['fecha'])) ?></td>
            <td><?= htmlspecialchars($g['descripcion']) ?><?php if ($g['notas']): ?><div class="muted"><?= htmlspecialchars($g['notas']) ?></div><?php endif; ?></td>
            <td><?= htmlspecialchars($g['usuario_nombre'] ?? '—') ?></td>
            <td class="r"><?= $money($g['monto']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="3">Total (<?= count($gastos) ?> registros)</td><td class="r"><?= $money($totalPeriodo) ?></td></tr></tfoot>
</table>

<div class="rpt-firma">
    <div>Elaborado por</div>
    <div>Revisado por</div>
</div>

</body>
</html>
