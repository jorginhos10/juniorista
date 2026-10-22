<?php
// Formulario de fórmula médica: paciente, médico, diagnóstico, prescripción (Rx) e indicaciones.
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
$valor = function (string $k, $defecto = '') use ($old, $formula) {
    if ($old !== null && array_key_exists($k, $old)) return $old[$k];
    return $formula[$k] ?? $defecto;
};

// Ítems a mostrar: lo enviado (si hubo error), los de la fórmula o una fila vacía
$items = [];
if ($old !== null) {
    foreach ((array)($old['medicamento'] ?? []) as $i => $m) {
        $items[] = [
            'producto_id' => $old['producto_id'][$i] ?? '', 'medicamento' => $m, 'presentacion' => $old['presentacion'][$i] ?? '',
            'cantidad' => $old['cantidad'][$i] ?? '', 'dosis' => $old['dosis'][$i] ?? '', 'via' => $old['via'][$i] ?? '',
            'frecuencia' => $old['frecuencia'][$i] ?? '', 'duracion' => $old['duracion'][$i] ?? '', 'indicaciones' => $old['indicaciones_item'][$i] ?? '',
        ];
    }
} elseif ($formula) {
    $items = $formula['items'];
}

$mascotaSel = (int)$valor('mascota_id', $mascotaInicial);
$vetSel     = (int)$valor('veterinario_id', 0);
if (!$vetSel) {
    // Por defecto firma el usuario en sesión si es médico (o el primero de la lista)
    $ids    = array_map('intval', array_column($veterinarios, 'id'));
    $vetSel = in_array((int)$_SESSION['usuario_id'], $ids, true) ? (int)$_SESSION['usuario_id'] : ($ids[0] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; <?= $formula ? 'Editar fórmula' : 'Nueva fórmula médica' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .rx-card { border-radius: .875rem; }
        .rx-card .card-header { background: #fff; border: 0; padding-top: 1rem; }
        .rx-titulo { font-weight: 700; display: flex; align-items: center; gap: .5rem; margin: 0; }
        .rx-titulo .letra { width: 26px; height: 26px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center;
                            font-size: .8rem; font-weight: 800; color: #4338ca; background: #eef2ff; }
        .ac { position: relative; }
        .ac-lista { display: none; position: absolute; left: 0; right: 0; top: calc(100% + 2px); z-index: 1060; max-height: 280px; overflow-y: auto;
                    background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .12); }
        .ac-op { padding: .45rem .7rem; font-size: .86rem; cursor: pointer; display: flex; justify-content: space-between; gap: .75rem; }
        .ac-op:hover, .ac-op.activa { background: #eef2ff; }
        .ac-op small { color: #94a3b8; }
        .ac-vacio { padding: .5rem .7rem; font-size: .84rem; color: #94a3b8; }
        .paciente-ficha { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .6rem; padding: .6rem .8rem; font-size: .85rem; }
        .rx-item { border: 1px solid #e2e8f0; border-radius: .75rem; padding: .85rem .9rem .6rem; margin-bottom: .75rem; position: relative; background: #fff; }
        .rx-item-num { position: absolute; top: -10px; left: 12px; background: #4f46e5; color: #fff; font-size: .72rem; font-weight: 700;
                       border-radius: 999px; padding: .05rem .5rem; }
        .rx-item .form-label { font-size: .75rem; font-weight: 600; color: #64748b; margin-bottom: .2rem; }
        .rx-origen { font-size: .7rem; font-weight: 700; border-radius: 4px; padding: 0 .35rem; }
        .rx-origen.almacen { color: #15803d; background: #dcfce7; }
        .rx-origen.manual  { color: #92400e; background: #fef3c7; }
        .rx-quitar { position: absolute; top: 6px; right: 8px; }
    </style>
</head>
<body class="dashboard-body">
<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0"><?= $formula ? 'Editar fórmula médica' : 'Nueva fórmula médica' ?></h5>
                <p class="topbar-date mb-0">Prescripción veterinaria · la firma el médico</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <a href="<?= BASE_URL ?>/formulas<?= $mascotaSel ? '?mascota=' . $mascotaSel : '' ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>
        </header>

        <main class="main-content">
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i><span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/formulas/guardar" id="formFormula" autocomplete="off">
                <input type="hidden" name="id" value="<?= $formula ? (int)$formula['id'] : 0 ?>">

                <!-- Paciente y médico -->
                <div class="card border-0 shadow-sm rx-card mb-3">
                    <div class="card-header"><h6 class="rx-titulo"><span class="letra">1</span>Paciente y médico</h6></div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <label class="form-label fw-semibold">Paciente (mascota) <span class="text-danger">*</span></label>
                                <div class="ac">
                                    <input type="text" class="form-control" id="pacienteBuscar" placeholder="Nombre de la mascota o del propietario…">
                                    <div class="ac-lista" id="pacienteLista"></div>
                                </div>
                                <input type="hidden" name="mascota_id" id="mascotaId" value="<?= $mascotaSel ?: '' ?>">
                                <div class="paciente-ficha mt-2 d-none" id="pacienteFicha"></div>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($valor('fecha', date('Y-m-d'))) ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <label class="form-label fw-semibold">Peso (kg)</label>
                                <input type="text" class="form-control" name="peso" inputmode="decimal" value="<?= htmlspecialchars((string)$valor('peso', '')) ?>" placeholder="Ej: 12,5">
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label class="form-label fw-semibold">Médico veterinario <span class="text-danger">*</span></label>
                                <select class="form-select" name="veterinario_id" id="veterinarioId" required>
                                    <?php foreach ($veterinarios as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= (int)$v['id'] === $vetSel ? 'selected' : '' ?>><?= htmlspecialchars($v['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3 offset-lg-9">
                                <label class="form-label fw-semibold">Tarjeta profesional</label>
                                <input type="text" class="form-control" name="tarjeta_profesional" id="tarjetaProfesional" maxlength="40"
                                       value="<?= htmlspecialchars((string)$valor('tarjeta_profesional', '')) ?>" placeholder="N.º de registro (aparece en la firma)">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnóstico -->
                <div class="card border-0 shadow-sm rx-card mb-3">
                    <div class="card-header"><h6 class="rx-titulo"><span class="letra">2</span>Diagnóstico</h6></div>
                    <div class="card-body pt-2">
                        <textarea class="form-control" name="diagnostico" rows="2" maxlength="2000"
                                  placeholder="Diagnóstico presuntivo o definitivo (ej: Otitis externa bilateral)"><?= htmlspecialchars((string)$valor('diagnostico', '')) ?></textarea>
                    </div>
                </div>

                <!-- Prescripción -->
                <div class="card border-0 shadow-sm rx-card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h6 class="rx-titulo"><span class="letra">Rx</span>Prescripción</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="agregarItem()">
                                <i class="bi bi-box-seam me-1"></i>Producto del almacén
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="agregarItem({ manual: true })">
                                <i class="bi bi-pencil me-1"></i>Otro producto
                            </button>
                        </div>
                    </div>
                    <div class="card-body pt-3" id="rxItems"></div>
                </div>

                <!-- Indicaciones -->
                <div class="card border-0 shadow-sm rx-card mb-3">
                    <div class="card-header"><h6 class="rx-titulo"><span class="letra">3</span>Indicaciones y control</h6></div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-lg-9">
                                <label class="form-label fw-semibold">Indicaciones generales</label>
                                <textarea class="form-control" name="indicaciones" rows="3" maxlength="3000"
                                          placeholder="Recomendaciones para el propietario: alimentación, reposo, cuidados, signos de alarma…"><?= htmlspecialchars((string)$valor('indicaciones', '')) ?></textarea>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label fw-semibold">Próximo control</label>
                                <input type="date" class="form-control" name="proximo_control" value="<?= htmlspecialchars((string)$valor('proximo_control', '')) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="<?= BASE_URL ?>/formulas" class="btn btn-light">Cancelar</a>
                    <button type="submit" class="btn btn-primary-custom px-4"><i class="bi bi-printer me-1"></i>Guardar e imprimir</button>
                </div>
            </form>
        </main>
    </div>
</div>

<template id="tplItem">
    <div class="rx-item">
        <span class="rx-item-num"></span>
        <button type="button" class="btn btn-sm btn-link text-danger rx-quitar" title="Quitar"><i class="bi bi-x-lg"></i></button>
        <input type="hidden" name="producto_id[]" class="it-producto">
        <div class="row g-2">
            <div class="col-lg-5">
                <label class="form-label">Medicamento <span class="rx-origen"></span></label>
                <div class="ac">
                    <input type="text" class="form-control form-control-sm it-medicamento" name="medicamento[]" maxlength="200" required>
                    <div class="ac-lista"></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <label class="form-label">Presentación / concentración</label>
                <input type="text" class="form-control form-control-sm it-presentacion" name="presentacion[]" maxlength="120" placeholder="Ej: Tabletas 250 mg">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label">Cantidad a dispensar</label>
                <input type="text" class="form-control form-control-sm it-cantidad" name="cantidad[]" maxlength="60" placeholder="Ej: 1 caja (20 tab.)">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label">Dosis</label>
                <input type="text" class="form-control form-control-sm it-dosis" name="dosis[]" maxlength="120" placeholder="Ej: 1 tableta">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label">Vía</label>
                <select class="form-select form-select-sm it-via" name="via[]">
                    <option value="">—</option>
                    <?php foreach ($vias as $v): ?><option value="<?= $v ?>"><?= $v ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label">Frecuencia</label>
                <input type="text" class="form-control form-control-sm it-frecuencia" name="frecuencia[]" maxlength="80" placeholder="Ej: cada 12 horas">
            </div>
            <div class="col-sm-6 col-lg-4">
                <label class="form-label">Duración</label>
                <input type="text" class="form-control form-control-sm it-duracion" name="duracion[]" maxlength="80" placeholder="Ej: 7 días">
            </div>
            <div class="col-12">
                <input type="text" class="form-control form-control-sm it-indicaciones" name="indicaciones_item[]" maxlength="255"
                       placeholder="Indicaciones de este medicamento (opcional): con comida, agitar antes de usar…">
            </div>
        </div>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const MASCOTAS  = <?= json_encode($mascotas, $jsonFlags) ?>;
const PRODUCTOS = <?= json_encode(array_map(fn($p) => ['id' => (int)$p['id'], 'nombre' => $p['nombre'], 'codigo' => (string)$p['codigo'],
                    'descripcion' => (string)$p['descripcion'], 'unidad' => (string)$p['unidad'], 'stock' => (int)$p['stock']], $productos), $jsonFlags) ?>;
const TARJETAS  = <?= json_encode((object)$tarjetas, $jsonFlags) ?>;
const ITEMS     = <?= json_encode(array_values($items), $jsonFlags) ?>;
const ICONOS    = { Perro: '🐶', Gato: '🐱', Ave: '🐦', Conejo: '🐰', Roedor: '🐹', Reptil: '🦎', Otro: '🐾' };
const sinTildes = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

// Autocompletado genérico: input + lista; buscar(q) devuelve [{html, valor}], elegir(valor)
function autocompletar(input, lista, buscar, elegir) {
    let activa = -1;
    const pintar = () => {
        const res = buscar(sinTildes(input.value.trim()));
        activa = -1;
        lista.innerHTML = res.length ? res.map((r, i) => `<div class="ac-op" data-i="${i}">${r.html}</div>`).join('')
                                     : '<div class="ac-vacio">Sin coincidencias</div>';
        lista.style.display = 'block';
        lista._res = res;
    };
    input.addEventListener('input', pintar);
    input.addEventListener('focus', pintar);
    input.addEventListener('blur', () => setTimeout(() => lista.style.display = 'none', 150));
    lista.addEventListener('mousedown', e => {
        const op = e.target.closest('.ac-op');
        if (op) { e.preventDefault(); elegir(lista._res[op.dataset.i].valor); lista.style.display = 'none'; }
    });
    input.addEventListener('keydown', e => {
        const ops = [...lista.querySelectorAll('.ac-op')];
        if (!ops.length || lista.style.display === 'none') return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            activa = (activa + (e.key === 'ArrowDown' ? 1 : -1) + ops.length) % ops.length;
            ops.forEach((o, i) => o.classList.toggle('activa', i === activa));
        } else if (e.key === 'Enter' && activa >= 0) {
            e.preventDefault(); elegir(lista._res[activa].valor); lista.style.display = 'none';
        }
    });
}

// ── Paciente ──
function edad(nac) {
    if (!nac) return '';
    const d = new Date(nac + 'T00:00:00'), h = new Date();
    let meses = (h.getFullYear() - d.getFullYear()) * 12 + h.getMonth() - d.getMonth() - (h.getDate() < d.getDate() ? 1 : 0);
    return meses >= 12 ? Math.floor(meses / 12) + (Math.floor(meses / 12) === 1 ? ' año' : ' años') : Math.max(meses, 0) + (meses === 1 ? ' mes' : ' meses');
}
function elegirMascota(m) {
    document.getElementById('mascotaId').value = m.id;
    document.getElementById('pacienteBuscar').value = m.nombre + (m.cliente ? ' — ' + m.cliente : '');
    const ficha = document.getElementById('pacienteFicha');
    ficha.classList.remove('d-none');
    ficha.innerHTML = `<strong>${ICONOS[m.especie] || '🐾'} ${escHtml(m.nombre)}</strong> · ${escHtml(m.especie)}${m.raza ? ' · ' + escHtml(m.raza) : ''}`
        + `${m.sexo ? ' · ' + (m.sexo === 'macho' ? 'Macho' : 'Hembra') : ''}${m.fecha_nacimiento ? ' · ' + edad(m.fecha_nacimiento) : ''}`
        + `<div class="text-muted">Propietario: ${escHtml(m.cliente || 'sin registrar')}${m.cliente_telefono ? ' · Tel. ' + escHtml(m.cliente_telefono) : ''}</div>`;
}
autocompletar(document.getElementById('pacienteBuscar'), document.getElementById('pacienteLista'),
    q => MASCOTAS.filter(m => !q || sinTildes(m.nombre + ' ' + (m.cliente || '')).includes(q)).slice(0, 30)
                 .map(m => ({ valor: m, html: `<span>${ICONOS[m.especie] || '🐾'} ${escHtml(m.nombre)}</span><small>${escHtml(m.cliente || 'sin propietario')}</small>` })),
    elegirMascota);
document.getElementById('pacienteBuscar').addEventListener('input', () => {
    document.getElementById('mascotaId').value = '';
    document.getElementById('pacienteFicha').classList.add('d-none');
});
const inicial = MASCOTAS.find(m => String(m.id) === document.getElementById('mascotaId').value);
if (inicial) elegirMascota(inicial);

// ── Médico: propone su última tarjeta profesional ──
document.getElementById('veterinarioId').addEventListener('change', e => {
    const t = TARJETAS[e.target.value];
    if (t) document.getElementById('tarjetaProfesional').value = t;
});
if (!document.getElementById('tarjetaProfesional').value) {
    const t = TARJETAS[document.getElementById('veterinarioId').value];
    if (t) document.getElementById('tarjetaProfesional').value = t;
}

// ── Prescripción ──
function renumerar() {
    document.querySelectorAll('#rxItems .rx-item').forEach((el, i) => el.querySelector('.rx-item-num').textContent = (i + 1) + '.');
}
function marcarOrigen(el, delAlmacen) {
    const tag = el.querySelector('.rx-origen');
    tag.className = 'rx-origen ' + (delAlmacen ? 'almacen' : 'manual');
    tag.textContent = delAlmacen ? 'Del almacén' : 'Otro producto';
    el.querySelector('.it-medicamento').placeholder = delAlmacen ? 'Busca un producto del almacén…' : 'Escribe el nombre del medicamento o producto';
}
function agregarItem(datos = {}) {
    const el = document.getElementById('tplItem').content.firstElementChild.cloneNode(true);
    const campos = ['medicamento', 'presentacion', 'cantidad', 'dosis', 'via', 'frecuencia', 'duracion', 'indicaciones'];
    campos.forEach(c => { if (datos[c]) el.querySelector('.it-' + c).value = datos[c]; });
    el.querySelector('.it-producto').value = datos.producto_id || '';
    marcarOrigen(el, !datos.manual && (!datos.medicamento || !!datos.producto_id));

    const inp = el.querySelector('.it-medicamento');
    autocompletar(inp, el.querySelector('.ac-lista'),
        q => el.querySelector('.rx-origen').classList.contains('manual') || !q ? [] :
             PRODUCTOS.filter(p => sinTildes(p.nombre + ' ' + p.codigo).includes(q)).slice(0, 25)
                      .map(p => ({ valor: p, html: `<span>${escHtml(p.nombre)} <small>${escHtml(p.codigo)}</small></span><small>${p.stock > 0 ? p.stock + ' en stock' : 'sin stock'}</small>` })),
        p => {
            inp.value = p.nombre;
            el.querySelector('.it-producto').value = p.id;
            const pres = el.querySelector('.it-presentacion');
            if (!pres.value) pres.value = p.descripcion || p.unidad;
            marcarOrigen(el, true);
            el.querySelector('.it-dosis').focus();
        });
    // Si se escribe algo distinto al producto elegido, deja de estar vinculado al almacén
    inp.addEventListener('input', () => { el.querySelector('.it-producto').value = ''; });
    el.querySelector('.rx-quitar').addEventListener('click', () => {
        if (document.querySelectorAll('#rxItems .rx-item').length === 1) { campos.forEach(c => el.querySelector('.it-' + c).value = ''); return; }
        el.remove(); renumerar();
    });
    document.getElementById('rxItems').appendChild(el);
    renumerar();
    if (!datos.medicamento) inp.focus();
}
(ITEMS.length ? ITEMS : [{}]).forEach(i => agregarItem(i.medicamento ? i : {}));
window.scrollTo(0, 0);

document.getElementById('formFormula').addEventListener('submit', e => {
    if (!document.getElementById('mascotaId').value) {
        e.preventDefault();
        alert('Selecciona el paciente de la lista.');
        document.getElementById('pacienteBuscar').focus();
    }
});
</script>
</body>
</html>
