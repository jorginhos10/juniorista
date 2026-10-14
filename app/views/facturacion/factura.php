<?php
$e     = fn($v) => htmlspecialchars((string)$v);
$money = fn($n) => '$' . number_format((float)$n, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?= $e($doc['numero']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #f1f5f9; padding: 24px; }
        .hoja { position: relative; max-width: 640px; margin: 0 auto; background: #fff; padding: 28px 32px; border-radius: 8px; box-shadow: 0 2px 12px rgba(15,23,42,.08); overflow: hidden; }

        .hdr { display: flex; justify-content: space-between; gap: 16px; padding-bottom: 14px; border-bottom: 2px solid #4f46e5; }
        .empresa { display: flex; align-items: center; gap: 12px; }
        .empresa-logo { width: 64px; height: 64px; object-fit: contain; flex-shrink: 0; }
        .empresa-nombre { font-size: 16px; font-weight: 800; color: #1e1b4b; }
        .empresa-datos { font-size: 10px; color: #6b7280; margin-top: 3px; line-height: 1.5; }
        .doc { text-align: right; }
        .doc-tipo { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #4f46e5; }
        .doc-num { font-size: 17px; font-weight: 800; font-family: Consolas, monospace; color: #1e1b4b; }
        .doc-fecha { font-size: 10px; color: #6b7280; }

        .bloque { margin-top: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; }
        .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 3px; }
        .muted { color: #6b7280; font-size: 10px; }

        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; padding: 6px 8px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; }
        .r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .c { text-align: center; }

        .totales { width: 260px; margin: 12px 0 0 auto; }
        .totales td { border: none; padding: 4px 8px; }
        .totales .total td { border-top: 2px solid #1e1b4b; font-weight: 800; font-size: 14px; padding-top: 7px; }

        .notas { margin-top: 14px; font-size: 11px; background: #f9fafb; border-radius: 6px; padding: 8px 10px; }
        .firma { display: flex; justify-content: space-between; margin-top: 44px; gap: 20px; }
        .firma div { flex: 1; border-top: 1px solid #9ca3af; text-align: center; padding-top: 5px; font-size: 10px; color: #6b7280; }
        .pie { margin-top: 18px; text-align: center; font-size: 9px; color: #9ca3af; }

        .anulada {
            position: absolute; top: 42%; left: 50%; transform: translate(-50%, -50%) rotate(-24deg);
            font-size: 72px; font-weight: 900; letter-spacing: .1em; color: rgba(220, 38, 38, .14);
            border: 8px solid rgba(220, 38, 38, .14); padding: 4px 24px; border-radius: 12px; pointer-events: none;
        }

        .no-print { max-width: 640px; margin: 0 auto 12px; display: flex; justify-content: flex-end; gap: 8px; }
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
    <button onclick="window.print()" style="background:#4f46e5;color:#fff">Imprimir</button>
    <button onclick="window.close()" style="background:#e5e7eb">Cerrar</button>
</div>

<div class="hoja">
    <?php if ($doc['anulada']): ?><div class="anulada">ANULADA</div><?php endif; ?>

    <div class="hdr">
        <div class="empresa">
            <?php if (!empty($empresa['logo'])): ?>
            <img class="empresa-logo" src="<?= BASE_URL ?>/<?= $e($empresa['logo']) ?>" alt="">
            <?php endif; ?>
            <div>
            <div class="empresa-nombre"><?= $e($empresa['nombre']) ?></div>
            <div class="empresa-datos">
                <?= $empresa['ruc'] ? 'RUC/NIT: ' . $e($empresa['ruc']) . '<br>' : '' ?>
                <?= $empresa['direccion'] ? $e($empresa['direccion']) . '<br>' : '' ?>
                <?= $empresa['telefono'] ? 'Tel: ' . $e($empresa['telefono']) : '' ?><?= $empresa['telefono'] && $empresa['email'] ? ' · ' : '' ?><?= $e($empresa['email']) ?>
            </div>
            </div>
        </div>
        <div class="doc">
            <div class="doc-tipo">Factura · <?= $e($tipo) ?></div>
            <div class="doc-num"><?= $e($doc['numero']) ?></div>
            <div class="doc-fecha">Fecha: <?= date('d/m/Y H:i', strtotime($doc['creado'])) ?></div>
        </div>
    </div>

    <div class="bloque">
        <div>
            <div class="lbl"><?= $doc['cliente_extra'] === 'Mascota' ? 'Mascota' : 'Cliente' ?></div>
            <div><strong><?= $e($doc['cliente']) ?></strong></div>
            <?php if ($doc['cliente_extra'] && $doc['cliente_extra'] !== 'Mascota'): ?><div class="muted"><?= $e($doc['cliente_extra']) ?></div><?php endif; ?>
        </div>
        <div>
            <div class="lbl">Atendido por</div>
            <div><?= $e($doc['usuario'] ?: '—') ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr><th class="c" style="width:50px">Cant.</th><th>Descripción</th><th class="r">P. unit.</th><th class="r">Desc.</th><th class="r">Importe</th></tr>
        </thead>
        <tbody>
            <?php foreach ($doc['lineas'] as $l): ?>
            <tr>
                <td class="c"><?= $l['cantidad'] ?></td>
                <td><?= $e($l['concepto']) ?></td>
                <td class="r"><?= $money($l['precio']) ?></td>
                <td class="r"><?= $l['descuento'] > 0 ? $money($l['descuento']) : '—' ?></td>
                <td class="r"><?= $money($l['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totales">
        <tr><td>Subtotal</td><td class="r"><?= $money($doc['subtotal']) ?></td></tr>
        <?php if ($doc['descuento'] > 0): ?>
        <tr><td>Descuento</td><td class="r">− <?= $money($doc['descuento']) ?></td></tr>
        <?php endif; ?>
        <tr class="total"><td>Total</td><td class="r"><?= $money($doc['total']) ?></td></tr>
        <?php if (!empty($doc['metodo_pago'])): ?>
        <tr><td>Método de pago</td><td class="r"><?= $e($doc['metodo_pago']) ?></td></tr>
        <?php endif; ?>
    </table>

    <?php if ($doc['notas']): ?>
    <div class="notas"><span class="lbl">Notas</span><br><?= nl2br($e($doc['notas'])) ?></div>
    <?php endif; ?>

    <div class="firma">
        <div>Firma de quien atiende</div>
        <div>Firma del cliente</div>
    </div>

    <div class="pie">
        Documento generado por PetControl · <?= $e($doc['numero']) ?> · Emitido por <?= $e($emisor) ?> el <?= date('d/m/Y H:i') ?>
    </div>
</div>

</body>
</html>
