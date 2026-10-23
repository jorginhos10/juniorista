<?php
// Un registro del historial impreso en el «Formato de historia clínica de pequeños animales»
$e   = fn($v) => htmlspecialchars((string)$v);
$g   = function (...$k) use ($f) { $v = $f; foreach ($k as $x) { if (!is_array($v) || !isset($v[$x])) return ''; $v = $v[$x]; } return is_array($v) ? '' : (string)$v; };
$box = fn(bool $on) => '<span class="box' . ($on ? ' on' : '') . '">' . ($on ? '✕' : '') . '</span>';
$fec = fn(string $d) => $d ? date('d/m/Y', strtotime($d)) : '';
$edad = function (?string $nac): string {
    if (!$nac) return '';
    $d = (new DateTime($nac))->diff(new DateTime('today'));
    return $d->y > 0 ? $d->y . ($d->y === 1 ? ' año' : ' años') : ($d->m > 0 ? $d->m . ($d->m === 1 ? ' mes' : ' meses') : $d->d . ' días');
};
$grupoVac = $r['especie'] === 'Gato' ? 'felino' : 'canino';
$ts = strtotime($r['fecha']);
$sexo = ['macho' => 'Macho', 'hembra' => 'Hembra'][$r['sexo'] ?? ''] ?? '';
// Quita los ceros decimales sobrantes (12.40 → 12.4) sin tocar los enteros (110 sigue siendo 110)
$num = fn($v, string $u = '') => $v !== null && $v !== '' ? (str_contains((string)$v, '.') ? rtrim(rtrim((string)$v, '0'), '.') : (string)$v) . $u : '';
$filas = fn(string $k) => array_values(array_filter((array)($f[$k] ?? []), 'is_array'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historia clínica <?= $e($numero) ?> — <?= $e($r['mascota_nombre']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #111827; background: #e5e7eb; padding: 20px; }
        .hoja { max-width: 780px; margin: 0 auto 16px; background: #fff; padding: 22px 26px; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        td, th { border: 1px solid #374151; padding: 3px 5px; vertical-align: top; }
        .lbl { font-weight: 700; font-size: 9px; text-transform: uppercase; display: block; color: #1f2937; }
        .val { font-size: 10.5px; min-height: 12px; display: block; }
        td.lbl { display: table-cell; vertical-align: middle; }
        .titulo td { text-align: center; font-weight: 800; font-size: 14px; padding: 8px; }
        .titulo .clinica { width: 34%; font-size: 15px; }
        .titulo .clinica img { max-height: 46px; display: block; margin: 0 auto 4px; }
        .titulo .no { text-align: right; font-size: 11px; font-weight: 800; }
        .sec th { background: #b4c7e7; text-align: left; font-style: italic; font-weight: 800; font-size: 10px; text-transform: uppercase;
                  -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sub { background: #dbe5f6; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .lado { font-weight: 800; font-style: italic; text-transform: uppercase; font-size: 9.5px; text-align: center; vertical-align: middle; width: 22%; }
        .box { display: inline-block; width: 12px; height: 10px; border: 1px solid #374151; border-radius: 3px; text-align: center;
               font-size: 8px; line-height: 9px; margin: 0 3px 0 2px; vertical-align: -1px; }
        .box.on { font-weight: 800; }
        .op { margin-right: 10px; white-space: nowrap; }
        .na { width: 26px; text-align: center; }
        .nota { font-size: 8.5px; font-style: italic; margin: -4px 0 8px; }
        .firma { margin: 30px 0 0 auto; width: 260px; text-align: center; border-top: 1px solid #111827; padding-top: 4px; font-weight: 700; }
        .firma small { display: block; font-weight: 400; color: #4b5563; }
        .no-print { max-width: 780px; margin: 0 auto 10px; display: flex; justify-content: flex-end; gap: 8px; }
        .no-print button { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        .salto { page-break-before: always; }
        @media print {
            body { background: #fff; padding: 0; }
            .hoja { box-shadow: none; margin: 0; max-width: none; padding: 0; }
            .no-print { display: none; }
            @page { size: letter; margin: 10mm; }
        }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="background:#1e3a6e;color:#fff">Imprimir</button>
    <button onclick="window.close()" style="background:#d1d5db">Cerrar</button>
</div>

<div class="hoja">
    <table class="titulo">
        <tr>
            <td class="clinica">
                <?php if (!empty($empresa['logo'])): ?><img src="<?= BASE_URL ?>/<?= $e($empresa['logo']) ?>" alt=""><?php endif; ?>
                <?= $e($empresa['nombre']) ?>
            </td>
            <td>
                <div class="no">No. <?= $e($numero) ?></div>
                FORMATO DE HISTORIA CLÍNICA DE PEQUEÑOS ANIMALES
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:16%"><span class="lbl">Fecha de admisión</span></td>
            <td><span class="lbl">Día</span><span class="val"><?= date('d', $ts) ?></span></td>
            <td><span class="lbl">Mes</span><span class="val"><?= date('m', $ts) ?></span></td>
            <td><span class="lbl">Año</span><span class="val"><?= date('Y', $ts) ?></span></td>
            <td><span class="lbl">Hora</span><span class="val"><?= $e($g('admision', 'hora')) ?></span></td>
            <td><span class="lbl">H.C.</span><span class="val"><?= $e($hc) ?></span></td>
        </tr>
        <tr>
            <td colspan="4"><span class="lbl">Médico veterinario</span><span class="val"><?= $e($r['veterinario_nombre']) ?></span></td>
            <td><span class="lbl">T.P.</span><span class="val"><?= $e($g('admision', 'tp')) ?></span></td>
            <td><span class="lbl">Chip</span><span class="val"><?= $e($g('admision', 'chip')) ?></span></td>
        </tr>
    </table>

    <table class="sec">
        <tr><th colspan="3">Reseña del paciente</th></tr>
        <tr>
            <td style="width:45%"><span class="lbl">Nombre</span><span class="val"><?= $e($r['mascota_nombre']) ?></span></td>
            <td><span class="lbl">Especie</span><span class="val"><?= $e($r['especie']) ?></span></td>
            <td><span class="lbl">Raza</span><span class="val"><?= $e($r['raza']) ?></span></td>
        </tr>
        <tr>
            <td><span class="lbl">Color</span><span class="val"><?= $e($g('resena', 'color')) ?></span></td>
            <td><span class="lbl">Sexo</span><span class="val"><?= $e($sexo) ?></span></td>
            <td><span class="lbl">Fecha nacimiento</span><span class="val"><?= $e($fec((string)$r['fecha_nacimiento'])) ?></span></td>
        </tr>
        <tr>
            <td><span class="lbl">Edad</span><span class="val"><?= $e($edad($r['fecha_nacimiento'])) ?></span></td>
            <td><span class="lbl">Señas particulares</span><span class="val"><?= $e($g('resena', 'senas')) ?></span></td>
            <td><span class="lbl">Procedencia</span>
                <span class="op">Urbana<?= $box($g('resena', 'procedencia') === 'Urbana') ?></span>
                <span class="op">Rural<?= $box($g('resena', 'procedencia') === 'Rural') ?></span></td>
        </tr>
    </table>

    <table class="sec">
        <tr><th colspan="3">Datos del propietario</th></tr>
        <tr>
            <td colspan="2"><span class="lbl">Nombre</span><span class="val"><?= $e($r['cliente_nombre']) ?></span></td>
            <td><span class="lbl">Identificación</span><span class="val"><?= $e($r['cliente_dni']) ?></span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Dirección</span><span class="val"><?= $e($r['cliente_direccion']) ?></span></td>
            <td><span class="lbl">Estrato</span><span class="val"><?= $e($g('propietario', 'estrato')) ?></span></td>
        </tr>
        <tr>
            <td style="width:34%"><span class="lbl">Municipio</span><span class="val"><?= $e($g('propietario', 'municipio')) ?></span></td>
            <td><span class="lbl">Teléfono</span><span class="val"><?= $e($r['cliente_telefono']) ?></span></td>
            <td><span class="lbl">Ocupación</span><span class="val"><?= $e($g('propietario', 'ocupacion')) ?></span></td>
        </tr>
    </table>

    <table class="sec">
        <tr><th>Motivo de la consulta</th></tr>
        <tr><td><span class="val"><?= $e($r['motivo']) ?></span></td></tr>
        <tr><th>Anamnésicos</th></tr>
        <tr><td style="height:40px"><span class="val"><?= nl2br($e($r['anamnesis'])) ?></span></td></tr>
    </table>

    <table class="sec">
        <tr><th colspan="4">Historia del paciente</th></tr>
        <tr>
            <td class="lado">Vacunación<br><small><?= $grupoVac === 'felino' ? 'Felinos' : 'Caninos' ?></small></td>
            <td colspan="3">
                <span class="op">No<?= $box($g('vacunacion', 'no') !== '') ?></span><br>
                <?php foreach ($vacunas[$grupoVac] as $k => $lbl): ?>
                <span class="op" style="display:inline-block;min-width:200px;"><?= $lbl ?><?= $box($g('vacunacion', $k, 'si') !== '') ?>
                    Fecha: <u><?= $e($fec($g('vacunacion', $k, 'fecha'))) ?: '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' ?></u>
                    <?= $k === 'otra' && $g('vacunacion', 'otra', 'cual') !== '' ? ' ¿Cuál? ' . $e($g('vacunacion', 'otra', 'cual')) : '' ?></span>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td class="lado">Última desparasitación</td>
            <td><span class="op">Sí<?= $box($g('desparasitacion', 'estado') === 'Sí') ?></span><span class="op">No<?= $box($g('desparasitacion', 'estado') === 'No') ?></span><br>
                Producto: <?= $e($g('desparasitacion', 'producto')) ?><br>Fecha: <?= $e($fec($g('desparasitacion', 'fecha'))) ?></td>
            <td class="lado" style="width:14%">Alimentación</td>
            <td><?php foreach ($alimentaciones as $o): ?><span class="op"><?= $o ?><?= $box($g('alimentacion', 'tipo') === $o) ?></span><?php endforeach; ?>
                <?= $g('alimentacion', 'otra') !== '' ? '<br>Otra: ' . $e($g('alimentacion', 'otra')) : '' ?></td>
        </tr>
        <tr>
            <td class="lado">Estado reproductivo</td>
            <td><span class="op">Castrado<?= $box($g('reproductivo', 'estado') === 'Castrado') ?></span><span class="op">Entero<?= $box($g('reproductivo', 'estado') === 'Entero') ?></span><br>
                <span class="op">Gestación<?= $box($g('reproductivo', 'gestacion') !== '') ?></span><span class="op">Lactancia<?= $box($g('reproductivo', 'lactancia') !== '') ?></span></td>
            <td class="lado">Alergias</td>
            <td><?= $e($g('alergias')) ?></td>
        </tr>
        <tr>
            <td class="lado">Enfermedades anteriores</td>
            <td><?= $e($g('enfermedades')) ?></td>
            <td class="lado">Cirugías</td>
            <td><?= $e($g('cirugias')) ?></td>
        </tr>
        <tr>
            <td class="lado">Antecedentes familiares</td>
            <td colspan="3"><?= $e($g('antecedentes')) ?></td>
        </tr>
        <tr>
            <td class="lado">Hábitat</td>
            <td colspan="3"><?php foreach ($habitats as $o): ?><span class="op"><?= $o ?><?= $box($g('habitat', 'tipo') === $o) ?></span><?php endforeach; ?>
                <?= $g('habitat', 'otro') !== '' ? ' ' . $e($g('habitat', 'otro')) : '' ?></td>
        </tr>
    </table>

    <table class="sec">
        <tr><th colspan="3">Constantes fisiológicas</th></tr>
        <tr>
            <td><span class="lbl">T.Ll.C.</span><span class="val"><?= $e($r['tllc']) ?></span></td>
            <td><span class="lbl">F.C.</span><span class="val"><?= $e($num($r['frecuencia_cardiaca'], ' lpm')) ?></span></td>
            <td><span class="lbl">F.R.</span><span class="val"><?= $e($num($r['frecuencia_respiratoria'], ' rpm')) ?></span></td>
        </tr>
        <tr>
            <td><span class="lbl">Pulso</span><span class="val"><?= $e($g('constantes', 'pulso')) ?></span></td>
            <td><span class="lbl">Temperatura</span><span class="val"><?= $e($num($r['temperatura'], ' °C')) ?></span></td>
            <td><span class="lbl">Peso</span><span class="val"><?= $e($num($r['peso'], ' kg')) ?></span></td>
        </tr>
    </table>
</div>

<div class="hoja salto">
    <table class="sec">
        <tr><th colspan="4">Examen clínico</th></tr>
        <tr><td class="lbl" style="width:24%">Actitud</td><td colspan="3"><?php foreach ($actitudes as $o): ?><span class="op"><?= $o ?><?= $box($g('examen', 'actitud') === $o || !empty($f['examen']['actitud'][$o])) ?></span><?php endforeach; ?></td></tr>
        <tr><td class="lbl">Condición corporal</td><td colspan="3"><?php foreach ($condiciones as $o): ?><span class="op"><?= $o ?><?= $box($g('examen', 'condicion') === $o) ?></span><?php endforeach; ?></td></tr>
        <tr><td class="lbl">Estado hidratación</td><td colspan="3">
            <span class="op">Normal<?= $box($r['hidratacion'] === 'Normal') ?></span> Deshidratación:
            <?php foreach (array_slice($hidratacion, 1) as $o): ?><span class="op"><?= $o ?><?= $box($r['hidratacion'] === $o) ?></span><?php endforeach; ?>
        </td></tr>
        <tr class="sub"><td class="lbl">Mucosas</td><td class="na lbl">N</td><td class="na lbl">A</td><td class="lbl">Observaciones</td></tr>
        <?php foreach ($zonasMucosas as $k => $lbl): $st = $g('mucosas', $k, 'estado'); ?>
        <tr><td style="font-style:italic;font-weight:700"><?= $lbl ?></td><td class="na"><?= $st === 'N' ? '✕' : '' ?></td><td class="na"><?= $st === 'A' ? '✕' : '' ?></td><td><?= $e($g('mucosas', $k, 'obs')) ?></td></tr>
        <?php endforeach; ?>
        <?php foreach ($sistemas as $k => $lbl): $st = $g('sistemas', $k, 'estado'); ?>
        <tr class="sub"><td class="lbl"><?= $lbl ?></td><td class="na"><?= $st === 'N' ? '✕' : '' ?></td><td class="na"><?= $st === 'A' ? '✕' : '' ?></td><td style="background:#fff"><?= $e($g('sistemas', $k, 'obs')) ?></td></tr>
        <?php endforeach; ?>
        <?php if (trim((string)$r['examen_fisico']) !== ''): ?>
        <tr><td class="lbl">Otros hallazgos</td><td colspan="3"><?= nl2br($e($r['examen_fisico'])) ?></td></tr>
        <?php endif; ?>
    </table>

    <table class="sec">
        <tr><th colspan="3">Lista de problemas</th></tr>
        <tr class="sub"><td class="lbl" style="text-align:center">Lista de problemas</td><td class="lbl" style="text-align:center">Lista maestra</td><td class="lbl" style="text-align:center">Diagnóstico diferencial (DAMNVIT)</td></tr>
        <?php $probs = $filas('problemas'); for ($i = 0; $i < max(4, count($probs)); $i++): $p = $probs[$i] ?? []; ?>
        <tr style="height:20px"><td><?= $e($p['problema'] ?? '') ?></td><td><?= $e($p['maestra'] ?? '') ?></td><td><?= $e($p['diferencial'] ?? '') ?></td></tr>
        <?php endfor; ?>
    </table>
    <div class="nota">(D: Degenerativa – A: Anomalía congénita – M: Metabólica – N: Nutricional y neoplásica – V: Vascular – I: Infecciosa, inflamatoria o idiomática – T: Trauma)</div>

    <table class="sec">
        <tr><th colspan="6">Plan diagnóstico</th></tr>
        <tr class="sub"><td class="lbl">Examen</td><td class="lbl na">Sí</td><td class="lbl" style="width:70px">Autorizado</td><td class="lbl" style="width:70px">Fecha</td><td class="lbl">Laboratorio</td><td class="lbl">Resultados</td></tr>
        <?php $plan = $filas('plan'); for ($i = 0; $i < max(3, count($plan)); $i++): $p = $plan[$i] ?? []; ?>
        <tr style="height:20px"><td><?= $e($p['examen'] ?? '') ?></td><td class="na"><?= !empty($p['si']) ? '✕' : '' ?></td><td class="na"><?= !empty($p['autorizado']) ? '✕' : '' ?></td>
            <td><?= $e(isset($p['fecha']) ? $fec($p['fecha']) : '') ?></td><td><?= $e($p['laboratorio'] ?? '') ?></td><td><?= $e($p['resultados'] ?? '') ?></td></tr>
        <?php endfor; ?>
    </table>

    <?php if (trim((string)$r['diagnostico'] . $r['tratamiento'] . $r['proximo_control'] . $r['observaciones']) !== ''): ?>
    <table class="sec">
        <tr><th colspan="2">Diagnóstico y tratamiento</th></tr>
        <?php if ($r['diagnostico']): ?><tr><td class="lbl" style="width:24%">Diagnóstico</td><td><?= nl2br($e($r['diagnostico'])) ?></td></tr><?php endif; ?>
        <?php if ($r['tratamiento']): ?><tr><td class="lbl">Tratamiento</td><td><?= nl2br($e($r['tratamiento'])) ?></td></tr><?php endif; ?>
        <?php if ($r['producto_aplicado']): ?><tr><td class="lbl">Producto aplicado</td><td><?= $e($r['producto_aplicado']) ?><?= $r['lote'] ? ' · Lote ' . $e($r['lote']) : '' ?></td></tr><?php endif; ?>
        <?php if ($r['proximo_control']): ?><tr><td class="lbl">Próximo control</td><td><?= $e($fec($r['proximo_control'])) ?></td></tr><?php endif; ?>
        <?php if ($r['observaciones']): ?><tr><td class="lbl">Observaciones</td><td><?= nl2br($e($r['observaciones'])) ?></td></tr><?php endif; ?>
    </table>
    <?php endif; ?>

    <div class="firma">
        <?= $e($r['veterinario_nombre'] ?: 'Médico veterinario') ?>
        <small>Médico veterinario<?= $g('admision', 'tp') !== '' ? ' · T.P. ' . $e($g('admision', 'tp')) : '' ?></small>
    </div>
</div>

<script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
