<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas por Unidad — <?= htmlspecialchars($sucursal_nombre) ?></title>
    <?php require_once ROOT . '/app/models/VeterinariaModel.php'; ?>
    <?php $iniciales = VeterinariaModel::generarIniciales($sucursal_nombre); ?>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI', Arial, sans-serif;
            font-size:11px; color:#1f2937; background:#fff; padding:32px 40px;
        }

        .rpt-header {
            display:flex; justify-content:space-between; align-items:flex-start;
            padding-bottom:16px; border-bottom:2px solid #4f46e5; margin-bottom:18px;
        }
        .rpt-logo { display:flex; align-items:center; gap:10px; }
        .rpt-logo-icon {
            width:42px; height:42px;
            background:linear-gradient(135deg,#1e1b4b,#4f46e5);
            border-radius:10px; display:flex; align-items:center;
            justify-content:center; color:#fff; font-size:20px;
        }
        .rpt-logo-name { font-size:20px; font-weight:800; color:#1e1b4b; }
        .rpt-logo-sub  { font-size:10px; color:#6b7280; letter-spacing:.05em; text-transform:uppercase; }
        .rpt-meta { text-align:right; }
        .rpt-meta-title { font-size:15px; font-weight:700; color:#1e1b4b; }
        .rpt-meta-suc   { font-size:12px; color:#4f46e5; font-weight:600; margin:2px 0; }
        .rpt-meta-info  { font-size:10px; color:#9ca3af; }

        .rpt-periodo {
            background:#f5f3ff; border-left:4px solid #4f46e5;
            border-radius:0 6px 6px 0; padding:10px 16px;
            margin-bottom:18px; display:flex; align-items:center; gap:16px;
        }
        .rpt-periodo-label { font-size:10px; font-weight:700; text-transform:uppercase;
                             letter-spacing:.06em; color:#6b7280; }
        .rpt-periodo-value { font-size:13px; font-weight:700; color:#1e1b4b; }
        .rpt-prefijo { margin-left:auto; text-align:right; }
        .rpt-prefijo-label { font-size:10px; color:#6b7280; font-weight:600; text-transform:uppercase; }
        .rpt-prefijo-value {
            font-family:monospace; font-size:14px; font-weight:800;
            color:#4f46e5; background:#eef2ff; padding:2px 10px;
            border-radius:4px; border:1px solid #c7d2fe;
        }

        .rpt-summary {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:10px; margin-bottom:20px;
        }
        .rpt-box {
            border:1px solid #e8eaf0; border-radius:8px;
            padding:12px; text-align:center;
        }
        .rpt-box-value { font-size:18px; font-weight:800; color:#1e1b4b; }
        .rpt-box-label { font-size:9px; color:#9ca3af; font-weight:600;
                         text-transform:uppercase; letter-spacing:.05em; margin-top:3px; }
        .rpt-box.blue  { border-color:#bfdbfe; background:#eff6ff; }
        .rpt-box.blue .rpt-box-value  { color:#1d4ed8; }
        .rpt-box.green { border-color:#bbf7d0; background:#f0fdf4; }
        .rpt-box.green .rpt-box-value { color:#15803d; }
        .rpt-box.orange{ border-color:#fed7aa; background:#fff7ed; }
        .rpt-box.orange .rpt-box-value{ color:#c2410c; }
        .rpt-box.purple{ border-color:#ddd6fe; background:#f5f3ff; }
        .rpt-box.purple .rpt-box-value{ color:#6d28d9; }

        .rpt-table-title {
            font-size:11px; font-weight:700; color:#1e1b4b;
            text-transform:uppercase; letter-spacing:.06em;
            margin-bottom:8px; padding-bottom:6px;
            border-bottom:1px solid #e8eaf0;
        }

        table { width:100%; border-collapse:collapse; font-size:10px; }
        thead th {
            background:#1e1b4b; color:#fff; padding:7px 8px;
            text-align:left; font-weight:600; font-size:9px;
            text-transform:uppercase; letter-spacing:.04em;
        }
        thead th.r { text-align:right; }
        thead th.c { text-align:center; }
        tbody tr:nth-child(even) { background:#f9fafb; }
        tbody td { padding:6px 8px; border-bottom:1px solid #f3f4f6; }
        tbody td.r { text-align:right; }
        tbody td.c { text-align:center; }

        .serial {
            font-family:monospace; font-size:9px; font-weight:700;
            background:#eef2ff; color:#4f46e5;
            border:1px solid #c7d2fe; border-radius:3px;
            padding:1px 5px;
        }

        tfoot td {
            padding:8px; font-weight:700; font-size:11px;
            background:#f5f3ff; border-top:2px solid #4f46e5;
        }
        tfoot td.r { text-align:right; color:#15803d; }
        tfoot td.c { text-align:center; }

        .rpt-footer {
            margin-top:20px; padding-top:10px;
            border-top:1px solid #e8eaf0;
            display:flex; justify-content:space-between;
            font-size:9px; color:#9ca3af;
        }

        @media print {
            body { padding:0; }
            @page { margin:12mm 12mm 10mm 12mm; size:A4 landscape; }
        }
    </style>
</head>
<body>

    <div class="rpt-header">
        <div class="rpt-logo">
            <div class="rpt-logo-icon">&#9829;</div>
            <div>
                <div class="rpt-logo-name">PetControl</div>
                <div class="rpt-logo-sub">Sistema de gestión veterinaria</div>
            </div>
        </div>
        <div class="rpt-meta">
            <div class="rpt-meta-title">Reporte — Ventas por Unidad</div>
            <div class="rpt-meta-suc"><?= htmlspecialchars($sucursal_nombre) ?></div>
            <div class="rpt-meta-info">
                Generado: <?= date('d/m/Y H:i') ?> &bull; Por: <?= htmlspecialchars($usuario['nombre']) ?>
            </div>
        </div>
    </div>

    <div class="rpt-periodo">
        <div>
            <div class="rpt-periodo-label">Período</div>
            <div class="rpt-periodo-value">
                <?php if ($desde === $hasta): ?>
                    <?= date('d \d\e F \d\e Y', strtotime($desde)) ?>
                <?php else: ?>
                    <?= date('d/m/Y', strtotime($desde)) ?> &nbsp;→&nbsp; <?= date('d/m/Y', strtotime($hasta)) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="rpt-prefijo">
            <div class="rpt-prefijo-label">Prefijo de serial</div>
            <div class="rpt-prefijo-value"><?= $iniciales ?>-DDMMAAAA-0001</div>
        </div>
    </div>

    <div class="rpt-summary">
        <div class="rpt-box blue">
            <div class="rpt-box-value"><?= number_format($resumen['total_unidades'] ?? 0) ?></div>
            <div class="rpt-box-label">Unidades vendidas</div>
        </div>
        <div class="rpt-box green">
            <div class="rpt-box-value">$<?= number_format($resumen['total_ingresos'] ?? 0, 2) ?></div>
            <div class="rpt-box-label">Total ingresos</div>
        </div>
        <div class="rpt-box orange">
            <div class="rpt-box-value"><?= $resumen['total_ventas'] ?? 0 ?></div>
            <div class="rpt-box-label">Ventas involucradas</div>
        </div>
        <div class="rpt-box purple">
            <div class="rpt-box-value"><?= $resumen['productos_distintos'] ?? 0 ?></div>
            <div class="rpt-box-label">Productos distintos</div>
        </div>
    </div>

    <div class="rpt-table-title">Detalle de unidades vendidas</div>

    <table>
        <thead>
            <tr>
                <th style="width:100px;">N° Venta</th>
                <th>Producto</th>
                <th class="c" style="width:50px;">Cant.</th>
                <th class="c" style="width:55px;">Unidad</th>
                <th class="r" style="width:70px;">P. Unit.</th>
                <th class="r" style="width:75px;">Subtotal</th>
                <th style="width:110px;">Vendedor</th>
                <th style="width:110px;">Cliente</th>
                <th class="c" style="width:85px;">Fecha</th>
                <th class="c" style="width:45px;">Hora</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lineas)): ?>
            <tr>
                <td colspan="10" style="text-align:center; padding:20px; color:#9ca3af;">
                    No hay ventas en el período seleccionado.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($lineas as $l): ?>
            <?php
                $serial   = VeterinariaModel::serialVenta($sucursal_nombre, $l['venta_id'], $l['created_at']);
                $subtotal = ($l['cantidad'] * $l['precio_unitario']) - ($l['desc_linea'] ?? 0);
            ?>
            <tr>
                <td><span class="serial"><?= $serial ?></span></td>
                <td>
                    <?= htmlspecialchars($l['producto_nombre']) ?>
                    <?php if ($l['producto_codigo']): ?>
                    <span style="color:#9ca3af; font-size:9px;"> · <?= htmlspecialchars($l['producto_codigo']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="c" style="font-weight:700;"><?= $l['cantidad'] ?></td>
                <td class="c" style="color:#6b7280;"><?= htmlspecialchars($l['unidad']) ?></td>
                <td class="r">$<?= number_format($l['precio_unitario'], 2) ?></td>
                <td class="r" style="font-weight:700;">$<?= number_format($subtotal, 2) ?></td>
                <td><?= htmlspecialchars($l['vendedor_nombre'] ?? 'Sistema') ?></td>
                <td><?= htmlspecialchars(trim($l['cliente_nombre']) ?: 'General') ?></td>
                <td class="c"><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
                <td class="c"><?= date('H:i', strtotime($l['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($lineas)): ?>
        <tfoot>
            <tr>
                <td colspan="2" style="padding-left:8px;">Total del período</td>
                <td class="c"><?= number_format($resumen['total_unidades'] ?? 0) ?></td>
                <td colspan="2"></td>
                <td class="r">$<?= number_format($resumen['total_ingresos'] ?? 0, 2) ?></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <div class="rpt-footer">
        <span>PetControl &mdash; <?= htmlspecialchars($sucursal_nombre) ?> &mdash; Prefijo: <?= $iniciales ?></span>
        <span>Reporte generado el <?= date('d/m/Y \a \l\a\s H:i') ?></span>
    </div>

</body>
<script>window.onload = function(){ window.print(); }</script>
</html>
