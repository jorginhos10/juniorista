<?php
$fecha = fn(?string $f) => $f ? date('d/m/Y', strtotime($f)) : '';
$edad = function (?string $nac): string {
    if (!$nac) return '';
    $diff = (new DateTime($nac))->diff(new DateTime('today'));
    if ($diff->y > 0) return $diff->y . ($diff->y === 1 ? ' año' : ' años');
    if ($diff->m > 0) return $diff->m . ($diff->m === 1 ? ' mes' : ' meses');
    return $diff->d . ($diff->d === 1 ? ' día' : ' días');
};
$e = fn($v) => htmlspecialchars((string)$v);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historia clínica — <?= $e($mascota['nombre']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; padding: 32px 40px; }

        .hdr { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 16px; border-bottom: 2px solid #4f46e5; margin-bottom: 18px; }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-ico { width: 42px; height: 42px; background: linear-gradient(135deg, #1e1b4b, #4f46e5); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; }
        .logo-name { font-size: 20px; font-weight: 800; color: #1e1b4b; }
        .logo-sub { font-size: 10px; color: #6b7280; letter-spacing: .05em; text-transform: uppercase; }
        .meta { text-align: right; }
        .meta-title { font-size: 16px; font-weight: 700; color: #1e1b4b; }
        .meta-info { font-size: 10px; color: #9ca3af; margin-top: 2px; }

        .paciente { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px 18px; background: #f5f3ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
        .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }
        .val { font-size: 12px; font-weight: 600; color: #1e1b4b; }
        .paciente .full { grid-column: 1 / -1; }

        .reg { border: 1px solid #e5e7eb; border-left: 4px solid var(--c); border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; page-break-inside: avoid; }
        .reg-hdr { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 6px; }
        .reg-tipo { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--c); }
        .reg-motivo { font-size: 13px; font-weight: 700; }
        .reg-fecha { text-align: right; font-size: 10px; color: #6b7280; white-space: nowrap; }
        .vitales { display: flex; flex-wrap: wrap; gap: 4px 14px; font-size: 11px; margin: 6px 0; }
        .vitales b { color: #6b7280; font-weight: 600; }
        .sec { margin-top: 5px; }
        .sec-t { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
        .extra { margin-top: 6px; font-size: 11px; color: #374151; }

        .vacio { text-align: center; color: #9ca3af; padding: 30px; border: 1px dashed #e5e7eb; border-radius: 8px; }
        .firma { display: flex; justify-content: flex-end; margin-top: 50px; }
        .firma div { width: 220px; border-top: 1px solid #9ca3af; text-align: center; padding-top: 6px; font-size: 10px; color: #6b7280; }

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

<div class="hdr">
    <div class="logo">
        <div class="logo-ico">♥</div>
        <div>
            <div class="logo-name">PetControl</div>
            <div class="logo-sub">Sistema veterinario</div>
        </div>
    </div>
    <div class="meta">
        <div class="meta-title">Historia clínica</div>
        <div class="meta-info">Generado el <?= date('d/m/Y H:i') ?> por <?= $e($usuario['nombre']) ?></div>
    </div>
</div>

<div class="paciente">
    <div><div class="lbl">Paciente</div><div class="val"><?= $e($mascota['nombre']) ?></div></div>
    <div><div class="lbl">Especie / raza</div><div class="val"><?= $e($mascota['especie']) ?><?= $mascota['raza'] ? ' · ' . $e($mascota['raza']) : '' ?></div></div>
    <div><div class="lbl">Sexo / edad</div><div class="val"><?= $mascota['sexo'] ? ucfirst($mascota['sexo']) : '—' ?><?= $mascota['fecha_nacimiento'] ? ' · ' . $edad($mascota['fecha_nacimiento']) : '' ?></div></div>
    <div><div class="lbl">Propietario</div><div class="val"><?= $e($mascota['cliente_nombre'] ?: 'Sin dueño') ?><?= $mascota['cliente_telefono'] ? ' · ' . $e($mascota['cliente_telefono']) : '' ?></div></div>
    <?php if ($mascota['notas']): ?>
    <div class="full"><div class="lbl">Notas del paciente</div><div class="val" style="font-weight:400"><?= $e($mascota['notas']) ?></div></div>
    <?php endif; ?>
</div>

<?php if (empty($registros)): ?>
<div class="vacio">Sin registros clínicos.</div>
<?php endif; ?>

<?php foreach ($registros as $r): $t = $tipos[$r['tipo']]; ?>
<div class="reg" style="--c:<?= $t['color'] ?>">
    <div class="reg-hdr">
        <div>
            <div class="reg-tipo"><?= $t['label'] ?></div>
            <div class="reg-motivo"><?= $e($r['motivo']) ?></div>
        </div>
        <div class="reg-fecha">
            <?= $fecha($r['fecha']) ?>
            <?= $r['veterinario_nombre'] ? '<br>' . $e($r['veterinario_nombre']) : '' ?>
        </div>
    </div>
    <?php
    $v = array_filter([
        'Peso' => $r['peso'] !== null ? rtrim(rtrim($r['peso'], '0'), '.') . ' kg' : null,
        'T°'   => $r['temperatura'] !== null ? $r['temperatura'] . ' °C' : null,
        'FC'   => $r['frecuencia_cardiaca'] !== null ? $r['frecuencia_cardiaca'] . ' lpm' : null,
        'FR'   => $r['frecuencia_respiratoria'] !== null ? $r['frecuencia_respiratoria'] . ' rpm' : null,
        'Mucosas' => $r['mucosas'], 'TLLC' => $r['tllc'], 'Deshidratación' => $r['hidratacion'],
        'CC'   => $r['condicion_corporal'] !== null ? $r['condicion_corporal'] . '/9' : null,
    ], fn($x) => $x !== null);
    ?>
    <?php if ($v): ?>
    <div class="vitales"><?php foreach ($v as $k => $x): ?><span><b><?= $k ?>:</b> <?= $e($x) ?></span><?php endforeach; ?></div>
    <?php endif; ?>
    <?php foreach (['anamnesis' => 'S · Anamnesis', 'examen_fisico' => 'O · Examen físico', 'diagnostico' => 'A · Diagnóstico', 'tratamiento' => 'P · Tratamiento / plan', 'observaciones' => 'Observaciones'] as $campo => $titulo): ?>
    <?php if ($r[$campo]): ?>
    <div class="sec"><div class="sec-t"><?= $titulo ?></div><div><?= nl2br($e($r[$campo])) ?></div></div>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($r['producto_aplicado'] || $r['proximo_control']): ?>
    <div class="extra">
        <?= $r['producto_aplicado'] ? '<b>Producto:</b> ' . $e($r['producto_aplicado']) . ($r['lote'] ? ' (lote ' . $e($r['lote']) . ')' : '') : '' ?>
        <?= $r['producto_aplicado'] && $r['proximo_control'] ? ' &nbsp;·&nbsp; ' : '' ?>
        <?= $r['proximo_control'] ? '<b>Próximo control:</b> ' . $fecha($r['proximo_control']) : '' ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="firma"><div>Firma y sello del médico veterinario</div></div>

</body>
</html>
