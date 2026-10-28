<?php
$e = fn($v) => htmlspecialchars((string)$v);
$clinica = mb_strtoupper($veterinaria['nombre'] ?? 'Veterinaria');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consentimiento informado — <?= $e($mascota['nombre']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.45; color: #111; background: #fff; padding: 36px 56px; max-width: 820px; margin: 0 auto; }

        .fecha { font-size: 11px; font-weight: 700; margin-bottom: 18px; }
        .titulo { text-align: center; margin-bottom: 18px; }
        .titulo .alerta { color: #dc2626; font-weight: 800; font-size: 14px; margin-bottom: 6px; }
        .titulo h1 { font-size: 15px; font-weight: 800; }
        .titulo h2 { font-size: 15px; font-weight: 800; }
        p, li { margin-bottom: 5px; }
        ol { list-style: none; }
        .sub { list-style: none; margin-left: 0; }
        .campo { display: inline-block; min-width: 130px; border-bottom: 1px solid #111; padding: 0 6px; font-weight: 700; text-align: center; text-transform: uppercase; }
        .campo.largo { min-width: 260px; }
        [contenteditable]:hover, [contenteditable]:focus { background: #fef9c3; outline: none; }
        .firma { margin-top: 56px; text-align: center; width: 340px; }
        .firma .linea { border-top: 1px solid #111; padding-top: 4px; font-size: 14px; }
        .firma .cc { margin-top: 2px; font-size: 14px; }
        .firma .cc span { display: inline-block; min-width: 150px; border-bottom: 1px solid #111; font-weight: 700; text-align: left; padding-left: 4px; }

        .no-print { position: fixed; top: 16px; right: 16px; display: flex; flex-direction: column; gap: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; box-shadow: 0 4px 14px rgba(0,0,0,.1); font-size: 12px; }
        .no-print button { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        .no-print label { display: flex; gap: 6px; align-items: center; cursor: pointer; }
        @media print { .no-print { display: none; } body { padding: 0; } [contenteditable]:hover { background: none; } }
    </style>
</head>
<body>

<div class="no-print">
    <label><input type="checkbox" id="chkSinExamenes" checked> «No se realizaron los exámenes prequirúrgicos»</label>
    <small style="color:#6b7280">Haz clic en el procedimiento, la raza u otro dato subrayado para corregirlo.</small>
    <button onclick="window.print()" style="background:#4f46e5;color:#fff">Imprimir</button>
    <button onclick="window.close()" style="background:#e5e7eb">Cerrar</button>
</div>

<div class="fecha">FECHA: <span class="campo" style="min-width:170px" contenteditable="true"><?= date('d/m/y') ?></span></div>

<div class="titulo">
    <div class="alerta" id="alertaExamenes">NO SE REALIZARON LOS EXAMENES PREQUIRURGICO CORRESPONDIENTE</div>
    <h1>AUTORIZACIÓN VOLUNTARIA</h1>
    <h2>(CONSENTIMIENTO INFORMADO)</h2>
</div>

<p>Para la prestación de servicios médicos, intervenciones quirúrgicas, procedimientos anestésicos o procedimientos especiales</p>

<ol>
    <li>1. En pleno uso de mis facultades mentales, autorizo al EQUIPO “<b><?= $e($clinica) ?></b>” y a los asistentes de su elección, para realizar en el (la) paciente
        <span class="campo" contenteditable="true"><?= $e($mascota['nombre']) ?></span>,
        especie <span class="campo" contenteditable="true"><?= $e($mascota['especie']) ?></span>
        y raza <span class="campo" contenteditable="true"><?= $e($mascota['raza'] ?? '') ?></span>
        la siguiente intervención o procedimiento especial:
        <span class="campo largo" contenteditable="true"><?= $e($procedimiento) ?></span></li>
    <li>2. Después del examen clínico practicado por el médico especialista tratante y los exámenes complementarios pertinentes, se me ha explicado la naturaleza y el propósito de la intervención o procedimiento especial, así como las ventajas, complicaciones y riesgos que puedan producirse, en particular las siguientes:
        <ol class="sub">
            <li>a. Shock anafiláctico.</li>
            <li>b. Paro cardiorrespiratorio.</li>
            <li>c. Muerte súbita.</li>
            <li>d. Dehiscencia de herida (en caso de ser sometido a procedimiento quirúrgico)</li>
        </ol></li>
    <li>3. Se me han explicado las posibles alternativas al tratamiento propuesto y se me ha dado la oportunidad de hacer preguntas y todas han sido contestadas satisfactoriamente.</li>
    <li>4. Autorizo a la clínica veterinaria <?= $e($clinica) ?>, y a sus Médicos Veterinarios para utilizar en el curso de la intervención o procedimientos y en el post operatorio, los medicamentos, anestésicos o medios de contraste necesarios.</li>
    <li>5. Reconozco que hay riesgos para la vida y la salud asociados con estos procedimientos y sustancias. Tales riesgos me han sido explicados por el médico veterinario.</li>
    <li>6. Entiendo que en el curso de la intervención o procedimiento especial, pueden presentarse situaciones imprevistas que requieran procedimientos adicionales. Autorizo la realización de estos procedimientos, si el médico y sus asistentes los juzgan necesarios.</li>
    <li>7. Certifico se me ha explicado que los médicos veterinarios no serán responsables por reacciones individuales, inmediatas o tardías adversas producidas por efectos del tratamiento, medicamento o procedimiento quirúrgico al cual fue sometido mi mascota, mientras estos hayan sido aplicados correctamente por parte del médico especialista. (Ley 576 de 2000, Capítulo II, Artículo 23)</li>
    <li>8. Reconozco que no se me han garantizado los resultados que se esperan de la intervención o procedimiento, por tratarse de una actividad médica de medios y no de resultados, teniendo en cuenta que se hará uso de todos los elementos técnico-científicos disponibles y demás parámetros de calidad para su ejecución. (Ley 23 de 1981. Res. 1995/99).</li>
    <li>9. Certifico que he leído y comprendido perfectamente lo anterior; que todos los espacios en blanco de este documento han sido completados antes de mi firma y que me encuentro en capacidad de expresar mi libre decisión.</li>
</ol>

<div class="firma">
    <div style="height:46px"></div>
    <div class="linea">Firma del responsable del paciente</div>
    <div class="cc">C.C. <span contenteditable="true"><?= $e($mascota['cliente_dni'] ?? '') ?></span></div>
    <?php if (!empty($mascota['cliente_nombre'])): ?>
    <div style="font-size:12px;margin-top:2px"><?= $e($mascota['cliente_nombre']) ?></div>
    <?php endif; ?>
</div>

<script>
document.getElementById('chkSinExamenes').addEventListener('change', e => {
    document.getElementById('alertaExamenes').style.display = e.target.checked ? '' : 'none';
});
</script>
</body>
</html>
