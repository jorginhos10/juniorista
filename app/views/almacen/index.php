<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Almacén</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Almacén</h5>
                <p class="topbar-date mb-0">Gestión de productos e inventario</p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarProducto">
                    <i class="bi bi-plus-lg me-1"></i> Agregar producto
                </button>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md">
                            <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                        </div>
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

            <!-- Flash messages -->
            <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-box-seam-fill text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['total_productos'] ?? 0 ?></div>
                            <div class="stat-label">Productos</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-layers-fill text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= number_format($totales['total_stock'] ?? 0) ?></div>
                            <div class="stat-label">Unidades en stock</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['stock_bajo'] ?? 0 ?></div>
                            <div class="stat-label">Stock bajo</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft">
                            <i class="bi bi-currency-dollar text-info"></i>
                        </div>
                        <div>
                            <div class="stat-value">$<?= number_format($totales['valor_inventario'] ?? 0, 2) ?></div>
                            <div class="stat-label">Valor del inventario</div>
                            <div class="text-muted" style="font-size:.72rem;">Unidades en stock × precio de venta</div>
                            <?php
                                // Reparto del valor del inventario: 70% capital, 30% ganancia
                                $valorInv    = (float)($totales['valor_inventario'] ?? 0);
                                $gananciaInv = round($valorInv * 0.30, 2);
                                $capitalInv  = $valorInv - $gananciaInv;
                            ?>
                            <div class="d-flex flex-wrap gap-3 mt-2 pt-2 border-top" style="font-size:.78rem;">
                                <div>
                                    <div class="text-muted">Capital <span class="fw-semibold">70%</span></div>
                                    <div class="fw-bold">$<?= number_format($capitalInv, 2) ?></div>
                                </div>
                                <div>
                                    <div class="text-muted">Ganancia <span class="fw-semibold">30%</span></div>
                                    <div class="fw-bold text-success">$<?= number_format($gananciaInv, 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de productos -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2">
                    <h6 class="card-title mb-0 fw-semibold">Productos en almacén</h6>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="<?= BASE_URL ?>/almacen/categorias" class="btn btn-sm btn-outline-primary text-nowrap" title="Administrar las categorías de productos">
                            <i class="bi bi-tags-fill me-1"></i>Categorías
                        </a>
                        <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar producto..." style="width:200px;">
                        <a href="<?= BASE_URL ?>/almacen/pdf" class="btn btn-sm btn-outline-danger text-nowrap" title="Descargar en PDF"
                           onclick="this.href = '<?= BASE_URL ?>/almacen/pdf?q=' + encodeURIComponent(document.getElementById('buscador').value.trim())">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                        </a>
                        <a href="<?= BASE_URL ?>/almacen/excel" class="btn btn-sm btn-outline-success text-nowrap" title="Descargar en Excel"
                           onclick="this.href = '<?= BASE_URL ?>/almacen/excel?q=' + encodeURIComponent(document.getElementById('buscador').value.trim())">
                            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaProductos">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Unidad</th>
                                    <th class="text-end">P. Compra</th>
                                    <th class="text-end">P. Venta</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-box-seam fs-2 d-block mb-2 opacity-25"></i>
                                        No hay productos registrados.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($productos as $i => $p): ?>
                                <?php $bloqueado = !(bool)$p['activo']; ?>
                                <tr class="<?= $bloqueado ? 'fila-bloqueada' : '' ?>">
                                    <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($p['foto'])): ?>
                                        <button type="button" class="prod-foto <?= $bloqueado ? 'opacity-50' : '' ?>" title="Ver foto"
                                                onclick="verFoto(<?= htmlspecialchars(json_encode(BASE_URL . '/' . $p['foto'])) ?>, <?= htmlspecialchars(json_encode($p['nombre'])) ?>)">
                                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['foto']) ?>" alt="" loading="lazy">
                                        </button>
                                        <?php else: ?>
                                        <span class="prod-foto prod-foto-vacia"><i class="bi bi-box-seam"></i></span>
                                        <?php endif; ?>
                                        <div>
                                        <div class="fw-semibold <?= $bloqueado ? 'text-muted' : '' ?>">
                                            <?= htmlspecialchars($p['nombre']) ?>
                                            <?php if ($bloqueado): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size:.65rem;">Bloqueado</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($p['descripcion']): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($p['descripcion']) ?></div>
                                        <?php endif; ?>
                                        </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge categoria-badge <?= $bloqueado ? 'opacity-50' : '' ?>">
                                            <?= htmlspecialchars($p['categoria'] ?: 'Sin categoría') ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($p['unidad']) ?></td>
                                    <td class="text-end <?= $bloqueado ? 'text-muted' : '' ?>">$<?= number_format($p['precio_compra'], 2) ?></td>
                                    <td class="text-end <?= $bloqueado ? 'text-muted' : 'fw-semibold' ?>">$<?= number_format($p['precio_venta'], 2) ?></td>
                                    <td class="text-center">
                                        <?php if (!$bloqueado): ?>
                                            <?php $stockClase = $p['stock'] <= $p['stock_minimo'] ? 'text-danger fw-bold' : 'text-success fw-semibold'; ?>
                                            <span class="<?= $stockClase ?>"><?= $p['stock'] ?></span>
                                            <?php if ($p['stock'] <= $p['stock_minimo']): ?>
                                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Stock bajo"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <?php if (!$bloqueado): ?>
                                            <button class="btn btn-sm btn-outline-secondary btn-accion" title="Editar"
                                                onclick="editarProducto(<?= htmlspecialchars(json_encode($p)) ?>)">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Transferir stock"
                                                onclick="abrirTransferir(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>', <?= $p['stock'] ?>)">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </button>
                                            <?php endif; ?>
                                            <a href="<?= BASE_URL ?>/almacen/toggle?id=<?= $p['id'] ?>"
                                               class="btn btn-sm btn-accion <?= $bloqueado ? 'btn-outline-success' : 'btn-outline-warning' ?>"
                                               title="<?= $bloqueado ? 'Desbloquear' : 'Bloquear' ?>"
                                               onclick="return confirm('<?= $bloqueado ? '¿Desbloquear' : '¿Bloquear' ?> <?= htmlspecialchars($p['nombre']) ?>?')">
                                                <i class="bi <?= $bloqueado ? 'bi-unlock-fill' : 'bi-lock-fill' ?>"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL FOTO AMPLIADA ──────────────────────────── -->
<div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold" id="fotoGrandeTitulo"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="fotoGrandeImg" alt="" style="max-width:100%; max-height:70vh; border-radius:.75rem;">
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL AGREGAR PRODUCTO ───────────────────────── -->
<div class="modal fade" id="modalAgregarProducto" tabindex="-1" aria-labelledby="modalAgregarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/almacen/agregar" id="formProducto" enctype="multipart/form-data">
                <input type="hidden" name="veterinaria_id" value="<?= $veterinaria_id ?>">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalAgregarLabel">
                        <i class="bi bi-plus-circle-fill text-primary me-2"></i>
                        <span id="modalTitulo">Agregar producto</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre del producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="inp-nombre" placeholder="Ej: Vacuna antirrábica" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex justify-content-between" for="inp-categoria">
                                Categoría
                                <a href="<?= BASE_URL ?>/almacen/categorias" class="small fw-normal text-decoration-none">Administrar</a>
                            </label>
                            <select class="form-select" name="categoria" id="inp-categoria">
                                <option value="">— Sin categoría —</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código</label>
                            <input type="text" class="form-control" name="codigo" id="inp-codigo"
                                placeholder="Ej: VAC-001, MED-042…">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Unidad de medida</label>
                            <select class="form-select" name="unidad" id="inp-unidad">
                                <option value="unidad">Unidad</option>
                                <option value="comprimido">Comprimido</option>
                                <option value="dosis">Dosis</option>
                                <option value="frasco">Frasco</option>
                                <option value="caja">Caja</option>
                                <option value="kg">Kilogramo</option>
                                <option value="litro">Litro</option>
                                <option value="ml">Mililitro</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Precio de compra ($)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="precio_compra" id="inp-precio_compra"
                                    step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Precio de venta ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="precio_venta" id="inp-precio_venta"
                                    step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Stock mínimo (alerta)</label>
                            <input type="number" class="form-control" name="stock_minimo" id="inp-stock_minimo" min="0" value="5">
                            <div class="form-text">Se alertará cuando el stock llegue a este valor.</div>
                        </div>
                        <input type="hidden" name="stock" id="inp-stock" value="0">

                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="inp-descripcion" rows="2"
                                placeholder="Descripción opcional del producto…"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="inp-foto">Foto del producto</label>
                            <div class="foto-campo">
                                <div class="foto-preview" id="foto-preview"><i class="bi bi-image"></i></div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control form-control-sm" name="foto" id="inp-foto"
                                           accept="image/png,image/jpeg,image/webp">
                                    <div class="form-text mb-0">JPG, PNG o WebP, máximo 2 MB.</div>
                                    <div class="form-check mt-1" id="foto-quitar-box" style="display:none">
                                        <input class="form-check-input" type="checkbox" name="quitar_foto" value="1" id="foto-quitar">
                                        <label class="form-check-label small text-danger" for="foto-quitar">Quitar la foto actual</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="rel-input">Relacionar con</label>
                            <div class="rel-caja" id="rel-caja">
                                <div id="rel-tags" class="rel-tags"></div>
                                <div class="rel-buscador">
                                    <input type="text" class="form-control" id="rel-input" autocomplete="off"
                                           placeholder="Escribe el nombre o código de otro producto…">
                                    <div class="rel-lista" id="rel-lista"></div>
                                </div>
                            </div>
                            <div class="form-text">Productos que hacen lo mismo que este. Al buscar uno en ventas, los relacionados aparecen aparte como alternativa.</div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> Guardar producto
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<style>
    .prod-foto { width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0; padding: 0; border: 1px solid #e2e8f0; background: #fff;
                 overflow: hidden; display: inline-flex; align-items: center; justify-content: center; cursor: zoom-in; }
    .prod-foto img { width: 100%; height: 100%; object-fit: cover; }
    .prod-foto-vacia { cursor: default; background: #f1f5f9; color: #cbd5e1; font-size: 1.1rem; }
    .foto-campo { display: flex; align-items: center; gap: 1rem; border: 1px dashed #cbd5e1; border-radius: .75rem; padding: .75rem 1rem; }
    .foto-preview { width: 84px; height: 84px; border-radius: .75rem; flex-shrink: 0; overflow: hidden; background: #f1f5f9;
                    display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #94a3b8; }
    .foto-preview img { width: 100%; height: 100%; object-fit: cover; }
    .rel-tags { display: flex; flex-wrap: wrap; gap: .35rem; }
    .rel-tags:not(:empty) { margin-bottom: .45rem; }
    .rel-tag { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .3rem .2rem .6rem; border-radius: 999px;
               background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; font-size: .8rem; font-weight: 600; }
    .rel-tag-x { border: 0; background: transparent; color: #6366f1; font-size: 1rem; line-height: 1; width: 20px; height: 20px;
                 border-radius: 50%; padding: 0; cursor: pointer; }
    .rel-tag-x:hover { background: #c7d2fe; color: #312e81; }
    .rel-buscador { position: relative; }
    .rel-lista { display: none; position: absolute; left: 0; right: 0; top: calc(100% + 2px); z-index: 1060; max-height: 220px; overflow-y: auto;
                 background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .12); }
    .rel-opcion { padding: .45rem .75rem; font-size: .85rem; cursor: pointer; display: flex; justify-content: space-between; gap: .75rem; }
    .rel-opcion:hover, .rel-opcion.activa { background: #eef2ff; }
    .rel-codigo { color: #94a3b8; font-size: .75rem; white-space: nowrap; }
    .rel-vacio { padding: .5rem .75rem; font-size: .82rem; color: #94a3b8; }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
// Buscador en tabla
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaProductos tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ── FOTO DEL PRODUCTO: vista previa de la guardada o de la recién elegida ──
const FOTO_BASE = '<?= BASE_URL ?>/';

function mostrarFoto(url) {
    const prev = document.getElementById('foto-preview');
    prev.innerHTML = url ? '<img alt="Foto del producto">' : '<i class="bi bi-image"></i>';
    if (url) prev.querySelector('img').src = url;
}

function prepararFoto(guardada) {
    const input = document.getElementById('inp-foto');
    input.value = '';
    input.dataset.guardada = guardada ? FOTO_BASE + guardada : '';
    document.getElementById('foto-quitar').checked = false;
    document.getElementById('foto-quitar-box').style.display = guardada ? '' : 'none';
    mostrarFoto(input.dataset.guardada);
}

document.getElementById('inp-foto').addEventListener('change', function () {
    const archivo = this.files[0];
    if (!archivo) { mostrarFoto(this.dataset.guardada); return; }
    if (!['image/png', 'image/jpeg', 'image/webp'].includes(archivo.type)) {
        alert('La foto debe ser una imagen JPG, PNG o WebP.');
        this.value = ''; mostrarFoto(this.dataset.guardada); return;
    }
    if (archivo.size > 2 * 1024 * 1024) {
        alert('La imagen supera los 2 MB.');
        this.value = ''; mostrarFoto(this.dataset.guardada); return;
    }
    document.getElementById('foto-quitar').checked = false;
    mostrarFoto(URL.createObjectURL(archivo));
});
document.getElementById('foto-quitar').addEventListener('change', function () {
    mostrarFoto(this.checked ? '' : document.getElementById('inp-foto').dataset.guardada);
});

// Ampliar la foto desde la tabla
function verFoto(url, nombre) {
    document.getElementById('fotoGrandeImg').src = url;
    document.getElementById('fotoGrandeTitulo').textContent = nombre;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFoto')).show();
}

// Editar producto: rellena el modal con los datos existentes
function editarProducto(p) {
    document.getElementById('modalTitulo').textContent = 'Editar producto';
    document.getElementById('formProducto').action = '<?= BASE_URL ?>/almacen/agregar?id=' + p.id;
    ['nombre','codigo','descripcion','categoria','unidad','precio_compra','precio_venta','stock_minimo','stock'].forEach(campo => {
        const el = document.getElementById('inp-' + campo);
        if (el) el.value = p[campo] ?? '';
    });
    prepararFoto(p.foto || '');
    relEditando = Number(p.id);
    relSeleccion = (REL_MAPA[p.id] || []).map(Number).filter(id => REL_PRODUCTOS.some(x => x.id === id));
    relPintarTags();
    new bootstrap.Modal(document.getElementById('modalAgregarProducto')).show();
}

// "Agregar producto" abre el formulario limpio (antes conservaba los datos del último producto editado)
document.querySelector('[data-bs-target="#modalAgregarProducto"]')?.addEventListener('click', () => {
    document.getElementById('formProducto').reset();
    document.getElementById('formProducto').action = '<?= BASE_URL ?>/almacen/agregar';
    document.getElementById('modalTitulo').textContent = 'Agregar producto';
    prepararFoto('');
    relEditando = 0;
    relSeleccion = [];
    relPintarTags();
});

// ── RELACIONAR CON: buscador predictivo que agrega productos como etiquetas ──
const REL_PRODUCTOS = <?= json_encode(array_values(array_map(
    fn($p) => ['id' => (int)$p['id'], 'nombre' => $p['nombre'], 'codigo' => (string)($p['codigo'] ?? '')],
    array_filter($productos, fn($p) => (int)$p['activo'] === 1)
)), JSON_UNESCAPED_UNICODE) ?>;
const REL_MAPA = <?= json_encode((object)($relacionados ?? [])) ?>;
let relEditando = 0;      // id del producto que se edita (no puede relacionarse consigo mismo)
let relSeleccion = [];    // ids elegidos
let relActivo = -1;       // sugerencia resaltada con el teclado

const relEsc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

function relPintarTags() {
    document.getElementById('rel-tags').innerHTML = relSeleccion.map(id => {
        const prod = REL_PRODUCTOS.find(x => x.id === id);
        if (!prod) return '';
        return `<span class="rel-tag">
                    <input type="hidden" name="relacionados[]" value="${id}">
                    ${relEsc(prod.nombre)}
                    <button type="button" class="rel-tag-x" onclick="relQuitar(${id})" title="Quitar" aria-label="Quitar ${relEsc(prod.nombre)}">&times;</button>
                </span>`;
    }).join('');
}

function relQuitar(id) {
    relSeleccion = relSeleccion.filter(x => x !== id);
    relPintarTags();
}

function relAgregar(id) {
    if (!relSeleccion.includes(id)) relSeleccion.push(id);
    relPintarTags();
    const inp = document.getElementById('rel-input');
    inp.value = '';
    relSugerir();
    inp.focus();
}

function relSugerir() {
    const q = document.getElementById('rel-input').value.trim().toLowerCase();
    const lista = document.getElementById('rel-lista');
    relActivo = -1;
    if (!q) { lista.style.display = 'none'; lista.innerHTML = ''; return; }

    const res = REL_PRODUCTOS.filter(x =>
        x.id !== relEditando && !relSeleccion.includes(x.id) &&
        (x.nombre.toLowerCase().includes(q) || x.codigo.toLowerCase().includes(q))
    ).slice(0, 8);

    lista.innerHTML = res.length
        ? res.map(x => `<div class="rel-opcion" data-id="${x.id}">${relEsc(x.nombre)}${x.codigo ? `<span class="rel-codigo">${relEsc(x.codigo)}</span>` : ''}</div>`).join('')
        : '<div class="rel-vacio">Sin productos que coincidan</div>';
    lista.style.display = 'block';
}

document.getElementById('rel-input').addEventListener('input', relSugerir);
document.getElementById('rel-input').addEventListener('focus', relSugerir);
document.getElementById('rel-input').addEventListener('keydown', function (e) {
    const ops = [...document.querySelectorAll('#rel-lista .rel-opcion')];
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (!ops.length) return;
        relActivo = (relActivo + (e.key === 'ArrowDown' ? 1 : -1) + ops.length) % ops.length;
        ops.forEach((o, i) => o.classList.toggle('activa', i === relActivo));
        ops[relActivo].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
        e.preventDefault();   // no enviar el formulario mientras se elige un relacionado
        const elegido = ops[relActivo] || ops[0];
        if (elegido) relAgregar(Number(elegido.dataset.id));
    } else if (e.key === 'Backspace' && this.value === '' && relSeleccion.length) {
        relSeleccion.pop();
        relPintarTags();
    } else if (e.key === 'Escape') {
        document.getElementById('rel-lista').style.display = 'none';
    }
});
// mousedown (no click) para elegir antes de que el campo pierda el foco
document.getElementById('rel-lista').addEventListener('mousedown', function (e) {
    const op = e.target.closest('.rel-opcion');
    if (op) { e.preventDefault(); relAgregar(Number(op.dataset.id)); }
});
document.getElementById('rel-input').addEventListener('blur', () => {
    setTimeout(() => { document.getElementById('rel-lista').style.display = 'none'; }, 120);
});

// Auto-abrir modal si hay error de validación
<?php if (!empty($error)): ?>
new bootstrap.Modal(document.getElementById('modalAgregarProducto')).show();
<?php endif; ?>

function abrirTransferir(id, nombre, stockDisp) {
    document.getElementById('tr-producto-id').value   = id;
    document.getElementById('tr-producto-nombre').textContent = nombre;
    document.getElementById('tr-cantidad').value      = 1;
    document.getElementById('tr-cantidad').max        = stockDisp;
    document.getElementById('tr-stock-disp').textContent = 'Stock disponible: ' + stockDisp;
    document.getElementById('tr-destino').value       = '';
    new bootstrap.Modal(document.getElementById('modalTransferir')).show();
}
</script>

<!-- ── MODAL TRANSFERIR STOCK ────────────────────────── -->
<?php if (count($veterinarias) > 1): ?>
<div class="modal fade" id="modalTransferir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/almacen/transferir">
                <input type="hidden" name="producto_id[]" id="tr-producto-id">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-left-right text-primary me-2"></i>
                        Transferir stock
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">

                    <div class="alert alert-light border py-2 small mb-3">
                        <div class="text-muted mb-1">Producto:</div>
                        <div class="fw-semibold" id="tr-producto-nombre"></div>
                        <div class="text-muted mt-1" id="tr-stock-disp"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sucursal destino <span class="text-danger">*</span></label>
                        <select class="form-select" name="destino_id" id="tr-destino" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($veterinarias as $v): ?>
                            <?php if ((int)$v['id'] === $veterinaria_id) continue; ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="cantidad[]" id="tr-cantidad"
                               min="1" value="1" required>
                    </div>

                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-arrow-left-right me-1"></i> Transferir
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
