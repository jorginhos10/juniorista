<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Órdenes de cirugía</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Órdenes de cirugía</h5>
                <p class="topbar-date mb-0">Programa la cirugía e imprime el consentimiento informado</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <button class="btn btn-primary-custom btn-sm" onclick="nuevaOrden()">
                    <i class="bi bi-plus-lg me-1"></i> Nueva orden
                </button>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <div class="dropdown-header">
                                <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($usuario['email']) ?></div>
                                <span class="badge badge-rol mt-1"><?= htmlspecialchars(ucfirst($usuario['rol'])) ?></span>
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
            <div class="alert alert-success py-2 mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <h6 class="fw-bold mb-0">Órdenes <span class="text-muted fw-normal small">(<?= count($ordenes) ?>)</span></h6>
                    <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar mascota, cirugía o responsable…" style="width:280px">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Mascota</th>
                                    <th>Cirugía</th>
                                    <th>Responsable</th>
                                    <th>Veterinario</th>
                                    <th class="text-center">Exámenes pre-quirúrgicos</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" style="width:150px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordenes as $o): [$etq, $color] = $estados[$o['estado']] ?? [$o['estado'], 'secondary']; ?>
                                <tr>
                                    <td class="ps-3 small text-nowrap"><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($o['mascota_nombre']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars(trim($o['especie'] . ($o['raza'] ? ' · ' . $o['raza'] : ''))) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($o['procedimiento']) ?></td>
                                    <td class="small">
                                        <?= htmlspecialchars($o['responsable_nombre'] ?: '—') ?>
                                        <?php if ($o['responsable_cc']): ?><div class="text-muted">C.C. <?= htmlspecialchars($o['responsable_cc']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($o['veterinario_nombre'] ?? '—') ?></td>
                                    <td class="text-center">
                                        <?= $o['examenes_prequirurgicos']
                                            ? '<span class="badge bg-success-subtle text-success-emphasis">Realizados</span>'
                                            : '<span class="badge bg-danger-subtle text-danger-emphasis">No realizados</span>' ?>
                                    </td>
                                    <td class="text-center"><span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><?= $etq ?></span></td>
                                    <td class="text-center text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary btn-accion" title="Imprimir consentimiento" target="_blank"
                                           href="<?= BASE_URL ?>/ordenes-cirugia/consentimiento?id=<?= (int)$o['id'] ?>"><i class="bi bi-printer"></i></a>
                                        <?php if ($o['estado'] === 'programada'): ?>
                                        <button class="btn btn-sm btn-outline-secondary btn-accion" title="Editar" onclick="editarOrden(<?= (int)$o['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-success btn-accion" title="Marcar como realizada" onclick="estado(<?= (int)$o['id'] ?>, 'realizada')"><i class="bi bi-check-lg"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-accion" title="Cancelar" onclick="estado(<?= (int)$o['id'] ?>, 'cancelada')"><i class="bi bi-x-lg"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr id="filaVacia" style="<?= $ordenes ? 'display:none' : '' ?>">
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-bandaid fs-2 d-block mb-2 opacity-25"></i>
                                        <span id="textoVacio">Aún no hay órdenes de cirugía.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL NUEVA ORDEN ─────────────────────────────── -->
<div class="modal fade" id="modalOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/ordenes-cirugia/guardar" autocomplete="off" id="formOrden">
                <input type="hidden" name="mascota_id" id="o-mascota_id" value="0">
                <input type="hidden" name="id" id="o-id" value="0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-bandaid text-primary me-2"></i><span id="o-titulo">Nueva orden de cirugía</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Mascota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="mascota_nombre" id="o-nombre" maxlength="100" required list="o-mascotas" placeholder="Busca o escribe el nombre">
                            <datalist id="o-mascotas"></datalist>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Especie</label>
                            <input type="text" class="form-control" name="especie" id="o-especie" maxlength="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Raza</label>
                            <input type="text" class="form-control" name="raza" id="o-raza" maxlength="100">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Responsable del paciente</label>
                            <input type="text" class="form-control" name="responsable_nombre" id="o-resp" maxlength="200" list="o-responsables" placeholder="Busca o escribe el nombre">
                            <datalist id="o-responsables"></datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">C.C.</label>
                            <input type="text" class="form-control" name="responsable_cc" id="o-cc" maxlength="30">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Cirugía o procedimiento <span class="text-danger">*</span></label>
                            <select class="form-select" id="o-proc-sel" required>
                                <option value="">Selecciona…</option>
                                <?php foreach ($procedimientos as $p): ?><option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
                                <option value="__otro">Otro (escribir)…</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" name="procedimiento" id="o-proc" maxlength="150" placeholder="Escribe la cirugía o procedimiento">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha</label>
                            <input type="date" class="form-control" name="fecha" value="<?= $hoy ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Veterinario</label>
                            <?php $miVet = current(array_filter($veterinarios, fn($v) => (int)$v['id'] === (int)$_SESSION['usuario_id'])) ?: null; ?>
                            <input type="text" class="form-control" id="o-vet" list="o-vets" maxlength="100" placeholder="Busca el veterinario"
                                   value="<?= htmlspecialchars($miVet['nombre'] ?? '') ?>">
                            <input type="hidden" name="veterinario_id" id="o-vet-id" value="<?= (int)($miVet['id'] ?? 0) ?>">
                            <datalist id="o-vets">
                                <?php foreach ($veterinarios as $v): ?><option value="<?= htmlspecialchars($v['nombre']) ?>"><?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="examenes_prequirurgicos" id="o-examenes" value="1">
                                <label class="form-check-label fw-semibold" for="o-examenes">Se realizaron los exámenes pre-quirúrgicos</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" class="form-control" name="notas" maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-printer me-1"></i><span id="o-btn">Guardar e imprimir consentimiento</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/ordenes-cirugia/estado" id="formEstado" class="d-none">
    <input type="hidden" name="id"><input type="hidden" name="estado">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $id = id => document.getElementById(id);
const MASCOTAS = <?= json_encode(array_map(fn($m) => [
    'id' => (int)$m['id'], 'nombre' => $m['nombre'], 'especie' => (string)$m['especie'], 'raza' => (string)($m['raza'] ?? ''),
    'cliente_id' => (int)($m['cliente_id'] ?? 0),
    'cliente' => (string)($m['cliente_nombre'] ?? ''), 'cc' => (string)($m['cliente_dni'] ?? ''),
], $mascotas), $jsonFlags) ?>;

const VETERINARIOS = <?= json_encode(array_map(fn($v) => ['id' => (int)$v['id'], 'nombre' => $v['nombre']], $veterinarios), $jsonFlags) ?>;
// Veterinario: el nombre escrito se enlaza al id solo si coincide con uno de la lista
$id('o-vet').addEventListener('input', () => {
    const v = VETERINARIOS.find(x => x.nombre.toLowerCase() === $id('o-vet').value.trim().toLowerCase());
    $id('o-vet-id').value = v ? v.id : 0;
});

const norm = s => String(s || '').trim().toLowerCase();
// Responsables únicos (clientes que tienen mascotas registradas)
const RESPONSABLES = [...new Map(MASCOTAS.filter(m => m.cliente_id).map(m => [m.cliente_id, { id: m.cliente_id, nombre: m.cliente, cc: m.cc }])).values()];

const opcion = (valor, etiqueta) => { const o = document.createElement('option'); o.value = valor; o.label = etiqueta; return o; };

// Responsable escrito/elegido (si coincide con un cliente registrado)
const responsableActual = () => RESPONSABLES.find(r => norm(r.nombre) === norm($id('o-resp').value));

// Cada campo sugiere según el otro: con un responsable elegido, la mascota solo ofrece sus mascotas; con una mascota, el responsable ofrece a sus dueños
function pintarSugerencias() {
    const r = responsableActual();
    const mascotas = r ? MASCOTAS.filter(m => m.cliente_id === r.id) : MASCOTAS;
    const dl = $id('o-mascotas');
    dl.replaceChildren(...mascotas.map(m => opcion(m.nombre, [m.cliente ? 'Dueño: ' + m.cliente : 'Sin dueño', m.especie].filter(Boolean).join(' · '))));

    const nombre = norm($id('o-nombre').value);
    const duenos = nombre ? MASCOTAS.filter(m => norm(m.nombre) === nombre && m.cliente_id).map(m => m.cliente_id) : [];
    const lista = duenos.length ? RESPONSABLES.filter(x => duenos.includes(x.id)) : RESPONSABLES;
    $id('o-responsables').replaceChildren(...lista.map(x => opcion(x.nombre, x.cc ? 'C.C. ' + x.cc : '')));
}

function aplicarMascota(m) {
    $id('o-mascota_id').value = m.id;
    $id('o-nombre').value  = m.nombre;
    $id('o-especie').value = m.especie;
    $id('o-raza').value    = m.raza;
    if (m.cliente) { $id('o-resp').value = m.cliente; $id('o-cc').value = m.cc; }
}

const ORDENES = <?= json_encode(array_map(fn($o) => [
    'id' => (int)$o['id'], 'fecha' => $o['fecha'], 'mascota_id' => (int)($o['mascota_id'] ?? 0), 'mascota_nombre' => $o['mascota_nombre'],
    'especie' => $o['especie'], 'raza' => $o['raza'], 'responsable_nombre' => $o['responsable_nombre'], 'responsable_cc' => $o['responsable_cc'],
    'procedimiento' => $o['procedimiento'], 'veterinario_id' => (int)($o['veterinario_id'] ?? 0),
    'examenes' => (int)$o['examenes_prequirurgicos'], 'notas' => $o['notas'],
], $ordenes), $jsonFlags) ?>;

// Cirugía: lista de la tarifa o texto libre. El campo «procedimiento» siempre lleva el valor final que se guarda
const PROCEDIMIENTOS = <?= json_encode($procedimientos, $jsonFlags) ?>;
function fijarProcedimiento(texto) {
    const sel = $id('o-proc-sel'), inp = $id('o-proc');
    const enLista = PROCEDIMIENTOS.includes(texto);
    sel.value = !texto ? '' : (enLista ? texto : '__otro');
    inp.value = texto || '';
    mostrarOtro();
}
function mostrarOtro() {
    const sel = $id('o-proc-sel'), inp = $id('o-proc'), otro = sel.value === '__otro';
    inp.classList.toggle('d-none', !otro);
    inp.required = otro;
    if (!otro) inp.value = sel.value;
}
$id('o-proc-sel').addEventListener('change', () => {
    const otro = $id('o-proc-sel').value === '__otro';
    if (otro) $id('o-proc').value = '';
    mostrarOtro();
    if (otro) $id('o-proc').focus();
});

// Editar: solo órdenes programadas; reutiliza el formulario con los datos de la orden
function editarOrden(id) {
    const o = ORDENES.find(x => x.id === id);
    if (!o) return;
    const f = $id('formOrden');
    f.reset();
    $id('o-id').value         = o.id;
    $id('o-mascota_id').value = o.mascota_id;
    $id('o-nombre').value     = o.mascota_nombre;
    $id('o-especie').value    = o.especie;
    $id('o-raza').value       = o.raza;
    $id('o-resp').value       = o.responsable_nombre;
    $id('o-cc').value         = o.responsable_cc;
    fijarProcedimiento(o.procedimiento);
    f.fecha.value             = o.fecha;
    $id('o-vet-id').value     = o.veterinario_id;
    $id('o-vet').value        = (VETERINARIOS.find(v => v.id === o.veterinario_id) || {}).nombre || '';
    $id('o-examenes').checked = !!o.examenes;
    f.notas.value             = o.notas;
    $id('o-titulo').textContent = 'Editar orden de cirugía';
    $id('o-btn').textContent    = 'Guardar cambios';
    pintarSugerencias();
    bootstrap.Modal.getOrCreateInstance($id('modalOrden')).show();
}

function nuevaOrden() {
    $id('formOrden').reset();
    $id('o-id').value = 0;
    fijarProcedimiento('');
    $id('o-titulo').textContent = 'Nueva orden de cirugía';
    $id('o-btn').textContent    = 'Guardar e imprimir consentimiento';
    $id('o-mascota_id').value = 0;
    pintarSugerencias();
    bootstrap.Modal.getOrCreateInstance($id('modalOrden')).show();
    setTimeout(() => $id('o-nombre').focus(), 300);
}

// Mascota elegida → completa el responsable y los demás datos (si hay varias con ese nombre, prefiere la del responsable ya puesto)
$id('o-nombre').addEventListener('input', () => {
    const r = responsableActual();
    const candidatas = MASCOTAS.filter(x => norm(x.nombre) === norm($id('o-nombre').value));
    const m = candidatas.find(x => r && x.cliente_id === r.id) || candidatas[0];
    if (m) aplicarMascota(m); else $id('o-mascota_id').value = 0;
    pintarSugerencias();
});

// Responsable elegido → completa la cédula y, si solo tiene una mascota (o ya hay una suya escrita), la mascota
$id('o-resp').addEventListener('input', () => {
    const r = responsableActual();
    if (r) {
        $id('o-cc').value = r.cc;
        const suyas = MASCOTAS.filter(m => m.cliente_id === r.id);
        const actual = suyas.find(m => norm(m.nombre) === norm($id('o-nombre').value));
        if (actual) aplicarMascota(actual);
        else if (suyas.length === 1 && !$id('o-nombre').value.trim()) aplicarMascota(suyas[0]);
    }
    pintarSugerencias();
});

pintarSugerencias();

function estado(id, nuevo) {
    if (!confirm(nuevo === 'realizada' ? '¿Marcar la cirugía como realizada?' : '¿Cancelar esta orden?')) return;
    const f = $id('formEstado');
    f.id.value = id;
    f.estado.value = nuevo;
    f.submit();
}

$id('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let n = 0;
    document.querySelectorAll('#tabla tbody tr:not(#filaVacia)').forEach(tr => {
        const ok = tr.textContent.toLowerCase().includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) n++;
    });
    $id('filaVacia').style.display = n ? 'none' : '';
    $id('textoVacio').textContent = q ? 'Ninguna orden coincide con la búsqueda.' : 'Aún no hay órdenes de cirugía.';
});

// Llegó desde el Historial con una mascota: abre la nueva orden ya rellenada
const MASCOTA_INICIAL = <?= (int)$mascotaInicial ?>;
if (MASCOTA_INICIAL) {
    const m = MASCOTAS.find(x => x.id === MASCOTA_INICIAL);
    if (m) { nuevaOrden(); aplicarMascota(m); pintarSugerencias(); }
}
</script>
</body>
</html>
