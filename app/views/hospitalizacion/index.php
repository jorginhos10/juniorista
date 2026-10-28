<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$e = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($f) => $f ? date('d/m/Y H:i', strtotime($f)) : '—';
// Días de estancia: el día de ingreso cuenta como día 1
$dias = fn($h) => max(1, (int)(new DateTime(date('Y-m-d', strtotime($h['fecha_ingreso']))))->diff(new DateTime(date('Y-m-d', strtotime($h['fecha_salida'] ?: 'now'))))->days + 1);
$icono = fn($esp) => strtolower((string)$esp) === 'gato' ? '🐱' : '🐶';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Hospitalización</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .mc { position: relative; }
        .mc-lista { display: none; position: absolute; left: 0; right: 0; top: calc(100% + 2px); z-index: 1060; max-height: 260px; overflow-y: auto;
                    background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .14); }
        .mc-op { display: flex; align-items: center; gap: .6rem; padding: .45rem .75rem; cursor: pointer; font-size: .9rem; }
        .mc-op:hover, .mc-op.activa { background: #eef2ff; }
        .mc-op.bloqueada { opacity: .5; cursor: not-allowed; }
        .mc-op small { display: block; color: #94a3b8; font-size: .75rem; }
        .mc-vacio { padding: .6rem .75rem; font-size: .85rem; color: #94a3b8; }
        .tablero { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
        .paciente { background: #fff; border: 1px solid #e5e7eb; border-left: 4px solid #f59e0b; border-radius: .75rem; padding: 1rem; display: flex; flex-direction: column; gap: .6rem;
                    text-decoration: none; color: inherit; transition: box-shadow .15s, transform .15s; }
        .paciente:hover { box-shadow: 0 6px 20px rgba(15,23,42,.1); transform: translateY(-2px); color: inherit; }
        .paciente-ico { width: 44px; height: 44px; border-radius: 12px; background: #fef3c7; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .paciente-dias { margin-left: auto; text-align: center; background: #fff7ed; color: #c2410c; border-radius: .6rem; padding: .15rem .6rem; line-height: 1.1; }
        .paciente-dias b { display: block; font-size: 1.15rem; }
        .paciente-dias small { font-size: .65rem; text-transform: uppercase; letter-spacing: .05em; }
        .paciente-dx { font-size: .85rem; color: #374151; }
        .paciente-meta { display: flex; flex-wrap: wrap; gap: .35rem; font-size: .75rem; }
        .chip { background: #f1f5f9; color: #475569; border-radius: 999px; padding: .1rem .55rem; }
        .chip.hoy { background: #dcfce7; color: #166534; font-weight: 600; }
        .chip.sin { background: #fee2e2; color: #991b1b; font-weight: 600; }
        .paciente-ultima { font-size: .78rem; color: #6b7280; border-top: 1px dashed #e5e7eb; padding-top: .5rem; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Hospitalización</h5>
                <p class="topbar-date mb-0"><?= count($internados) ?> paciente<?= count($internados) === 1 ? '' : 's' ?> internado<?= count($internados) === 1 ? '' : 's' ?> · hoja ECOP de medicación</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <button class="btn btn-primary-custom btn-sm" onclick="nuevaHospitalizacion()">
                    <i class="bi bi-plus-lg me-1"></i> Hospitalizar paciente
                </button>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <div class="dropdown-header">
                                <div class="fw-semibold"><?= $e($usuario['nombre']) ?></div>
                                <div class="text-muted small"><?= $e($usuario['email']) ?></div>
                                <span class="badge badge-rol mt-1"><?= $e(ucfirst($usuario['rol'])) ?></span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout" onclick="return confirm('¿Cerrar sesión?')">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="main-content">

            <?php if (!empty($success)): ?>
            <div class="alert alert-success py-2 mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= $e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $e($error) ?></div>
            <?php endif; ?>

            <!-- Tablero de internados -->
            <?php if ($internados): ?>
            <div class="tablero mb-4">
                <?php foreach ($internados as $h): [$ult_med, $ult_hora, $ult_dia] = array_pad(explode('|', (string)$h['ultima_aplicacion']), 3, ''); ?>
                <a class="paciente" href="<?= BASE_URL ?>/hospitalizacion/ver?id=<?= (int)$h['id'] ?>">
                    <div class="d-flex align-items-center gap-2">
                        <span class="paciente-ico"><?= $icono($h['especie']) ?></span>
                        <div>
                            <div class="fw-bold fs-6"><?= $e($h['mascota_nombre']) ?></div>
                            <div class="small text-muted"><?= $e(trim($h['especie'] . ($h['raza'] ? ' · ' . $h['raza'] : ''))) ?></div>
                        </div>
                        <div class="paciente-dias"><b><?= $dias($h) ?></b><small>día</small></div>
                    </div>
                    <div class="paciente-dx"><i class="bi bi-clipboard2-pulse me-1 text-muted"></i><?= $e($h['diagnostico']) ?></div>
                    <div class="paciente-meta">
                        <span class="chip"><i class="bi bi-calendar-event me-1"></i>Ingreso <?= date('d/m H:i', strtotime($h['fecha_ingreso'])) ?></span>
                        <?php if ($h['cliente_nombre']): ?><span class="chip"><i class="bi bi-person me-1"></i><?= $e($h['cliente_nombre']) ?></span><?php endif; ?>
                        <span class="chip <?= (int)$h['aplicaciones_hoy'] > 0 ? 'hoy' : 'sin' ?>"><?= (int)$h['aplicaciones_hoy'] ?> aplicación<?= (int)$h['aplicaciones_hoy'] === 1 ? '' : 'es' ?> hoy</span>
                    </div>
                    <div class="paciente-ultima">
                        <?= $ult_med !== '' ? 'Última: <b>' . $e($ult_med) . '</b> · ' . $e($ult_dia) . ' ' . $e($ult_hora) : 'Aún sin medicación registrada' ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-hospital fs-1 d-block mb-2 opacity-25"></i>
                    No hay pacientes internados.
                    <div class="mt-3"><button class="btn btn-primary-custom btn-sm" onclick="nuevaHospitalizacion()"><i class="bi bi-plus-lg me-1"></i>Hospitalizar paciente</button></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Historial de egresos -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <h6 class="fw-bold mb-0">Egresos recientes <span class="text-muted fw-normal small">(<?= count($egresos) ?>)</span></h6>
                    <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar mascota o diagnóstico…" style="width:260px">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Mascota</th>
                                    <th>Diagnóstico</th>
                                    <th>Ingreso</th>
                                    <th>Salida</th>
                                    <th class="text-center">Días</th>
                                    <th class="text-center">Aplicaciones</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" style="width:100px">ECOP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($egresos as $h): [$etq, $color] = $estados[$h['estado']] ?? [$h['estado'], 'secondary']; ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= $icono($h['especie']) ?> <?= $e($h['mascota_nombre']) ?></td>
                                    <td class="small"><?= $e($h['diagnostico']) ?></td>
                                    <td class="small text-nowrap"><?= $fmt($h['fecha_ingreso']) ?></td>
                                    <td class="small text-nowrap"><?= $fmt($h['fecha_salida']) ?></td>
                                    <td class="text-center"><?= $dias($h) ?></td>
                                    <td class="text-center"><?= (int)$h['total_aplicaciones'] ?></td>
                                    <td class="text-center"><span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><?= $etq ?></span></td>
                                    <td class="text-center text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary btn-accion" title="Ver hoja ECOP" href="<?= BASE_URL ?>/hospitalizacion/ver?id=<?= (int)$h['id'] ?>"><i class="bi bi-eye"></i></a>
                                        <a class="btn btn-sm btn-outline-secondary btn-accion" title="Imprimir ECOP" target="_blank" href="<?= BASE_URL ?>/hospitalizacion/imprimir?id=<?= (int)$h['id'] ?>"><i class="bi bi-printer"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr id="filaVacia" style="<?= $egresos ? 'display:none' : '' ?>">
                                    <td colspan="8" class="text-center text-muted py-4"><span id="textoVacio">Aún no hay egresos.</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL HOSPITALIZAR ────────────────────────────── -->
<div class="modal fade" id="modalHosp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/hospitalizacion/guardar" autocomplete="off" id="formHosp">
                <input type="hidden" name="mascota_id" id="h-mascota_id" value="0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-hospital text-primary me-2"></i>Hospitalizar paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mascota <span class="text-danger">*</span></label>
                            <div class="mc">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="h-nombre" required placeholder="Selecciona o busca la mascota…" role="combobox" aria-expanded="false" aria-controls="h-lista">
                                </div>
                                <div class="mc-lista" id="h-lista" role="listbox"></div>
                            </div>
                            <div class="form-text" id="h-info">Elige una mascota registrada de la lista.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Diagnóstico / motivo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="diagnostico" id="h-dx" maxlength="255" required placeholder="Ej: Gastroenteritis hemorrágica">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha y hora de ingreso</label>
                            <input type="datetime-local" class="form-control" name="fecha_ingreso" value="<?= $ahora ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Peso (kg)</label>
                            <input type="number" class="form-control" name="peso" step="0.01" min="0" id="h-peso">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Veterinario responsable</label>
                            <?php $miVet = current(array_filter($veterinarios, fn($v) => (int)$v['id'] === (int)$_SESSION['usuario_id'])) ?: null; ?>
                            <input type="text" class="form-control" id="h-vet" list="h-vets" value="<?= $e($miVet['nombre'] ?? '') ?>" placeholder="Busca el veterinario">
                            <input type="hidden" name="veterinario_id" id="h-vet-id" value="<?= (int)($miVet['id'] ?? 0) ?>">
                            <datalist id="h-vets"><?php foreach ($veterinarios as $v): ?><option value="<?= $e($v['nombre']) ?>"><?php endforeach; ?></datalist>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" class="form-control" name="notas" maxlength="255" placeholder="Jaula, dieta, cuidados especiales…">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-clipboard2-plus me-1"></i>Ingresar y abrir hoja ECOP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $id = id => document.getElementById(id);
const MASCOTAS = <?= json_encode(array_map(fn($m) => [
    'id' => (int)$m['id'], 'nombre' => $m['nombre'], 'cliente' => (string)($m['cliente_nombre'] ?? ''),
    'etiqueta' => $m['nombre'] . ($m['cliente_nombre'] ? ' — ' . $m['cliente_nombre'] : ''),
    'especie' => (string)$m['especie'], 'raza' => (string)($m['raza'] ?? ''),
], $mascotas), $jsonFlags) ?>;
const VETERINARIOS = <?= json_encode(array_map(fn($v) => ['id' => (int)$v['id'], 'nombre' => $v['nombre']], $veterinarios), $jsonFlags) ?>;
const INTERNADOS = <?= json_encode(array_map(fn($h) => (int)$h['mascota_id'], $internados)) ?>;

function nuevaHospitalizacion(mascota) {
    $id('formHosp').reset();
    $id('h-mascota_id').value = 0;
    mensajeMascota('');
    bootstrap.Modal.getOrCreateInstance($id('modalHosp')).show();
    setTimeout(() => {
        if (mascota && !INTERNADOS.includes(mascota.id)) elegirMascota(mascota);
        else $id('h-nombre').focus();
    }, 300);
}

// Mascota: lista desplegable con búsqueda (nombre, dueño o especie) solo sobre las mascotas registradas
let activa = -1;
const esc = t => escHtml(t);

function etiquetaInfo(m) {
    return `${m.especie}${m.raza ? ' · ' + m.raza : ''}${m.cliente ? ' · Dueño: ' + m.cliente : ''}`;
}

function filtrar() {
    const q = $id('h-nombre').value.trim().toLowerCase();
    return MASCOTAS.filter(m => !q || (m.nombre + ' ' + m.cliente + ' ' + m.especie + ' ' + m.raza).toLowerCase().includes(q)).slice(0, 40);
}

function abrirLista() {
    const res = filtrar();
    activa = res.findIndex(m => !INTERNADOS.includes(m.id));
    $id('h-lista').innerHTML = res.length
        ? res.map((m, i) => {
            const dentro = INTERNADOS.includes(m.id);
            return `<div class="mc-op ${dentro ? 'bloqueada' : ''} ${i === activa ? 'activa' : ''}" role="option" data-id="${m.id}">
                <span>${m.especie.toLowerCase() === 'gato' ? '🐱' : '🐶'}</span>
                <div><b>${esc(m.nombre)}</b>${dentro ? ' <span class="badge bg-warning-subtle text-warning-emphasis">Internado</span>' : ''}
                <small>${esc([m.cliente ? 'Dueño: ' + m.cliente : 'Sin dueño', m.especie, m.raza].filter(Boolean).join(' · '))}</small></div></div>`;
        }).join('')
        : '<div class="mc-vacio">Ninguna mascota registrada coincide</div>';
    $id('h-lista').style.display = 'block';
    $id('h-nombre').setAttribute('aria-expanded', 'true');
}
function cerrarLista() { $id('h-lista').style.display = 'none'; $id('h-nombre').setAttribute('aria-expanded', 'false'); }

function elegirMascota(m) {
    $id('h-mascota_id').value = m.id;
    $id('h-nombre').value = m.nombre + (m.cliente ? ' — ' + m.cliente : '');
    $id('h-info').textContent = etiquetaInfo(m);
    $id('h-info').className = 'form-text';
    cerrarLista();
    $id('h-dx').focus();
}

function mensajeMascota(texto) {
    $id('h-info').innerHTML = texto ? `<span class="text-danger">${texto}</span>` : 'Elige una mascota registrada de la lista.';
}

// Escribir solo filtra: la mascota queda elegida únicamente al seleccionarla de la lista
$id('h-nombre').addEventListener('input', () => { $id('h-mascota_id').value = 0; mensajeMascota(''); abrirLista(); });
$id('h-nombre').addEventListener('focus', abrirLista);
$id('h-nombre').addEventListener('blur', () => setTimeout(() => {
    cerrarLista();
    if (!Number($id('h-mascota_id').value)) $id('h-nombre').value = '';   // sin selección no queda texto suelto
}, 150));
$id('h-nombre').addEventListener('keydown', e => {
    const ops = [...$id('h-lista').querySelectorAll('.mc-op:not(.bloqueada)')];
    if (e.key === 'Escape') { e.stopPropagation(); cerrarLista(); return; }
    if (!ops.length || !['ArrowDown', 'ArrowUp', 'Enter'].includes(e.key)) return;
    e.preventDefault();
    let i = ops.findIndex(o => o.classList.contains('activa'));
    if (e.key === 'Enter') { if (i < 0) i = 0; elegirMascota(MASCOTAS.find(m => m.id === Number(ops[i].dataset.id))); return; }
    i = e.key === 'ArrowDown' ? Math.min(ops.length - 1, i + 1) : Math.max(0, i - 1);
    ops.forEach(o => o.classList.remove('activa'));
    ops[i].classList.add('activa');
    ops[i].scrollIntoView({ block: 'nearest' });
});
$id('h-lista').addEventListener('mousedown', e => {
    const op = e.target.closest('.mc-op');
    e.preventDefault();
    if (!op) return;
    if (op.classList.contains('bloqueada')) { mensajeMascota('Esta mascota ya está internada.'); return; }
    elegirMascota(MASCOTAS.find(m => m.id === Number(op.dataset.id)));
});
$id('formHosp').addEventListener('submit', e => {
    if (!Number($id('h-mascota_id').value)) { e.preventDefault(); mensajeMascota('Elige una mascota registrada de la lista.'); $id('h-nombre').focus(); }
});

$id('h-vet').addEventListener('input', () => {
    const v = VETERINARIOS.find(x => x.nombre.toLowerCase() === $id('h-vet').value.trim().toLowerCase());
    $id('h-vet-id').value = v ? v.id : 0;
});

$id('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let n = 0;
    document.querySelectorAll('#tabla tbody tr:not(#filaVacia)').forEach(tr => {
        const ok = tr.textContent.toLowerCase().includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) n++;
    });
    $id('filaVacia').style.display = n ? 'none' : '';
    $id('textoVacio').textContent = q ? 'Ningún egreso coincide con la búsqueda.' : 'Aún no hay egresos.';
});

// Llegó desde la historia médica con una mascota: abre el ingreso ya rellenado
const MASCOTA_INICIAL = <?= (int)$mascotaInicial ?>;
if (MASCOTA_INICIAL) {
    const m = MASCOTAS.find(x => x.id === MASCOTA_INICIAL);
    if (m) nuevaHospitalizacion(m);
}
</script>
</body>
</html>
