<?php
// Acta de descargo de inventario: los productos que se descargaron juntos, con su motivo y fecha
$e     = fn($v) => htmlspecialchars((string)$v);
$money = fn($n) => '$' . number_format((float)$n, 2);
$c     = $items[0];
$m     = $motivos[$c['motivo']] ?? ['label' => ucfirst($c['motivo'])];
$totalU = array_sum(array_map(fn($i) => (int)$i['cantidad'], $items));
$totalV = array_sum(array_map(fn($i) => (float)$i['subtotal'], $items));
$meses  = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$larga  = fn(string $f) => (int)date('j', strtotime($f)) . ' de ' . $meses[date('n', strtotime($f)) - 1] . ' de ' . date('Y', strtotime($f));
// Con descargos de varios días, la fecha es el rango
$fechas = array_column($items, 'fecha');
$fechaLarga = min($fechas) === max($fechas) ? $larga(min($fechas)) : 'del ' . $larga(min($fechas)) . ' al ' . $larga(max($fechas));
$personas = implode(', ', array_unique(array_filter(array_column($items, 'usuario_nombre')))) ?: '—';
$variosMotivos = count(array_unique(array_column($items, 'motivo'))) > 1 || count(array_unique(array_column($items, 'motivo_detalle'))) > 1;
$nProd  = count($items);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de descargo <?= $e($numero) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #f1f5f9; padding: 24px; }
        .hoja { max-width: 720px; margin: 0 auto; background: #fff; padding: 28px 34px; border-radius: 8px; box-shadow: 0 2px 12px rgba(15,23,42,.08); }
        .hdr { display: flex; justify-content: space-between; gap: 16px; padding-bottom: 12px; border-bottom: 2px solid #b91c1c; }
        .empresa { display: flex; align-items: center; gap: 12px; }
        .empresa-logo { width: 60px; height: 60px; object-fit: contain; }
        .empresa-nombre { font-size: 16px; font-weight: 800; color: #1f2937; }
        .empresa-datos { font-size: 10px; color: #6b7280; margin-top: 3px; line-height: 1.5; }
        .doc { text-align: right; }
        .doc-tipo { font-size: 15px; font-weight: 800; letter-spacing: .05em; color: #b91c1c; text-transform: uppercase; }
        .doc-num { font-family: Consolas, monospace; font-size: 12px; font-weight: 700; }
        .doc-fecha { font-size: 10px; color: #6b7280; }

        .datos { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid #e5e7eb; border-radius: 6px; margin-top: 14px; }
        .datos > div { padding: 8px 10px; }
        .datos > div + div { border-left: 1px solid #e5e7eb; }
        .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 3px; }
        .motivo { margin-top: 10px; border: 1px solid #fecaca; background: #fef2f2; border-radius: 6px; padding: 8px 10px; font-size: 12px;
                  -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; padding: 6px 8px;
             background: #f9fafb; border-bottom: 1px solid #e5e7eb; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; }
        .r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .c { text-align: center; }
        .cod { font-family: Consolas, monospace; font-size: 10.5px; color: #4b5563; }
        tfoot td { font-weight: 800; border-top: 2px solid #1f2937; border-bottom: none; }

        .fecha-acta { margin-top: 16px; font-size: 13px; font-weight: 700; }
        .fecha-acta .lbl { display: inline-block; margin-right: 8px; }
        .texto { font-size: 12px; color: #1f2937; margin-top: 10px; line-height: 1.6; text-align: justify; }
        .det { font-size: 10.5px; color: #4b5563; }
        td { vertical-align: top; }
        .nota { font-size: 9.5px; color: #9ca3af; margin-top: 4px; }
        .firmas { display: flex; gap: 30px; margin-top: 50px; }
        .firmas div { flex: 1; border-top: 1px solid #111827; text-align: center; padding-top: 5px; font-size: 11px; font-weight: 700; }
        .firmas small { display: block; font-weight: 400; color: #6b7280; }
        .pie { margin-top: 18px; text-align: center; font-size: 9px; color: #9ca3af; }
        .no-print { max-width: 720px; margin: 0 auto 12px; display: flex; justify-content: flex-end; gap: 8px; }
        .no-print button { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        @media print {
            body { background: #fff; padding: 0; }
            .hoja { box-shadow: none; border-radius: 0; max-width: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="background:#b91c1c;color:#fff">Imprimir</button>
    <button onclick="window.close()" style="background:#e5e7eb">Cerrar</button>
</div>

<div class="hoja">
    <div class="hdr">
        <div class="empresa">
            <?php if (!empty($empresa['logo'])): ?><img class="empresa-logo" src="<?= BASE_URL ?>/<?= $e($empresa['logo']) ?>" alt=""><?php endif; ?>
            <div>
                <div class="empresa-nombre"><?= $e($empresa['nombre']) ?></div>
                <div class="empresa-datos">
                    <?= $empresa['ruc'] ? 'NIT: ' . $e($empresa['ruc']) . '<br>' : '' ?>
                    <?= $empresa['direccion'] ? $e($empresa['direccion']) : '' ?>
                </div>
            </div>
        </div>
        <div class="doc">
            <div class="doc-tipo">Acta de descargo</div>
            <div class="doc-num"><?= $e($numero) ?></div>
            <div class="doc-fecha">Registrada el <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></div>
        </div>
    </div>

    <div class="fecha-acta"><span class="lbl">Fecha</span><?= $e($fechaLarga) ?></div>

    <p class="texto">
        Por medio de la presente se hace descargo del inventario de <strong><?= $e($empresa['nombre']) ?></strong>
        de <?= $nProd === 1 ? 'el producto relacionado' : 'los ' . $nProd . ' productos relacionados' ?> a continuación
        (<?= $totalU ?> unidad<?= $totalU === 1 ? '' : 'es' ?> por un valor de <?= $money($totalV) ?>), que se retiran de las existencias
        por <?= $variosMotivos ? 'los motivos indicados' : 'el motivo indicado' ?> y dejan de estar disponibles para la venta. El descargo fue registrado en el sistema
        por <?= $e($personas) ?> y se firma en constancia por quienes intervienen.
    </p>

    <table>
        <thead>
            <tr><th style="width:30px">#</th><?php if (min($fechas) !== max($fechas)): ?><th style="width:80px">Fecha</th><?php endif; ?><th>Concepto</th><th class="c" style="width:90px">Cantidad</th><th class="r" style="width:110px">Valor</th><th style="width:34%">Motivo</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $n => $i): $mi = $motivos[$i['motivo']] ?? ['label' => ucfirst($i['motivo'])]; ?>
            <tr>
                <td><?= $n + 1 ?></td>
                <?php if (min($fechas) !== max($fechas)): ?><td><?= date('d/m/Y', strtotime($i['fecha'])) ?></td><?php endif; ?>
                <td><?= $e($i['producto_nombre']) ?><?php if ($i['codigo']): ?><div class="cod"><?= $e($i['codigo']) ?></div><?php endif; ?></td>
                <td class="c"><?= (int)$i['cantidad'] ?> <?= $e($i['unidad']) ?></td>
                <td class="r"><?= $money($i['subtotal']) ?><div class="cod"><?= $money($i['precio_unitario']) ?> c/u</div></td>
                <td><strong><?= $e($mi['label']) ?></strong><?php if (!empty($i['motivo_detalle'])): ?><div class="det"><?= $e($i['motivo_detalle']) ?></div><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="<?= min($fechas) !== max($fechas) ? 3 : 2 ?>" class="r">Total</td><td class="c"><?= $totalU ?></td><td class="r"><?= $money($totalV) ?></td><td></td></tr>
        </tfoot>
    </table>
    <div class="nota">Valores a precio de compra.</div>

    <div class="firmas">
        <div><?= $e($personas !== '—' ? $personas : '') ?><small>Vendedor</small></div>
        <div>&nbsp;<small>Administrador</small></div>
    </div>
    <div class="pie">Acta generada por PetControl · <?= $e($numero) ?></div>
</div>
<script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
