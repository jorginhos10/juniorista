<?php
$iconos = ['Perro' => '🐶', 'Gato' => '🐱', 'Ave' => '🐦', 'Conejo' => '🐰', 'Roedor' => '🐹', 'Reptil' => '🦎', 'Otro' => '🐾'];

$edad = function (?string $nac): string {
    if (!$nac) return '';
    $diff = (new DateTime($nac))->diff(new DateTime('today'));
    if ($diff->y > 0) return $diff->y . ($diff->y === 1 ? ' año' : ' años');
    if ($diff->m > 0) return $diff->m . ($diff->m === 1 ? ' mes' : ' meses');
    return $diff->d . ($diff->d === 1 ? ' día' : ' días');
};

$total     = count($mascotas);
$perros    = count(array_filter($mascotas, fn($m) => $m['especie'] === 'Perro'));
$gatos     = count(array_filter($mascotas, fn($m) => $m['especie'] === 'Gato'));
$sinDueno  = count(array_filter($mascotas, fn($m) => empty($m['cliente_nombre'])));

$nombreFiltroCliente = '';
foreach ($clientes as $c) {
    if ((int)$c['id'] === $filtroCliente) { $nombreFiltroCliente = $c['nombre_completo']; break; }
}
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .mascota-ico {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.3rem; background: #f1f5f9;
        }
        .filtros { display: flex; gap: .5rem; flex-wrap: wrap; }
        .filtros .form-select, .filtros .form-control { font-size: .82rem; }

        .ac-wrap { position: relative; }
        .ac-list {
            position: absolute; z-index: 1080; top: 100%; left: 0; right: 0; margin-top: 4px;
            max-height: 240px; overflow-y: auto; background: #fff;
            border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
            display: none;
        }
        .ac-item { padding: .45rem .75rem; cursor: pointer; font-size: .875rem; }
        .ac-item:hover, .ac-item.activo { background: #f1f5ff; }
        .ac-item small { color: #64748b; }
        .ac-empty { padding: .6rem .75rem; color: var(--text-muted); font-size: .85rem; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Mascotas</h5>
                <p class="topbar-date mb-0">Pacientes registrados y sus dueños</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-primary-custom btn-sm" onclick="nuevaMascota()">
                    <i class="bi bi-plus-lg me-1"></i> Nueva mascota
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
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft"><i class="bi bi-heart-fill text-primary"></i></div>
                        <div>
                            <div class="stat-value"><?= $total ?></div>
                            <div class="stat-label">Mascotas registradas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft" style="font-size:1.3rem">🐶</div>
                        <div>
                            <div class="stat-value"><?= $perros ?></div>
                            <div class="stat-label">Perros</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft" style="font-size:1.3rem">🐱</div>
                        <div>
                            <div class="stat-value"><?= $gatos ?></div>
                            <div class="stat-label">Gatos</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary-soft"><i class="bi bi-person-x text-secondary"></i></div>
                        <div>
                            <div class="stat-value"><?= $sinDueno ?></div>
                            <div class="stat-label">Sin dueño asignado</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold">
                        Listado de mascotas
                        <?php if ($nombreFiltroCliente): ?>
                        <span class="badge bg-primary-soft text-primary ms-1">
                            Dueño: <?= htmlspecialchars($nombreFiltroCliente) ?>
                            <a href="<?= BASE_URL ?>/mascotas" class="text-primary ms-1" title="Quitar filtro"><i class="bi bi-x-lg"></i></a>
                        </span>
                        <?php endif; ?>
                    </h6>
                    <div class="filtros">
                        <select class="form-select form-select-sm" id="filtroEspecie" style="width:140px">
                            <option value="">Todas las especies</option>
                            <?php foreach ($especies as $e): ?>
                            <option value="<?= $e ?>"><?= $e ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select form-select-sm" id="filtroDueno" style="width:140px">
                            <option value="">Con y sin dueño</option>
                            <option value="con">Con dueño</option>
                            <option value="sin">Sin dueño</option>
                        </select>
                        <input type="text" class="form-control form-control-sm" id="buscador"
                               placeholder="Buscar mascota, raza o dueño…" style="width:220px">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaMascotas">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Mascota</th>
                                    <th>Raza</th>
                                    <th>Sexo</th>
                                    <th>Edad</th>
                                    <th>Dueño</th>
                                    <th class="text-center">Citas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mascotas)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-heart fs-2 d-block mb-2 opacity-25"></i>
                                        No hay mascotas registradas.
                                        <div><a href="#" onclick="nuevaMascota(); return false;">Registrar la primera</a></div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($mascotas as $m):
                                    $oculta = $filtroCliente > 0 && (int)$m['cliente_id'] !== $filtroCliente; ?>
                                <tr data-especie="<?= htmlspecialchars($m['especie']) ?>"
                                    data-dueno="<?= $m['cliente_nombre'] ? 'con' : 'sin' ?>"
                                    <?= $oculta ? 'data-fuera-filtro="1" style="display:none"' : '' ?>>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="mascota-ico"><?= $iconos[$m['especie']] ?? '🐾' ?></span>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($m['nombre']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($m['especie']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($m['raza'] ?: '—') ?></td>
                                    <td class="small">
                                        <?php if ($m['sexo'] === 'macho'): ?><i class="bi bi-gender-male text-primary"></i> Macho
                                        <?php elseif ($m['sexo'] === 'hembra'): ?><i class="bi bi-gender-female text-danger"></i> Hembra
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>
                                    <td class="small"><?= $edad($m['fecha_nacimiento']) ?: '<span class="text-muted">—</span>' ?></td>
                                    <td>
                                        <?php if ($m['cliente_nombre']): ?>
                                        <div class="small fw-semibold"><?= htmlspecialchars($m['cliente_nombre']) ?></div>
                                        <?php if ($m['cliente_telefono']): ?>
                                        <a href="tel:<?= htmlspecialchars($m['cliente_telefono']) ?>" class="text-muted small text-decoration-none">
                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($m['cliente_telefono']) ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <span class="badge bg-secondary-soft text-secondary">Sin dueño</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $m['total_citas'] > 0 ? 'bg-primary-soft text-primary' : 'bg-secondary-soft text-secondary' ?>">
                                            <?= (int)$m['total_citas'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="<?= BASE_URL ?>/historial?mascota=<?= $m['id'] ?>" class="btn btn-sm btn-outline-success btn-accion" title="Historial médico">
                                                <i class="bi bi-clipboard2-pulse-fill"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick='editarMascota(<?= json_encode($m, $jsonFlags) ?>)'>
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar"
                                                    onclick='eliminarMascota(<?= (int)$m['id'] ?>, <?= json_encode($m['nombre'], $jsonFlags) ?>)'>
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($mascotas): ?>
                <div class="card-footer bg-white text-muted small border-0">
                    Mostrando <span id="contadorVisible"></span> de <?= $total ?> mascotas
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL MASCOTA ─────────────────────────────────── -->
<div class="modal fade" id="modalMascota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/mascotas/guardar" id="formMascota" autocomplete="off">
                <input type="hidden" name="id" id="m-id" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-heart-fill text-danger me-2"></i><span id="mascotaTitulo">Nueva mascota</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="m-nombre" maxlength="100" required placeholder="Ej: Max">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Especie <span class="text-danger">*</span></label>
                            <select class="form-select" name="especie" id="m-especie" required>
                                <?php foreach ($especies as $e): ?>
                                <option value="<?= $e ?>"><?= $e ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Sexo</label>
                            <select class="form-select" name="sexo" id="m-sexo">
                                <option value="">—</option>
                                <option value="macho">Macho</option>
                                <option value="hembra">Hembra</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Raza</label>
                            <input type="text" class="form-control" name="raza" id="m-raza" maxlength="100" placeholder="Ej: Labrador, mestizo…">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" id="m-fecha_nacimiento" max="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Dueño <span class="text-muted fw-normal small">(opcional)</span></label>
                            <div class="ac-wrap">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="m-cliente-txt" placeholder="Buscar cliente por nombre, DNI o teléfono…">
                                    <button type="button" class="btn btn-outline-secondary" id="m-cliente-quitar" title="Quitar dueño" style="display:none">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="cliente_id" id="m-cliente" value="0">
                                <div class="ac-list" id="m-cliente-list"></div>
                            </div>
                            <div class="form-text">
                                Se elige de los clientes registrados. ¿No está? <a href="<?= BASE_URL ?>/clientes">Crear cliente</a>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas</label>
                            <textarea class="form-control" name="notas" id="m-notas" rows="2" placeholder="Alergias, carácter, observaciones…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> <span id="mascotaBtnTexto">Registrar mascota</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/mascotas/eliminar" id="formEliminar" class="d-none">
    <input type="hidden" name="id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CLIENTES = <?= json_encode($clientes, $jsonFlags) ?>;
const esc = s => String(s ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
const $ = id => document.getElementById(id);
const modal = () => bootstrap.Modal.getOrCreateInstance($('modalMascota'));
const campos = ['nombre', 'especie', 'sexo', 'raza', 'fecha_nacimiento', 'notas'];

// ── Dueño (desde Clientes) ────────────────────────────
function seleccionarDueno(cli) {
    $('m-cliente').value     = cli ? cli.id : 0;
    $('m-cliente-txt').value = cli ? cli.nombre_completo : '';
    $('m-cliente-quitar').style.display = cli ? '' : 'none';
}

(function () {
    const input = $('m-cliente-txt'), list = $('m-cliente-list');
    let activo = -1;

    function render() {
        const q = input.value.trim().toLowerCase();
        const res = CLIENTES.filter(c =>
            !q || c.nombre_completo.toLowerCase().includes(q) ||
            (c.dni || '').toLowerCase().includes(q) || (c.telefono || '').includes(q)
        ).slice(0, 30);
        activo = -1;
        list.innerHTML = res.length
            ? res.map(c => `<div class="ac-item" data-id="${c.id}">${esc(c.nombre_completo)}
                   <small class="ms-1">${esc([c.dni, c.telefono].filter(Boolean).join(' · '))}</small></div>`).join('')
            : '<div class="ac-empty">Sin resultados</div>';
        list.style.display = 'block';
    }

    input.addEventListener('focus', render);
    input.addEventListener('input', () => { $('m-cliente').value = 0; $('m-cliente-quitar').style.display = 'none'; render(); });
    input.addEventListener('blur', () => setTimeout(() => {
        list.style.display = 'none';
        // Si escribió algo pero no eligió de la lista, se limpia: el dueño debe ser un cliente existente
        if ($('m-cliente').value === '0') input.value = '';
    }, 150));
    input.addEventListener('keydown', e => {
        const items = [...list.querySelectorAll('.ac-item')];
        if (!items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            activo = (activo + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            items.forEach((it, i) => it.classList.toggle('activo', i === activo));
            items[activo].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && list.style.display === 'block') {
            e.preventDefault();
            const it = items[activo] || items[0];
            seleccionarDueno(CLIENTES.find(c => String(c.id) === it.dataset.id));
            list.style.display = 'none';
        }
    });
    list.addEventListener('mousedown', e => {
        const it = e.target.closest('.ac-item');
        if (!it) return;
        e.preventDefault();
        seleccionarDueno(CLIENTES.find(c => String(c.id) === it.dataset.id));
        list.style.display = 'none';
    });
    $('m-cliente-quitar').addEventListener('click', () => { seleccionarDueno(null); input.focus(); });
})();

// ── Crear / editar / eliminar ─────────────────────────
function nuevaMascota() {
    $('mascotaTitulo').textContent   = 'Nueva mascota';
    $('mascotaBtnTexto').textContent = 'Registrar mascota';
    $('m-id').value = 0;
    campos.forEach(c => $('m-' + c).value = '');
    $('m-especie').value = 'Perro';
    seleccionarDueno(CLIENTES.find(c => c.id == <?= $filtroCliente ?>) || null);
    modal().show();
    setTimeout(() => $('m-nombre').focus(), 300);
}

function editarMascota(m) {
    $('mascotaTitulo').textContent   = 'Editar mascota';
    $('mascotaBtnTexto').textContent = 'Guardar cambios';
    $('m-id').value = m.id;
    campos.forEach(c => $('m-' + c).value = m[c] ?? '');
    seleccionarDueno(m.cliente_nombre ? CLIENTES.find(c => String(c.id) === String(m.cliente_id)) : null);
    modal().show();
}

function eliminarMascota(id, nombre) {
    if (!confirm('¿Eliminar a ' + nombre + '?')) return;
    const f = $('formEliminar');
    f.id.value = id;
    f.submit();
}

// ── Filtros ───────────────────────────────────────────
function filtrar() {
    const q   = $('buscador').value.toLowerCase();
    const esp = $('filtroEspecie').value;
    const due = $('filtroDueno').value;
    let visibles = 0;
    document.querySelectorAll('#tablaMascotas tbody tr[data-especie]').forEach(tr => {
        const ok = !tr.dataset.fueraFiltro
            && (!esp || tr.dataset.especie === esp)
            && (!due || tr.dataset.dueno === due)
            && tr.textContent.toLowerCase().includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) visibles++;
    });
    const cont = $('contadorVisible');
    if (cont) cont.textContent = visibles;
}
['buscador', 'filtroEspecie', 'filtroDueno'].forEach(id => $(id).addEventListener(id === 'buscador' ? 'input' : 'change', filtrar));
filtrar();
</script>
</body>
</html>
