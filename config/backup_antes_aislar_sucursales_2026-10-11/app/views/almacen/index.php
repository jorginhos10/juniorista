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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de productos -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2">
                    <h6 class="card-title mb-0 fw-semibold">Productos en almacén</h6>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" class="form-control form-control-sm" id="buscador" placeholder="Buscar producto..." style="width:200px;">
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
                                        <div class="fw-semibold <?= $bloqueado ? 'text-muted' : '' ?>">
                                            <?= htmlspecialchars($p['nombre']) ?>
                                            <?php if ($bloqueado): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size:.65rem;">Bloqueado</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($p['descripcion']): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($p['descripcion']) ?></div>
                                        <?php endif; ?>
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

<!-- ── MODAL AGREGAR PRODUCTO ───────────────────────── -->
<div class="modal fade" id="modalAgregarProducto" tabindex="-1" aria-labelledby="modalAgregarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/almacen/agregar" id="formProducto">
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
                            <label class="form-label fw-semibold">Categoría</label>
                            <input type="text" class="form-control" name="categoria" id="inp-categoria"
                                placeholder="Ej: Vacunas, Medicamentos…"
                                list="listaCategorias">
                            <datalist id="listaCategorias">
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>">
                                <?php endforeach; ?>
                            </datalist>
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

// Editar producto: rellena el modal con los datos existentes
function editarProducto(p) {
    document.getElementById('modalTitulo').textContent = 'Editar producto';
    document.getElementById('formProducto').action = '<?= BASE_URL ?>/almacen/agregar?id=' + p.id;
    ['nombre','descripcion','categoria','unidad','precio_compra','precio_venta','stock_minimo','stock'].forEach(campo => {
        const el = document.getElementById('inp-' + campo);
        if (el) el.value = p[campo] ?? '';
    });
    new bootstrap.Modal(document.getElementById('modalAgregarProducto')).show();
}

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
