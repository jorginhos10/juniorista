<?php
$e      = fn($v) => htmlspecialchars((string)$v);
$money  = fn($n) => '$' . number_format((float)$n, 2);
$numero = 'ABO-' . str_pad($r['id'], 5, '0', STR_PAD_LEFT);
$pagado = $r['saldo_despues'] <= 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura de abono <?= $numero ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #f1f5f9; padding: 24px; }
        .hoja { max-width: 560px; margin: 0 auto; background: #fff; padding: 28px 32px; border-radius: 8px; box-shadow: 0 2px 12px rgba(15,23,42,.08); }

        .hdr { display: flex; justify-content: space-between; gap: 16px; padding-bottom: 14px; border-bottom: 2px solid #0891b2; }
        .empresa-nombre { font-size: 16px; font-weight: 800; color: #1e1b4b; }
        .empresa-datos { font-size: 10px; color: #6b7280; margin-top: 3px; line-height: 1.5; }
        .doc { text-align: right; }
        .doc-tipo { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #0891b2; }
        .doc-num { font-size: 18px; font-weight: 800; font-family: Consolas, monospace; color: #1e1b4b; }
        .doc-fecha { font-size: 10px; color: #6b7280; }

        .bloque { margin-top: 16px; }
        .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 3px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; }

        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; padding: 6px 8px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; }
        .r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }

        .abono { margin-top: 16px; background: #ecfeff; border: 1px solid #a5f3fc; border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
        .abono-monto { font-size: 24px; font-weight: 800; color: #0e7490; }
        .saldos td { border: none; padding: 4px 8px; }
        .saldos .total td { border-top: 2px solid #1e1b4b; font-weight: 800; font-size: 13px; padding-top: 7px; }
        .sello { display: inline-block; margin-top: 4px; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .sello.ok { background: #dcfce7; color: #15803d; }
        .sello.pend { background: #fef3c7; color: #b45309; }

        .firma { display: flex; justify-content: space-between; margin-top: 44px; gap: 20px; }
        .firma div { flex: 1; border-top: 1px solid #9ca3af; text-align: center; padding-top: 5px; font-size: 10px; color: #6b7280; }
        .pie { margin-top: 18px; text-align: center; font-size: 9px; color: #9ca3af; }

        .no-print { max-width: 560px; margin: 0 auto 12px; display: flex; justify-content: flex-end; gap: 8px; }
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
    <button onclick="window.print()" style="background:#0891b2;color:#fff">Imprimir</button>
    <button onclick="window.close()" style="background:#e5e7eb">Cerrar</button>
</div>

<div class="hoja">
    <div class="hdr">
        <div>
            <div class="empresa-nombre"><?= $e($r['sucursal'] ?: 'PetControl') ?></div>
            <div class="empresa-datos">
                <?= $r['ruc'] ? 'RUC/NIT: ' . $e($r['ruc']) . '<br>' : '' ?>
                <?= $r['direccion'] ? $e($r['direccion']) . '<br>' : '' ?>
                <?= $r['telefono'] ? 'Tel: ' . $e($r['telefono']) : '' ?><?= $r['telefono'] && $r['email'] ? ' · ' : '' ?><?= $e($r['email']) ?>
            </div>
        </div>
        <div class="doc">
            <div class="doc-tipo">Factura de abono</div>
            <div class="doc-num"><?= $numero ?></div>
            <div class="doc-fecha">Fecha del pago: <?= date('d/m/Y', strtotime($r['fecha'])) ?></div>
            <div class="doc-fecha">Emitida: <?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <div class="bloque grid">
        <div>
            <div class="lbl">Paciente</div>
            <div><strong><?= $e($r['nombre']) ?></strong> · <?= $e($especies[$r['especie']] ?? $r['especie']) ?></div>
        </div>
        <div>
            <div class="lbl">Recibido por</div>
            <div><?= $e($r['usuario_nombre'] ?: '—') ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr><th>Concepto</th><th>Inicio</th><th class="r">Valor total</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Tratamiento: <?= $e($r['descripcion']) ?></td>
                <td><?= date('d/m/Y', strtotime($r['fecha_tratamiento'])) ?></td>
                <td class="r"><?= $money($r['valor']) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="abono">
        <div>
            <div class="lbl">Valor de este abono</div>
            <div><?= $e($r['nota'] ?: 'Abono al tratamiento') ?></div>
        </div>
        <div class="abono-monto"><?= $money($r['monto']) ?></div>
    </div>

    <table class="saldos">
        <tr><td>Valor total del tratamiento</td><td class="r"><?= $money($r['valor']) ?></td></tr>
        <tr><td>Abonado anteriormente</td><td class="r"><?= $money($r['abonado_antes']) ?></td></tr>
        <tr><td>Saldo antes de este abono</td><td class="r"><?= $money($r['saldo_antes']) ?></td></tr>
        <tr><td>Este abono</td><td class="r">− <?= $money($r['monto']) ?></td></tr>
        <tr class="total">
            <td>Saldo pendiente</td>
            <td class="r"><?= $money(max(0, $r['saldo_despues'])) ?></td>
        </tr>
    </table>
    <div style="text-align:right">
        <span class="sello <?= $pagado ? 'ok' : 'pend' ?>"><?= $pagado ? '✓ TRATAMIENTO PAGADO' : 'SALDO POR PAGAR' ?></span>
    </div>

    <div class="firma">
        <div>Firma de quien recibe</div>
        <div>Firma del cliente</div>
    </div>

    <div class="pie">Comprobante de abono generado por PetControl · <?= $numero ?></div>
</div>

</body>
</html>
