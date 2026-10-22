<?php
// Fórmula médica veterinaria imprimible. Única firma: la del médico veterinario.
$e = fn($v) => htmlspecialchars((string)$v);
$edad = function (?string $nac): string {
    if (!$nac) return '';
    $d = (new DateTime($nac))->diff(new DateTime('today'));
    return $d->y > 0 ? $d->y . ($d->y === 1 ? ' año' : ' años') : ($d->m > 0 ? $d->m . ($d->m === 1 ? ' mes' : ' meses') : $d->d . ' días');
};
$sexo = ['macho' => 'Macho', 'hembra' => 'Hembra'][$f['sexo'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fórmula médica <?= $e($numero) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #f1f5f9; padding: 24px; }
        .hoja { max-width: 720px; margin: 0 auto; background: #fff; padding: 28px 34px; border-radius: 8px; box-shadow: 0 2px 12px rgba(15,23,42,.08); }

        .hdr { display: flex; justify-content: space-between; gap: 16px; padding-bottom: 12px; border-bottom: 2px solid #4f46e5; }
        .empresa { display: flex; align-items: center; gap: 12px; }
        .empresa-logo { width: 64px; height: 64px; object-fit: contain; }
        .empresa-nombre { font-size: 16px; font-weight: 800; color: #1e1b4b; }
        .empresa-datos { font-size: 10px; color: #6b7280; margin-top: 3px; line-height: 1.5; }
        .doc { text-align: right; }
        .doc-tipo { font-size: 15px; font-weight: 800; letter-spacing: .06em; color: #4f46e5; text-transform: uppercase; }
        .doc-num { font-family: Consolas, monospace; font-size: 12px; font-weight: 700; }
        .doc-fecha { font-size: 10px; color: #6b7280; }

        .datos { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin-top: 14px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; }
        .datos > div { padding: 8px 10px; }
        .datos > div + div { border-left: 1px solid #e5e7eb; }
        .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 4px; }
        .fila { display: flex; gap: 6px; font-size: 11px; line-height: 1.6; }
        .fila b { min-width: 70px; color: #374151; font-weight: 600; }

        .seccion { margin-top: 14px; }
        .seccion-titulo { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #4f46e5; margin-bottom: 5px; }
        .texto { font-size: 12px; line-height: 1.55; white-space: pre-line; }

        .rx { font-size: 28px; font-weight: 800; color: #4f46e5; font-family: Georgia, serif; line-height: 1; margin-right: 8px; }
        .item { display: flex; gap: 10px; padding: 9px 0; border-bottom: 1px dashed #e5e7eb; page-break-inside: avoid; }
        .item-n { width: 22px; height: 22px; border-radius: 50%; background: #eef2ff; color: #4338ca; font-weight: 800; font-size: 11px;
                  display: flex; align-items: center; justify-content: center; flex-shrink: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .item-nombre { font-size: 13px; font-weight: 700; }
        .item-pres { font-size: 11px; color: #4b5563; }
        .item-cant { margin-left: auto; text-align: right; font-size: 11px; white-space: nowrap; }
        .posologia { font-size: 12px; margin-top: 3px; }
        .item-ind { font-size: 11px; color: #4b5563; font-style: italic; margin-top: 2px; }

        .firma { margin: 54px 0 0 auto; width: 280px; text-align: center; }
        .firma-linea { border-top: 1px solid #111827; padding-top: 5px; font-size: 12px; font-weight: 700; }
        .firma-sub { font-size: 10px; color: #4b5563; line-height: 1.5; }
        .pie { margin-top: 18px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; display: flex; justify-content: space-between; }

        .no-print { max-width: 720px; margin: 0 auto 12px; display: flex; justify-content: flex-end; gap: 8px; }
        .no-print button { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        @media print {
            body { background: #fff; padding: 0; }
            .hoja { box-shadow: none; border-radius: 0; max-width: none; padding: 0; }
            .no-print { display: none; }
            @page { size: letter; margin: 14mm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="background:#4f46e5;color:#fff">Imprimir</button>
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
                    <?= $empresa['direccion'] ? $e($empresa['direccion']) . '<br>' : '' ?>
                    <?= $empresa['telefono'] ? 'Tel: ' . $e($empresa['telefono']) : '' ?><?= $empresa['telefono'] && $empresa['email'] ? ' · ' : '' ?><?= $e($empresa['email']) ?>
                </div>
            </div>
        </div>
        <div class="doc">
            <div class="doc-tipo">Fórmula médica veterinaria</div>
            <div class="doc-num"><?= $e($numero) ?></div>
            <div class="doc-fecha">Fecha: <?= date('d/m/Y', strtotime($f['fecha'])) ?></div>
        </div>
    </div>

    <!-- Propietario y paciente -->
    <div class="datos">
        <div>
            <div class="lbl">Propietario</div>
            <div class="fila"><b>Nombre</b><span><?= $e($f['cliente'] ?: 'Sin registrar') ?></span></div>
            <?php if (!empty($f['cliente_dni'])): ?><div class="fila"><b>Documento</b><span><?= $e($f['cliente_dni']) ?></span></div><?php endif; ?>
            <?php if (!empty($f['cliente_telefono'])): ?><div class="fila"><b>Teléfono</b><span><?= $e($f['cliente_telefono']) ?></span></div><?php endif; ?>
            <?php if (!empty($f['cliente_direccion'])): ?><div class="fila"><b>Dirección</b><span><?= $e($f['cliente_direccion']) ?></span></div><?php endif; ?>
        </div>
        <div>
            <div class="lbl">Paciente</div>
            <div class="fila"><b>Nombre</b><span><?= $e($f['mascota']) ?></span></div>
            <?php if ($f['especie'] || $f['raza']): ?><div class="fila"><b>Especie</b><span><?= $e(trim($f['especie'] . ($f['especie'] && $f['raza'] ? ' · ' : '') . $f['raza'])) ?></span></div><?php endif; ?>
            <?php $edadTxt = $f['fecha_nacimiento'] ? $edad($f['fecha_nacimiento']) : (string)($f['paciente_edad'] ?? ''); ?>
            <?php if ($sexo || $edadTxt !== ''): ?>
            <div class="fila"><b>Sexo / edad</b><span><?= $e(trim($sexo . ($sexo && $edadTxt !== '' ? ' · ' : '') . $edadTxt)) ?></span></div>
            <?php endif; ?>
            <?php if ($f['peso'] !== null): ?><div class="fila"><b>Peso</b><span><?= rtrim(rtrim(number_format((float)$f['peso'], 2, ',', ''), '0'), ',') ?> kg</span></div><?php endif; ?>
        </div>
    </div>

    <?php if (!empty($f['diagnostico'])): ?>
    <div class="seccion">
        <div class="seccion-titulo">Diagnóstico</div>
        <div class="texto"><?= $e($f['diagnostico']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Prescripción -->
    <div class="seccion">
        <div class="seccion-titulo"><span class="rx">℞</span>Prescripción</div>
        <?php foreach ($f['items'] as $n => $it):
            $posologia = array_filter([
                $it['dosis'] ?: null,
                $it['via'] ? 'vía ' . mb_strtolower($it['via']) : null,
                $it['frecuencia'],
                $it['duracion'] ? 'durante ' . $it['duracion'] : null,
            ]); ?>
        <div class="item">
            <div class="item-n"><?= $n + 1 ?></div>
            <div style="flex:1">
                <div style="display:flex;gap:10px;">
                    <div>
                        <div class="item-nombre"><?= $e($it['medicamento']) ?></div>
                        <?php if ($it['presentacion']): ?><div class="item-pres"><?= $e($it['presentacion']) ?></div><?php endif; ?>
                    </div>
                    <?php if ($it['cantidad']): ?><div class="item-cant"><div class="lbl" style="margin:0">Cantidad</div><?= $e($it['cantidad']) ?></div><?php endif; ?>
                </div>
                <?php if ($posologia): ?><div class="posologia"><?= $e(implode(' · ', $posologia)) ?></div><?php endif; ?>
                <?php if ($it['indicaciones']): ?><div class="item-ind"><?= $e($it['indicaciones']) ?></div><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($f['indicaciones'])): ?>
    <div class="seccion">
        <div class="seccion-titulo">Indicaciones generales</div>
        <div class="texto"><?= $e($f['indicaciones']) ?></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($f['proximo_control'])): ?>
    <div class="seccion">
        <div class="seccion-titulo">Próximo control</div>
        <div class="texto"><?= date('d/m/Y', strtotime($f['proximo_control'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Única firma: la del médico veterinario -->
    <div class="firma">
        <div class="firma-linea"><?= $e($f['veterinario'] ?: 'Médico veterinario') ?></div>
        <div class="firma-sub">
            Médico veterinario
        </div>
    </div>

    <div class="pie">
        <span>Uso veterinario exclusivo · No administrar a personas</span>
        <span><?= $e($numero) ?></span>
    </div>
</div>

<script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
