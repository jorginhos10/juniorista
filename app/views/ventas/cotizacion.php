<?php
// Cotización imprimible (modo cotización del punto de venta). No es factura ni descuenta inventario.
$e     = fn($v) => htmlspecialchars((string)$v);
$money = fn($n) => '$' . number_format((float)$n, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización <?= $e($numero) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #f1f5f9; padding: 24px; }
        .hoja { position: relative; max-width: 680px; margin: 0 auto; background: #fff; padding: 28px 32px; border-radius: 8px; box-shadow: 0 2px 12px rgba(15,23,42,.08); }

        .hdr { display: flex; justify-content: space-between; gap: 16px; padding-bottom: 14px; border-bottom: 2px solid #16a34a; }
        .empresa { display: flex; align-items: center; gap: 12px; }
        .empresa-logo { width: 64px; height: 64px; object-fit: contain; flex-shrink: 0; }
        .empresa-nombre { font-size: 16px; font-weight: 800; color: #14532d; }
        .empresa-datos { font-size: 10px; color: #6b7280; margin-top: 3px; line-height: 1.5; }
        .doc { text-align: right; }
        .doc-tipo { font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #16a34a; }
        .doc-num { font-size: 13px; font-weight: 700; font-family: Consolas, monospace; color: #1f2937; }
        .doc-fecha { font-size: 10px; color: #6b7280; line-height: 1.5; }

        .bloque { margin-top: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; }
        .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 3px; }
        .muted { color: #6b7280; font-size: 10px; }

        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; padding: 6px 8px; background: #f0fdf4; border-bottom: 1px solid #dcfce7; }
        td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .c { text-align: center; }
        .det { font-size: 10px; color: #6b7280; }
        .tag-servicio { display: inline-block; font-size: 9px; font-weight: 700; color: #15803d; background: #dcfce7; border-radius: 4px; padding: 0 5px; margin-left: 4px; }

        .totales { width: 280px; margin: 12px 0 0 auto; }
        .totales td { border: none; padding: 4px 8px; }
        .totales .total td { border-top: 2px solid #14532d; font-weight: 800; font-size: 15px; padding-top: 7px; color: #14532d; }

        .notas { margin-top: 14px; font-size: 11px; background: #f9fafb; border-radius: 6px; padding: 8px 10px; }
        .condiciones { margin-top: 16px; font-size: 10px; color: #4b5563; line-height: 1.6; border-top: 1px dashed #d1d5db; padding-top: 10px; }
        .firma { display: flex; justify-content: flex-end; margin-top: 48px; }
        .firma div { max-width: 280px; }
        .firma div { flex: 1; border-top: 1px solid #9ca3af; text-align: center; padding-top: 5px; font-size: 10px; color: #6b7280; }
        .pie { margin-top: 18px; text-align: center; font-size: 9px; color: #9ca3af; }

        .no-print { max-width: 680px; margin: 0 auto 12px; display: flex; justify-content: flex-end; gap: 8px; }
        .no-print button { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        @media print {
            body { background: #fff; padding: 0; }
            .hoja { box-shadow: none; border-radius: 0; max-width: none; }
            .no-print { display: none; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="background:#16a34a;color:#fff">Imprimir</button>
    <button onclick="window.close()" style="background:#e5e7eb">Cerrar</button>
</div>

<div class="hoja">
    <div class="hdr">
        <div class="empresa">
            <?php if (!empty($empresa['logo'])): ?>
            <img class="empresa-logo" src="<?= BASE_URL ?>/<?= $e($empresa['logo']) ?>" alt="">
            <?php endif; ?>
            <div>
                <div class="empresa-nombre"><?= $e($empresa['nombre']) ?></div>
                <div class="empresa-datos">
                    <?= $empresa['ruc'] ? 'NIT: ' . $e($empresa['ruc']) . '<br>' : '' ?>
                    <?= $empresa['direccion'] ? $e($empresa['direccion']) . '<br>' : '' ?>
                    <?= $empresa['telefono'] ? 'Tel: ' . $e($empresa['telefono']) : '' ?><?= $empresa['telefono'] && $empresa['email'] ? ' · ' : '' ?><?= $e($empresa['email']) ?>
                </div>
            </div>
        </div>
        <div class="doc">
            <div class="doc-tipo">Cotización</div>
            <div class="doc-num"><?= $e($numero) ?></div>
            <div class="doc-fecha">Fecha: <?= date('d/m/Y H:i', $fecha) ?><br>Válida hasta: <?= date('d/m/Y', $vence) ?></div>
        </div>
    </div>

    <div class="bloque">
        <div>
            <div class="lbl">Cliente</div>
            <?php if ($cliente): ?>
            <div><strong><?= $e(trim($cliente['nombre'] . ' ' . $cliente['apellido'])) ?></strong></div>
            <div class="muted"><?= $e(trim(($cliente['dni'] ? 'Doc: ' . $cliente['dni'] : '') . ($cliente['telefono'] ? ' · Tel: ' . $cliente['telefono'] : ''), ' ·')) ?></div>
            <?php else: ?>
            <div><strong>Cliente general</strong></div>
            <?php endif; ?>
        </div>
        <div>
            <div class="lbl">Cotizado por</div>
            <div><?= $e($usuario ?: '—') ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr><th class="c" style="width:50px">Cant.</th><th>Descripción</th><th class="r">P. unit.</th><th class="r">Importe</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lineas as $l): ?>
            <tr>
                <td class="c"><?= (int)$l['cantidad'] ?></td>
                <td>
                    <?= $e($l['concepto']) ?><?php if ($l['detalle'] === 'Servicio'): ?><span class="tag-servicio">SERVICIO</span><?php endif; ?>
                    <?php if ($l['detalle'] !== 'Servicio' && (string)$l['detalle'] !== ''): ?><div class="det"><?= $e($l['detalle']) ?></div><?php endif; ?>
                </td>
                <td class="r"><?= $money($l['precio']) ?></td>
                <td class="r"><?= $money($l['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totales">
        <tr><td>Subtotal</td><td class="r"><?= $money($subtotal) ?></td></tr>
        <tr><td>Descuento<?= !empty($descuentoPct) ? ' (' . rtrim(rtrim(number_format($descuentoPct, 2, '.', ''), '0'), '.') . '%)' : '' ?></td><td class="r">− <?= $money($descuento) ?></td></tr>
        <tr class="total"><td>Total cotizado</td><td class="r"><?= $money($total) ?></td></tr>
    </table>

    <?php if ($notas !== ''): ?>
    <div class="notas"><span class="lbl">Notas</span><br><?= nl2br($e($notas)) ?></div>
    <?php endif; ?>

    <div class="condiciones">
        Esta cotización es válida por 15 días, hasta el <?= date('d/m/Y', $vence) ?>. Los precios de los productos están sujetos
        a la disponibilidad del inventario al momento de la compra. Este documento no es una factura.
    </div>

    <!-- Única firma: la del médico veterinario -->
    <div class="firma">
        <div>Firma y sello del médico veterinario</div>
    </div>

    <div class="pie">Cotización generada por PetControl · <?= $e($numero) ?></div>
</div>

<script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
