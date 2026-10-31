<?php
// Hoja ECOP impresa: mismo formato del papel (cabecera de datos + tabla de medicamentos). Se completa con filas en blanco para seguir a mano.
$e = fn($v) => htmlspecialchars((string)$v);
$edad = function (?string $nac): string {
    if (!$nac) return '';
    $d = (new DateTime($nac))->diff(new DateTime('today'));
    if ($d->y > 0) return $d->y . ($d->y === 1 ? ' año' : ' años');
    if ($d->m > 0) return $d->m . ($d->m === 1 ? ' mes' : ' meses');
    return $d->d . ($d->d === 1 ? ' día' : ' días');
};
$fmt = fn($f) => $f ? date('d/m/Y H:i', strtotime($f)) : '';
$minimo = 17;   // filas totales de la hoja; si hay más aplicaciones, la hoja continúa en otra página
$blancos = max(0, $minimo - count($aplicaciones));
$blancosC = max(0, $minimo - count($constancias));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ECOP — <?= $e($h['mascota_nombre']) ?></title>
    <style>
        @page { size: letter landscape; margin: 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #000; padding: 16px 24px; }
        h1 { text-align: center; font-size: 26px; font-weight: 800; margin-bottom: 2px; }
        .clinica { text-align: center; font-size: 10px; color: #555; margin-bottom: 8px; }
        .datos { border: 1px solid #000; padding: 6px 8px; display: grid; grid-template-columns: 1fr 1fr; gap: 5px 24px; margin-bottom: 12px; }
        .dato { white-space: nowrap; overflow: hidden; }
        .dato .l { font-weight: 400; }
        .dato .v { font-weight: 700; border-bottom: 1px solid #000; padding: 0 4px; display: inline-block; min-width: 140px; }
        .dato.doble { display: flex; gap: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 3px 5px; text-align: left; height: 23px; font-size: 11px; }
        th { text-align: center; font-weight: 800; text-transform: uppercase; }
        td.c { text-align: center; }
        tr { page-break-inside: avoid; }
        .pagina2 { page-break-before: always; padding-top: 4px; }
        .pagina2 h1 { font-size: 24px; margin-bottom: 6px; }
        .paciente-linea { text-align: center; font-size: 11px; margin-bottom: 10px; }
        .pagina2 td { height: 25px; }
        .obs td { height: auto; vertical-align: top; padding: 5px 6px; }
        .obs td.t { white-space: pre-wrap; }
        .no-print { position: fixed; top: 12px; right: 12px; display: flex; gap: 8px; }
        .no-print button { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="background:#4f46e5;color:#fff">Imprimir</button>
    <button onclick="window.close()" style="background:#e5e7eb">Cerrar</button>
</div>

<h1>ECOP</h1>
<div class="clinica"><?= $e($empresa['nombre']) ?></div>

<div class="datos">
    <div class="dato"><span class="l">DIAGNOSTICO</span> <span class="v" style="min-width:300px"><?= $e($h['diagnostico']) ?></span></div>
    <div class="dato"><span class="l">FECHA DE INGRESO</span> <span class="v" style="min-width:230px"><?= $fmt($h['fecha_ingreso']) ?></span></div>
    <div class="dato"><span class="l">MASCOTA</span> <span class="v" style="min-width:300px"><?= $e($h['mascota_nombre']) ?></span></div>
    <div class="dato"><span class="l">FECHA DE SALIDA</span> <span class="v" style="min-width:230px"><?= $fmt($h['fecha_salida']) ?></span></div>
    <div class="dato doble"><span><span class="l">RAZA</span> <span class="v" style="min-width:150px"><?= $e($h['raza'] ?: $h['especie']) ?></span></span>
        <span><span class="l">SEXO</span> <span class="v" style="min-width:90px"><?= $e(ucfirst((string)$h['sexo'])) ?></span></span></div>
    <div class="dato"><span class="l">EDAD</span> <span class="v" style="min-width:150px"><?= $e($edad($h['fecha_nacimiento'])) ?></span></div>
    <div class="dato"><span class="l">PROPIETARIO</span> <span class="v" style="min-width:250px"><?= $e($h['cliente_nombre']) ?></span></div>
    <div class="dato"><span class="l">PESO</span> <span class="v" style="min-width:150px"><?= $h['peso'] !== null ? rtrim(rtrim($h['peso'], '0'), '.') . ' kg' : '' ?></span></div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:9%">Fecha</th><th style="width:7%">Hora</th><th style="width:20%">Medicamento</th><th style="width:9%">Dosis</th>
            <th style="width:7%">Vía</th><th style="width:14%">Frecuencia</th><th style="width:12%">Firma</th><th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($aplicaciones as $a): ?>
        <tr>
            <td class="c"><?= date('d/m/Y', strtotime($a['fecha'])) ?></td>
            <td class="c"><?= $e($a['hora']) ?></td>
            <td><?= $e($a['medicamento']) ?></td>
            <td class="c"><?= $e($a['dosis']) ?></td>
            <td class="c"><?= $e($a['via']) ?></td>
            <td><?= $e($a['frecuencia']) ?></td>
            <td style="font-size:9px"><?= $e($a['firma']) ?></td>
            <td><?= $e($a['observaciones']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php for ($i = 0; $i < $blancos; $i++): ?>
        <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
    </tbody>
</table>

<!-- Página 2: constancias fisiológicas -->
<div class="pagina2">
    <h1>CONSTANCIAS FISIOLÓGICAS</h1>
    <div class="paciente-linea">
        <b><?= $e($h['mascota_nombre']) ?></b> · <?= $e($h['especie']) ?><?= $h['raza'] ? ' · ' . $e($h['raza']) : '' ?>
        · Ingreso <?= $fmt($h['fecha_ingreso']) ?> · <?= $e($h['diagnostico']) ?>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:8%">Fecha</th><th style="width:6%">Hora</th><th>TEMP.</th><th>F.C.</th><th>F.R.</th><th>P.S.D.</th>
                <th>S.O.</th><th>GLU.</th><th>VOM.</th><th>DEP.</th><th>OR.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($constancias as $c): ?>
            <tr>
                <td class="c"><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                <td class="c"><?= $e($c['hora']) ?></td>
                <td class="c"><?= $e($c['temp']) ?></td><td class="c"><?= $e($c['fc']) ?></td><td class="c"><?= $e($c['fr']) ?></td>
                <td class="c"><?= $e($c['psd']) ?></td><td class="c"><?= $e($c['so']) ?></td><td class="c"><?= $e($c['glu']) ?></td>
                <td class="c"><?= $e($c['vom']) ?></td><td class="c"><?= $e($c['dep']) ?></td><td class="c"><?= $e($c['orina']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < $blancosC; $i++): ?>
            <tr><?php for ($j = 0; $j < 11; $j++): ?><td></td><?php endfor; ?></tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>

<!-- Página 3: bitácora de observaciones -->
<div class="pagina2">
    <h1>BITÁCORA DE OBSERVACIONES</h1>
    <div class="paciente-linea">
        <b><?= $e($h['mascota_nombre']) ?></b> · <?= $e($h['especie']) ?><?= $h['raza'] ? ' · ' . $e($h['raza']) : '' ?>
        · Ingreso <?= $fmt($h['fecha_ingreso']) ?> · <?= $e($h['diagnostico']) ?>
    </div>
    <table class="obs">
        <thead><tr><th style="width:9%">Fecha</th><th style="width:6%">Hora</th><th style="width:12%">Tipo</th><th>Observación</th><th style="width:13%">Firma</th></tr></thead>
        <tbody>
            <?php foreach ($observaciones as $o): ?>
            <tr>
                <td class="c"><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
                <td class="c"><?= $e($o['hora']) ?></td>
                <td><?= $e($categorias[$o['categoria']][0] ?? 'Otro') ?></td>
                <td class="t"><?= $e($o['texto']) ?></td>
                <td style="font-size:9px"><?= $e($o['firma']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php for ($i = count($observaciones); $i < 8; $i++): ?>
            <tr><td style="height:40px"></td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>

<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
