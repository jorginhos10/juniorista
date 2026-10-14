<?php
$totalProductos = array_sum(array_column($categorias, 'total_productos'));
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .cat-card {
            display: flex; align-items: center; gap: .9rem;
            background: #fff; border: 1px solid #e8eaf0; border-radius: .875rem; padding: .9rem 1rem;
            transition: box-shadow .15s, border-color .15s;
        }
        .cat-card:hover { border-color: #c7d2fe; box-shadow: 0 6px 18px rgba(79, 70, 229, .08); }
        .cat-ico {
            width: 44px; height: 44px; border-radius: .75rem; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; color: var(--primary); background: #ede9fe;
        }
        .cat-nombre { font-weight: 700; line-height: 1.25; word-break: break-word; }
        .cat-cuenta { font-size: .78rem; color: #64748b; }
        .cat-nueva { max-width: 560px; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Categorías</h5>
                <p class="topbar-date mb-0">
                    <a href="<?= BASE_URL ?>/almacen" class="text-decoration-none text-muted">Almacén</a>
                    <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> Categorías de productos
                </p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <a href="<?= BASE_URL ?>/almacen" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Almacén
                </a>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= htmlspecialchars(strtoupper(mb_substr($usuario['nombre'], 0, 1))) ?></div>
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

            <!-- Agregar -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-semibold mb-1"><i class="bi bi-plus-circle text-primary me-1"></i>Agregar categoría</h6>
                    <p class="text-muted small mb-3">Las categorías que agregues aquí aparecen en la lista «Categoría» al crear o editar un producto.</p>
                    <form method="POST" action="<?= BASE_URL ?>/almacen/categorias/guardar" class="cat-nueva d-flex gap-2" autocomplete="off">
                        <input type="hidden" name="id" value="0">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                            <input type="text" class="form-control text-uppercase" name="nombre" maxlength="80" required
                                   placeholder="Ej: Medicamentos, Alimentos, Accesorios…" autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary-custom text-nowrap" style="padding:.45rem 1rem">
                            <i class="bi bi-plus-lg me-1"></i>Agregar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Listado -->
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h6 class="fw-semibold mb-0">
                    <?= count($categorias) ?> categoría<?= count($categorias) === 1 ? '' : 's' ?>
                    <span class="text-muted fw-normal small">· <?= $totalProductos ?> productos clasificados</span>
                </h6>
                <?php if (count($categorias) > 6): ?>
                <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar categoría…" style="width:220px">
                <?php endif; ?>
            </div>

            <?php if (empty($categorias)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-tags fs-1 d-block mb-2 opacity-25"></i>
                    Aún no hay categorías. Agrega la primera con el formulario de arriba.
                </div>
            </div>
            <?php else: ?>
            <div class="row g-3" id="listaCategorias">
                <?php foreach ($categorias as $c): $n = (int)$c['total_productos']; ?>
                <div class="col-md-6 col-xl-4 js-cat">
                    <div class="cat-card">
                        <div class="cat-ico"><i class="bi bi-tag-fill"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="cat-nombre"><?= htmlspecialchars($c['nombre']) ?></div>
                            <div class="cat-cuenta"><?= $n ?> producto<?= $n === 1 ? '' : 's' ?></div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Renombrar"
                                    onclick='renombrar(<?= (int)$c['id'] ?>, <?= json_encode($c['nombre'], $jsonFlags) ?>, <?= $n ?>)'>
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <span title="<?= $n > 0 ? 'Tiene productos: cámbialos de categoría antes de eliminarla' : 'Eliminar' ?>">
                                <button class="btn btn-sm btn-outline-danger btn-accion" <?= $n > 0 ? 'disabled' : '' ?>
                                        onclick='eliminar(<?= (int)$c['id'] ?>, <?= json_encode($c['nombre'], $jsonFlags) ?>)'>
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- ── MODAL RENOMBRAR ───────────────────────────────── -->
<div class="modal fade" id="modalRenombrar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/almacen/categorias/guardar" autocomplete="off">
                <input type="hidden" name="id" id="ren-id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Renombrar categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="ren-nombre">Nombre</label>
                    <input type="text" class="form-control text-uppercase" name="nombre" id="ren-nombre" maxlength="80" required>
                    <div class="form-text" id="ren-ayuda"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/almacen/categorias/eliminar" id="formEliminar" class="d-none">
    <input type="hidden" name="id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function renombrar(id, nombre, productos) {
    document.getElementById('ren-id').value = id;
    document.getElementById('ren-nombre').value = nombre;
    document.getElementById('ren-ayuda').textContent = productos > 0
        ? 'Sus ' + productos + ' producto(s) pasarán a mostrar el nuevo nombre.' : '';
    const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRenombrar'));
    m.show();
    setTimeout(() => document.getElementById('ren-nombre').select(), 300);
}

function eliminar(id, nombre) {
    if (!confirm('¿Eliminar la categoría «' + nombre + '»?')) return;
    const f = document.getElementById('formEliminar');
    f.id.value = id;
    f.submit();
}

document.getElementById('buscador')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#listaCategorias .js-cat').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
