<?php
$money = fn($n) => '$' . number_format((float)$n, 2);
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Cirugías y tratamientos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= (int)@filemtime(ROOT . '/assets/css/style.css') ?>" rel="stylesheet">
    <style>
        .lab-card { width: 100%; }
        .monto { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
        .sigla { font-family: ui-monospace, Consolas, monospace; font-weight: 700; color: #7c3aed; background: #f5f3ff; padding: .1rem .5rem; border-radius: 6px; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Cirugías y tratamientos</h5>
                <p class="topbar-date mb-0">
                    <a href="<?= BASE_URL ?>/configuracion" class="text-decoration-none text-muted">Configuración</a>
                    <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> Catálogo
                </p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
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

            <div class="card border-0 shadow-sm lab-card">
                <div class="card-body p-4">
                    <ul class="nav nav-pills mb-3 gap-1">
                        <?php foreach ($tipos as $k => $t): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $k === $tipo ? 'active' : 'bg-light text-body' ?>" href="<?= BASE_URL ?>/configuracion/catalogo?tipo=<?= $k ?>">
                                <i class="bi <?= $t[2] ?> me-1"></i><?= $t[0] ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
                        <h6 class="fw-bold mb-0"><?= $tipos[$tipo][0] ?></h6>
                        <?php if ($esAdmin): ?>
                        <button type="button" class="btn btn-primary-custom btn-sm" onclick="nuevoItem()">
                            <i class="bi bi-plus-lg me-1"></i>Agregar <?= mb_strtolower($tipos[$tipo][1]) ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-3">
                        Sucursal: <strong><?= htmlspecialchars($sucursal['nombre'] ?? '') ?></strong>.
                        <?php if ($tipo === 'cirugia'): ?>
                        Estas cirugías aparecen en la lista de <strong>Órdenes de cirugía</strong> y como sugerencia al agregar un servicio en una <strong>cotización</strong>.
                        <?php else: ?>
                        Estos tratamientos aparecen como sugerencia al registrar un <strong>Tratamiento</strong> y al agregar un servicio en una <strong>cotización</strong>.
                        <?php endif; ?>
                        El <strong>precio</strong> es solo una sugerencia: se puede cambiar al usarlo. Siempre se puede escribir algo que no esté en la lista.
                    </p>

                    <div class="input-group input-group-sm mb-3" style="max-width:340px">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="buscarItem" placeholder="Buscar…" autocomplete="off">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaItems">
                            <thead class="table-light">
                                <tr>
                                    <th><?= $tipos[$tipo][1] ?></th>
                                    <th class="text-end" style="width:140px">Precio sugerido</th>
                                    <?php if ($esAdmin): ?><th class="text-center" style="width:100px">Acciones</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $x): ?>
                                <tr data-q="<?= htmlspecialchars(mb_strtolower($x['nombre'])) ?>">
                                    <td><?= htmlspecialchars($x['nombre']) ?></td>
                                    <td class="text-end monto"><?= (float)$x['precio'] > 0 ? $money($x['precio']) : '<span class="text-muted fw-normal">—</span>' ?></td>
                                    <?php if ($esAdmin): ?>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick='editarItem(<?= json_encode($x, $jsonFlags) ?>)'><i class="bi bi-pencil-fill"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-accion" title="Quitar"
                                                    onclick='quitarItem(<?= (int)$x['id'] ?>, <?= json_encode($x['nombre'], $jsonFlags) ?>)'><i class="bi bi-trash-fill"></i></button>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                                <tr id="sinItems" class="<?= $items ? 'd-none' : '' ?>">
                                    <td colspan="<?= $esAdmin ? 3 : 2 ?>" class="text-center text-muted py-5">
                                        <i class="bi <?= $tipos[$tipo][2] ?> fs-2 d-block mb-2 opacity-25"></i>
                                        <span id="sinItemsTxt"><?= $items ? 'Sin resultados' : 'Aún no hay ' . mb_strtolower($tipos[$tipo][0]) . ' en el catálogo' . ($esAdmin ? '. Agrega el primero.' : '.') ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= BASE_URL ?>/configuracion" class="btn btn-light">Volver</a>
                        <?php if (!$esAdmin): ?>
                        <span class="small text-muted align-self-center"><i class="bi bi-lock me-1"></i>Solo un administrador puede cambiar el catálogo.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php if ($esAdmin): ?>
<div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/configuracion/catalogo/guardar" autocomplete="off">
                <input type="hidden" name="id" id="i-id" value="0">
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                <input type="hidden" name="alcance" id="i-alcance" value="sucursal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi <?= $tipos[$tipo][2] ?> me-2" style="color:<?= $tipos[$tipo][3] ?>"></i><span id="i-titulo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="i-nombre">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="i-nombre" maxlength="150" required>
                    </div>
                    <div>
                        <label class="form-label fw-semibold" for="i-precio">Precio sugerido</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="precio" id="i-precio" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-text">Opcional. Se propone al elegirlo, pero se puede cambiar.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cambio de precio: ¿solo esta sucursal o todas? -->
<div class="modal fade" id="modalAlcance" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <i class="bi bi-currency-dollar fs-1 text-primary"></i>
                <h6 class="fw-bold mt-2">Cambio de precio</h6>
                <p class="small text-muted mb-3">¿Aplicar el nuevo precio solo en <strong><?= htmlspecialchars($sucursal['nombre'] ?? 'esta sucursal') ?></strong> o en todas las sucursales?</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary-custom" onclick="confirmarAlcance('sucursal')">Solo esta sucursal</button>
                    <button type="button" class="btn btn-outline-primary" onclick="confirmarAlcance('todas')">Todas las sucursales</button>
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/configuracion/catalogo/eliminar" id="formQuitar" class="d-none">
    <input type="hidden" name="id">
    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
</form>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $id = id => document.getElementById(id);
const ETQ = <?= json_encode(mb_strtolower($tipos[$tipo][1])) ?>;
$id('buscarItem').addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase();
    let n = 0;
    document.querySelectorAll('#tablaItems tbody tr[data-q]').forEach(tr => {
        const ok = !q || tr.dataset.q.includes(q);
        tr.classList.toggle('d-none', !ok);
        if (ok) n++;
    });
    $id('sinItems').classList.toggle('d-none', n > 0);
    $id('sinItemsTxt').textContent = 'Sin resultados';
});
<?php if ($esAdmin): ?>
const modalItem = () => bootstrap.Modal.getOrCreateInstance($id('modalItem'));
const HAY_OTRAS = <?= count($veterinarias) > 1 ? 'true' : 'false' ?>;
let precioOriginal = 0, alcanceElegido = false;
const formItem = $id('i-id').form;
formItem.addEventListener('submit', e => {
    if (!alcanceElegido) $id('i-alcance').value = 'sucursal';
    const editando = Number($id('i-id').value) > 0;
    const cambio = Math.abs((parseFloat($id('i-precio').value) || 0) - precioOriginal) > 0.001;
    if (HAY_OTRAS && editando && cambio && !alcanceElegido) {
        e.preventDefault();
        bootstrap.Modal.getOrCreateInstance($id('modalAlcance')).show();
    }
});
function confirmarAlcance(a) {
    $id('i-alcance').value = a;
    alcanceElegido = true;
    formItem.requestSubmit();
}
function nuevoItem() {
    precioOriginal = 0; alcanceElegido = false;
    $id('i-id').value = 0; $id('i-nombre').value = ''; $id('i-precio').value = '';
    $id('i-titulo').textContent = 'Agregar ' + ETQ;
    modalItem().show();
    setTimeout(() => $id('i-nombre').focus(), 300);
}
function editarItem(x) {
    precioOriginal = Number(x.precio) || 0; alcanceElegido = false;
    $id('i-id').value = x.id; $id('i-nombre').value = x.nombre;
    $id('i-precio').value = Number(x.precio) > 0 ? x.precio : '';
    $id('i-titulo').textContent = 'Editar ' + ETQ;
    modalItem().show();
}
function quitarItem(id, nombre) {
    if (!confirm('¿Quitar «' + nombre + '» del catálogo? Lo ya registrado conserva su texto.')) return;
    const f = $id('formQuitar');
    f.id.value = id;
    f.submit();
}
<?php endif; ?>
</script>
</body>
</html>
