<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Clientes</title>
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
                <h5 class="topbar-title mb-0">Clientes</h5>
                <p class="topbar-date mb-0">Gestión de propietarios de mascotas</p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="nuevoCliente()">
                    <i class="bi bi-person-plus-fill me-1"></i> Nuevo cliente
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

            <!-- Flash -->
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
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-people-fill text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['total'] ?? 0 ?></div>
                            <div class="stat-label">Clientes registrados</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-person-check-fill text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['nuevos_mes'] ?? 0 ?></div>
                            <div class="stat-label">Nuevos este mes</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-paw-fill text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= array_sum(array_column($clientes, 'total_mascotas')) ?></div>
                            <div class="stat-label">Mascotas en total</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft">
                            <i class="bi bi-graph-up-arrow text-info"></i>
                        </div>
                        <div>
                            <div class="stat-value">
                                <?php
                                    $total = $totales['total'] ?? 0;
                                    $conMascotas = count(array_filter($clientes, fn($c) => $c['total_mascotas'] > 0));
                                    echo $total > 0 ? round(($conMascotas / $total) * 100) . '%' : '0%';
                                ?>
                            </div>
                            <div class="stat-label">Con mascotas activas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                    <h6 class="card-title mb-0 fw-semibold">Listado de clientes</h6>
                    <input type="text" class="form-control form-control-sm" id="buscador"
                        placeholder="Buscar por nombre, DNI, email…" style="width:220px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaClientes">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Dirección</th>
                                    <th class="text-center">Mascotas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clientes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                                        No hay clientes registrados.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($clientes as $i => $c): ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="cliente-avatar">
                                                <?= strtoupper(substr($c['nombre'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></div>
                                                <div class="text-muted small">Desde <?= date('M Y', strtotime($c['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($c['dni'] ?: '—') ?></td>
                                    <td>
                                        <?php if ($c['telefono']): ?>
                                        <a href="tel:<?= htmlspecialchars($c['telefono']) ?>" class="text-decoration-none text-body">
                                            <i class="bi bi-telephone me-1 text-muted small"></i><?= htmlspecialchars($c['telefono']) ?>
                                        </a>
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['email']): ?>
                                        <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="text-decoration-none small text-body">
                                            <?= htmlspecialchars($c['email']) ?>
                                        </a>
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>
                                    <td class="text-muted small" style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars($c['direccion'] ?: '—') ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/mascotas?cliente=<?= $c['id'] ?>" title="Ver mascotas"
                                           class="badge text-decoration-none <?= $c['total_mascotas'] > 0 ? 'bg-success-soft text-success' : 'bg-secondary-soft text-secondary' ?>">
                                            <i class="bi bi-heart-fill me-1"></i><?= $c['total_mascotas'] ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-secondary btn-accion" title="Ver detalle"
                                                onclick="verCliente(<?= htmlspecialchars(json_encode($c)) ?>)">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                onclick="editarCliente(<?= htmlspecialchars(json_encode($c)) ?>)">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/clientes/eliminar?id=<?= $c['id'] ?>"
                                               class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar"
                                               onclick="return confirm('¿Eliminar a <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?>?')">
                                                <i class="bi bi-trash-fill"></i>
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
                <?php if (!empty($clientes)): ?>
                <div class="card-footer bg-white text-muted small border-0">
                    Mostrando <span id="contadorVisible"><?= count($clientes) ?></span> de <?= count($clientes) ?> clientes
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL CLIENTE (crear / editar) ───────────────── -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/clientes/guardar" id="formCliente">
                <input type="hidden" name="id" id="inp-id" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-fill text-primary me-2"></i>
                        <span id="modalTitulo">Nuevo cliente</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="inp-nombre"
                                placeholder="Nombre" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="apellido" id="inp-apellido"
                                placeholder="Apellido" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">DNI / Documento</label>
                            <input type="text" class="form-control" name="dni" id="inp-dni"
                                placeholder="Ej: 12345678">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control" name="telefono" id="inp-telefono"
                                    placeholder="Ej: 555-1234">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Correo electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="inp-email"
                                    placeholder="correo@ejemplo.com">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Dirección</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" name="direccion" id="inp-direccion"
                                    placeholder="Calle, número, ciudad…">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas</label>
                            <textarea class="form-control" name="notas" id="inp-notas" rows="2"
                                placeholder="Observaciones adicionales…"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> <span id="btnGuardarTexto">Registrar cliente</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL VER DETALLE ─────────────────────────────── -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-circle text-primary me-2"></i>
                    Detalle del cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleBody"></div>
            <div class="modal-footer border-0">
                <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const campos = ['nombre','apellido','dni','telefono','email','direccion','notas'];

function nuevoCliente() {
    document.getElementById('modalTitulo').textContent   = 'Nuevo cliente';
    document.getElementById('btnGuardarTexto').textContent = 'Registrar cliente';
    document.getElementById('inp-id').value = '0';
    campos.forEach(c => { const el = document.getElementById('inp-' + c); if (el) el.value = ''; });
}

function editarCliente(c) {
    document.getElementById('modalTitulo').textContent   = 'Editar cliente';
    document.getElementById('btnGuardarTexto').textContent = 'Guardar cambios';
    document.getElementById('inp-id').value = c.id;
    campos.forEach(campo => {
        const el = document.getElementById('inp-' + campo);
        if (el) el.value = c[campo] ?? '';
    });
    new bootstrap.Modal(document.getElementById('modalCliente')).show();
}

function verCliente(c) {
    const fila = (label, val, icon = '') =>
        `<div class="detalle-fila">
            <span class="detalle-label"><i class="bi bi-${icon} me-1"></i>${label}</span>
            <span class="detalle-valor">${val || '<span class="text-muted">—</span>'}</span>
         </div>`;

    document.getElementById('detalleBody').innerHTML = `
        <div class="detalle-header mb-3">
            <div class="detalle-avatar">${escHtml(c.nombre.charAt(0).toUpperCase())}</div>
            <div>
                <div class="fw-bold fs-5">${escHtml(c.nombre)} ${escHtml(c.apellido)}</div>
                <div class="text-muted small">Cliente desde ${new Date(c.created_at).toLocaleDateString('es', {month:'long', year:'numeric'})}</div>
            </div>
        </div>
        <div class="detalle-grid">
            ${fila('DNI', escHtml(c.dni), 'card-text')}
            ${fila('Teléfono', c.telefono ? `<a href="tel:${encodeURIComponent(c.telefono)}">${escHtml(c.telefono)}</a>` : '', 'telephone')}
            ${fila('Email', c.email ? `<a href="mailto:${encodeURIComponent(c.email)}">${escHtml(c.email)}</a>` : '', 'envelope')}
            ${fila('Dirección', escHtml(c.direccion), 'geo-alt')}
            ${fila('Mascotas', c.total_mascotas, 'paw-fill')}
            ${c.notas ? fila('Notas', escHtml(c.notas), 'sticky') : ''}
        </div>`;

    new bootstrap.Modal(document.getElementById('modalDetalle')).show();
}

// Buscador
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let visibles = 0;
    document.querySelectorAll('#tablaClientes tbody tr').forEach(tr => {
        const mostrar = tr.textContent.toLowerCase().includes(q);
        tr.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    const cont = document.getElementById('contadorVisible');
    if (cont) cont.textContent = visibles;
});
</script>
</body>
</html>
