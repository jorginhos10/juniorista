<?php
// Órdenes de laboratorio del doctor: informativo. Sin costos, abonos ni importes; solo exámenes y el valor a cobrar que se manda.
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$estados = [
    'por_cobrar' => ['Por cobrar',   'warning'],
    'parcial'    => ['Pago parcial', 'info'],
    'pagada'     => ['Pagada',       'success'],
    'cancelada'  => ['Cancelada',    'secondary'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Órdenes de laboratorio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .especie-ico { width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .especie-perro { background: #fef3c7; }
        .especie-gato  { background: #ede9fe; }
        .ex-ac { position: relative; }
        .ex-lista { display: none; position: absolute; left: 0; right: 0; top: calc(100% + 2px); z-index: 1060; max-height: 240px; overflow-y: auto;
                    background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .14); }
        .ex-op { padding: .45rem .75rem; font-size: .88rem; cursor: pointer; }
        .ex-op:hover, .ex-op.activa { background: #eef2ff; }
        .ex-op small { display: block; color: #94a3b8; font-size: .75rem; }
        .ex-vacio { padding: .55rem .75rem; font-size: .84rem; color: #94a3b8; }
        .ex-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .3rem .15rem .6rem; border-radius: 999px;
                   background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; font-size: .8rem; font-weight: 600; }
        .ex-chip button { border: 0; background: transparent; color: #6366f1; width: 20px; height: 20px; border-radius: 50%; padding: 0; line-height: 1; cursor: pointer; }
        .ex-chip button:hover { background: #c7d2fe; color: #312e81; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Órdenes de laboratorio</h5>
                <p class="topbar-date mb-0">Envía los exámenes y el valor a cobrar; caja se encarga del pago</p>
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
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i><span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i><span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <h6 class="fw-bold mb-0">Mis órdenes <span class="text-muted fw-normal small">(<?= count($ordenes) ?>)</span></h6>
                    <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar mascota o examen…" style="width:240px">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Mascota</th>
                                    <th>Exámenes</th>
                                    <?php if ($esAdmin): ?><th>Solicitó</th><?php endif; ?>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center" style="width:90px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordenes as $o): [$etq, $color] = $estados[$o['estado']]; ?>
                                <tr>
                                    <td class="ps-3 small text-nowrap"><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="especie-ico especie-<?= $o['especie'] ?>"><?= $o['especie'] === 'gato' ? '🐱' : '🐶' ?></span>
                                            <div class="fw-semibold"><?= htmlspecialchars($o['nombre']) ?></div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($o['descripcion']) ?></td>
                                    <?php if ($esAdmin): ?><td class="small text-muted"><?= htmlspecialchars($o['solicito']) ?></td><?php endif; ?>
                                    <td class="text-center"><span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><?= $etq ?></span></td>
                                    <td class="text-center">
                                        <?php if ($o['estado'] === 'por_cobrar'): ?>
                                        <button class="btn btn-sm btn-outline-danger btn-accion" title="Anular orden"
                                                onclick='anular(<?= $o['id'] ?>, <?= json_encode($o['nombre'] . ' · ' . $o['descripcion'], $jsonFlags) ?>)'>
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr id="filaVacia" style="<?= $ordenes ? 'display:none' : '' ?>">
                                    <td colspan="<?= $esAdmin ? 6 : 5 ?>" class="text-center text-muted py-5">
                                        <i class="bi bi-eyedropper fs-2 d-block mb-2 opacity-25"></i>
                                        <span id="textoVacio">Aún no has enviado órdenes de laboratorio.</span>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/ordenes-laboratorio/guardar" autocomplete="off" id="formOrden">
                <input type="hidden" name="mascota_id" id="o-mascota_id" value="0">
                <input type="hidden" name="cliente_id" id="o-cliente_id" value="0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-eyedropper text-primary me-2"></i>Nueva orden de laboratorio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold" for="o-nombre">Mascota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="o-nombre" maxlength="100" required list="o-mascotas" placeholder="Nombre de la mascota">
                            <datalist id="o-mascotas">
                                <?php foreach ($mascotas as $m): ?>
                                <option value="<?= htmlspecialchars($m['nombre']) ?>" label="<?= htmlspecialchars(trim(($m['cliente'] ? 'Dueño: ' . $m['cliente'] : 'Sin dueño') . ' · ' . $m['especie'])) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Especie <span class="text-danger">*</span></label>
                            <select class="form-select" name="especie" id="o-especie" required>
                                <?php foreach ($especies as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="o-buscar">Exámenes <span class="text-danger">*</span></label>
                            <div class="ex-ac">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="o-buscar" placeholder="Buscar examen o perfil por sigla o nombre…">
                                </div>
                                <div class="ex-lista" id="o-lista"></div>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-2" id="o-chips"></div>
                            <div id="o-hidden"></div>
                            <div class="form-text" id="o-ayuda"><?= $examenes ? 'Puedes elegir varios exámenes o perfiles.' : 'Aún no hay exámenes en el catálogo; pide a administración que los configure.' ?></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="o-valor">Valor a cobrar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="valor" id="o-valor" step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="o-nota">Nota <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" class="form-control" name="nota" id="o-nota" maxlength="120" placeholder="Ej: urgente">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-send me-1"></i>Enviar a caja</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/ordenes-laboratorio/anular" id="formAnular" class="d-none"><input type="hidden" name="id"></form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $id = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const EXAMENES = <?= json_encode($examenes, $jsonFlags) ?>;
const MASCOTAS = <?= json_encode(array_map(fn($m) => ['id' => (int)$m['id'], 'nombre' => $m['nombre'], 'especie' => strtolower((string)$m['especie']), 'cliente_id' => (int)($m['cliente_id'] ?? 0)], $mascotas), $jsonFlags) ?>;
let elegidos = [];

function nuevaOrden() {
    $id('formOrden').reset();
    elegidos = [];
    $id('o-mascota_id').value = 0;
    $id('o-cliente_id').value = 0;
    pintar();
    bootstrap.Modal.getOrCreateInstance($id('modalOrden')).show();
    setTimeout(() => $id('o-nombre').focus(), 300);
}

// Mascota registrada: al elegirla del listado se anclan mascota, dueño y especie
$id('o-nombre').addEventListener('input', () => {
    const m = MASCOTAS.find(x => x.nombre.toLowerCase() === $id('o-nombre').value.trim().toLowerCase());
    $id('o-mascota_id').value = m ? m.id : 0;
    $id('o-cliente_id').value = m ? m.cliente_id : 0;
    if (m && [...$id('o-especie').options].some(o => o.value === m.especie)) $id('o-especie').value = m.especie;
});

function pintar() {
    $id('o-chips').innerHTML = elegidos.map(x => `<span class="ex-chip" title="${esc(x.nombre)}">${esc(x.codigo)}<button type="button" data-id="${x.id}" aria-label="Quitar ${esc(x.codigo)}">&times;</button></span>`).join('');
    $id('o-hidden').innerHTML = elegidos.map(x => `<input type="hidden" name="examenes[]" value="${x.id}">`).join('');
}

// Un combo no se mezcla con otros combos ni con exámenes que ya incluye (se cobrarían dos veces)
function ofrecer(x) {
    if (elegidos.some(e => e.id === x.id)) return false;
    if (x.combo) return !elegidos.some(e => e.combo || x.incluye_ids.includes(e.id));
    return !elegidos.some(e => e.combo && e.incluye_ids.includes(x.id));
}

function abrir() {
    const q = $id('o-buscar').value.trim().toLowerCase();
    const res = EXAMENES.filter(x => ofrecer(x) && (!q || (x.codigo + ' ' + x.nombre).toLowerCase().includes(q))).slice(0, 15);
    $id('o-lista').innerHTML = res.length
        ? res.map(x => `<div class="ex-op" data-id="${x.id}">${esc(x.codigo)} · ${esc(x.nombre)}${x.combo ? ' <span class="badge bg-primary-subtle text-primary">Perfil</span>' : ''}
            ${(x.combo && x.incluye) || x.muestra ? `<small>${esc([x.combo && x.incluye ? 'Incluye ' + x.incluye : '', x.muestra].filter(Boolean).join(' · '))}</small>` : ''}</div>`).join('')
        : '<div class="ex-vacio">No hay más exámenes que coincidan</div>';
    $id('o-lista').style.display = 'block';
}
const cerrar = () => { $id('o-lista').style.display = 'none'; };
$id('o-buscar').addEventListener('focus', abrir);
$id('o-buscar').addEventListener('input', abrir);
$id('o-buscar').addEventListener('blur', () => setTimeout(cerrar, 150));
$id('o-buscar').addEventListener('keydown', e => {
    if (e.key === 'Enter') {   // Enter elige el único resultado y no envía el formulario
        e.preventDefault();
        const ops = $id('o-lista').querySelectorAll('.ex-op');
        if (ops.length === 1) elegir(ops[0].dataset.id);
    } else if (e.key === 'Escape') { e.stopPropagation(); cerrar(); }
});
$id('o-lista').addEventListener('mousedown', e => {
    const op = e.target.closest('.ex-op');
    if (op) { e.preventDefault(); elegir(op.dataset.id); }
});
function elegir(id) {
    const x = EXAMENES.find(e => e.id === Number(id));
    if (x) elegidos.push(x);
    $id('o-buscar').value = '';
    cerrar();
    pintar();
}
$id('o-chips').addEventListener('click', e => {
    const b = e.target.closest('button[data-id]');
    if (!b) return;
    elegidos = elegidos.filter(x => x.id !== Number(b.dataset.id));
    pintar();
});
$id('formOrden').addEventListener('submit', e => {
    if (!elegidos.length) { e.preventDefault(); $id('o-ayuda').innerHTML = '<span class="text-danger">Elige al menos un examen.</span>'; $id('o-buscar').focus(); }
});

function anular(id, texto) {
    if (!confirm('¿Anular la orden «' + texto + '»?')) return;
    const f = $id('formAnular');
    f.id.value = id;
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
    $id('textoVacio').textContent = q ? 'Ninguna orden coincide con la búsqueda.' : 'Aún no has enviado órdenes de laboratorio.';
});
</script>
</body>
</html>
