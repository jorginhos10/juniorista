<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Proveedores</title>
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
                <h5 class="topbar-title mb-0">Proveedores</h5>
                <p class="topbar-date mb-0">Gestión de proveedores de productos</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal"
                        data-bs-target="#modalProveedor" onclick="nuevoProveedor()">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo proveedor
                </button>
                <div class="dropdown">
                    <button class="btn btn-link topbar-avatar-btn p-0" data-bs-toggle="dropdown">
                        <div class="avatar-md"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><div class="dropdown-header">
                            <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($usuario['email']) ?></div>
                        </div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"
                               onclick="return confirm('¿Cerrar sesión?')">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </a></li>
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
                        <div class="stat-icon" style="background:#fff7ed;">
                            <i class="bi bi-truck-front-fill" style="color:#ea580c;"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['total'] ?? 0 ?></div>
                            <div class="stat-label">Total proveedores</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-check-circle-fill text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['activos'] ?? 0 ?></div>
                            <div class="stat-label">Activos</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary-soft">
                            <i class="bi bi-slash-circle-fill text-secondary"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['inactivos'] ?? 0 ?></div>
                            <div class="stat-label">Inactivos</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-box-seam-fill text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-value">
                                <?= count(array_filter($proveedores, fn($p) => !empty($p['email']))) ?>
                            </div>
                            <div class="stat-label">Con correo</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card border-0 shadow-sm" style="border-radius:.875rem;">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2">
                    <h6 class="fw-bold mb-0">Listado de proveedores</h6>
                    <input type="text" class="form-control form-control-sm" id="buscador"
                           placeholder="Buscar proveedor…" style="width:220px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaProveedores">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Nombre</th>
                                    <th>Contacto</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Dirección</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($proveedores)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-truck-front fs-2 d-block mb-2 opacity-25"></i>
                                        No hay proveedores registrados.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($proveedores as $i => $p): ?>
                                <?php $inactivo = !(bool)$p['activo']; ?>
                                <tr class="<?= $inactivo ? 'fila-bloqueada' : '' ?>">
                                    <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="cliente-avatar" style="background:#fff7ed; color:#ea580c; width:34px; height:34px; font-size:.85rem;">
                                                <?= strtoupper(substr($p['nombre'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold <?= $inactivo ? 'text-muted' : '' ?>">
                                                    <?= htmlspecialchars($p['nombre']) ?>
                                                    <?php if ($inactivo): ?>
                                                    <span class="badge bg-secondary ms-1" style="font-size:.62rem;">Inactivo</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($p['notas']): ?>
                                                <div class="text-muted" style="font-size:.72rem; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                    <?= htmlspecialchars($p['notas']) ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($p['contacto'] ?: '—') ?></td>
                                    <td class="small">
                                        <?php if ($p['telefono']): ?>
                                        <a href="tel:<?= htmlspecialchars($p['telefono']) ?>" class="text-decoration-none text-body">
                                            <i class="bi bi-telephone me-1 text-muted" style="font-size:.75rem;"></i><?= htmlspecialchars($p['telefono']) ?>
                                        </a>
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if ($p['email']): ?>
                                        <a href="mailto:<?= htmlspecialchars($p['email']) ?>" class="text-decoration-none text-body">
                                            <?= htmlspecialchars($p['email']) ?>
                                        </a>
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>
                                    <td class="small text-muted" style="max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars($p['direccion'] ?: '—') ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$inactivo): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Activo
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <i class="bi bi-circle me-1" style="font-size:.5rem;"></i>Inactivo
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick="editarProveedor(<?= htmlspecialchars(json_encode($p)) ?>)">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/proveedores/toggle?id=<?= $p['id'] ?>"
                                               class="btn btn-sm btn-accion <?= $inactivo ? 'btn-outline-success' : 'btn-outline-warning' ?>"
                                               title="<?= $inactivo ? 'Activar' : 'Desactivar' ?>"
                                               onclick="return confirm('<?= $inactivo ? '¿Activar' : '¿Desactivar' ?> a <?= htmlspecialchars($p['nombre']) ?>?')">
                                                <i class="bi <?= $inactivo ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' ?>"></i>
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

<!-- Modal Proveedor -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/proveedores/guardar">
                <input type="hidden" name="id" id="prov-id" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-truck-front-fill me-2" style="color:#ea580c;"></i>
                        <span id="prov-titulo">Nuevo proveedor</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="prov-nombre"
                                   placeholder="Ej: Laboratorios XYZ" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Persona de contacto</label>
                            <input type="text" class="form-control" name="contacto" id="prov-contacto"
                                   placeholder="Nombre del representante">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control" name="telefono" id="prov-telefono"
                                       placeholder="555-0000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Correo electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="prov-email"
                                       placeholder="proveedor@ejemplo.com">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Dirección</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" name="direccion" id="prov-direccion"
                                       placeholder="Calle, número, ciudad">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Notas</label>
                            <textarea class="form-control" name="notas" id="prov-notas" rows="2"
                                      placeholder="Condiciones de pago, observaciones…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> <span id="prov-btn">Registrar proveedor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const _campos = ['nombre','contacto','telefono','email','direccion','notas'];

function nuevoProveedor() {
    document.getElementById('prov-titulo').textContent = 'Nuevo proveedor';
    document.getElementById('prov-btn').textContent    = 'Registrar proveedor';
    document.getElementById('prov-id').value = '0';
    _campos.forEach(f => { const el = document.getElementById('prov-' + f); if (el) el.value = ''; });
}

function editarProveedor(p) {
    document.getElementById('prov-titulo').textContent = 'Editar proveedor';
    document.getElementById('prov-btn').textContent    = 'Guardar cambios';
    document.getElementById('prov-id').value = p.id;
    _campos.forEach(f => { const el = document.getElementById('prov-' + f); if (el) el.value = p[f] || ''; });
    new bootstrap.Modal(document.getElementById('modalProveedor')).show();
}

document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaProveedores tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
