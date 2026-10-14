<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Sucursales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .suc-card {
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 1rem;
            overflow: hidden;
            transition: box-shadow .2s, border-color .2s, transform .15s;
        }
        .suc-card:hover {
            box-shadow: 0 8px 24px rgba(79,70,229,.1);
            border-color: #c7d2fe;
            transform: translateY(-3px);
        }
        .suc-card-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 100%);
            padding: 1.5rem 1.25rem 1rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        .suc-avatar {
            width: 48px; height: 48px;
            background: rgba(255,255,255,.2);
            border-radius: .75rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff; flex-shrink: 0;
        }
        .suc-nombre {
            font-weight: 700; font-size: 1rem; color: #fff; line-height: 1.3;
            margin-bottom: .15rem;
        }
        .suc-ruc { font-size: .75rem; color: rgba(255,255,255,.65); }
        .suc-card-body { padding: 1rem 1.25rem; }
        .suc-meta-row {
            display: flex; align-items: center; gap: .5rem;
            font-size: .8rem; color: #6b7280; padding: .3rem 0;
        }
        .suc-meta-row i { color: #9ca3af; font-size: .85rem; flex-shrink: 0; }
        .suc-meta-row span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .suc-card-footer {
            padding: .85rem 1.25rem;
            border-top: 1px solid #f3f4f6;
            display: flex; gap: .5rem;
        }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Sucursales</h5>
                <p class="topbar-date mb-0">Gestión de veterinarias y sedes</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal"
                        data-bs-target="#modalSucursal" onclick="nuevaSucursal()">
                    <i class="bi bi-plus-lg me-1"></i> Nueva sucursal
                </button>

                <a href="<?= BASE_URL ?>/configuracion" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>

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
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"
                               onclick="return confirm('¿Cerrar sesión?')">
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
                        <div class="stat-icon" style="background:#eef2ff;">
                            <i class="bi bi-building-fill" style="color:#4f46e5;"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= count($veterinarias) ?></div>
                            <div class="stat-label">Sucursales activas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-geo-alt-fill text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value">
                                <?= count(array_filter($veterinarias, fn($v) => !empty($v['direccion']))) ?>
                            </div>
                            <div class="stat-label">Con dirección</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-telephone-fill text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value">
                                <?= count(array_filter($veterinarias, fn($v) => !empty($v['telefono']))) ?>
                            </div>
                            <div class="stat-label">Con teléfono</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft">
                            <i class="bi bi-globe text-info"></i>
                        </div>
                        <div>
                            <div class="stat-value">
                                <?= count(array_filter($veterinarias, fn($v) => !empty($v['sitio_web']))) ?>
                            </div>
                            <div class="stat-label">Con sitio web</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de sucursales -->
            <?php if (empty($veterinarias)): ?>
            <div class="card border-0 shadow-sm text-center py-5" style="border-radius:1rem;">
                <i class="bi bi-building fs-1 d-block mb-3 text-muted opacity-25"></i>
                <p class="text-muted mb-3">Aún no tienes sucursales registradas.</p>
                <div>
                    <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal"
                            data-bs-target="#modalSucursal" onclick="nuevaSucursal()">
                        <i class="bi bi-plus-lg me-1"></i> Agregar primera sucursal
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($veterinarias as $v): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="suc-card">
                        <div class="suc-card-header">
                            <div class="suc-avatar">
                                <i class="bi bi-building-fill"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="suc-nombre"><?= htmlspecialchars($v['nombre']) ?></div>
                                <div class="suc-ruc">
                                    <?= $v['ruc'] ? 'RUC: ' . htmlspecialchars($v['ruc']) : 'Sin RUC registrado' ?>
                                </div>
                            </div>
                        </div>
                        <div class="suc-card-body">
                            <div class="suc-meta-row">
                                <i class="bi bi-telephone-fill"></i>
                                <span><?= htmlspecialchars($v['telefono'] ?: '—') ?></span>
                            </div>
                            <div class="suc-meta-row">
                                <i class="bi bi-envelope-fill"></i>
                                <span><?= htmlspecialchars($v['email'] ?: '—') ?></span>
                            </div>
                            <div class="suc-meta-row">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span><?= htmlspecialchars($v['direccion'] ?: '—') ?></span>
                            </div>
                            <div class="suc-meta-row">
                                <i class="bi bi-clock-fill"></i>
                                <span><?= htmlspecialchars($v['horario'] ?: '—') ?></span>
                            </div>
                            <?php if (!empty($v['sitio_web'])): ?>
                            <div class="suc-meta-row">
                                <i class="bi bi-globe"></i>
                                <span><?= htmlspecialchars($v['sitio_web']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="suc-card-footer">
                            <button class="btn btn-primary-custom btn-sm flex-fill"
                                    onclick="editarSucursal(<?= htmlspecialchars(json_encode($v)) ?>)">
                                <i class="bi bi-pencil-fill me-1"></i> Editar
                            </button>
                            <?php if ($v['tiene_datos']): ?>
                            <button class="btn btn-outline-secondary btn-sm" disabled
                                    title="Tiene ventas, ingresos o stock registrado"
                                    data-bs-toggle="tooltip">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                            <?php else: ?>
                            <a href="<?= BASE_URL ?>/configuracion/veterinaria/eliminar?id=<?= $v['id'] ?>"
                               class="btn btn-outline-danger btn-sm"
                               title="Eliminar sucursal"
                               onclick="return confirm('¿Eliminar la sucursal «<?= htmlspecialchars($v['nombre']) ?>»? Esta acción no se puede deshacer.')">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Card agregar nueva -->
                <div class="col-md-6 col-xl-4">
                    <button class="btn w-100 h-100 border-2 border-dashed d-flex flex-column align-items-center
                                   justify-content-center gap-2 text-muted py-4"
                            style="border-style:dashed!important; border-color:#d1d5db!important;
                                   border-radius:1rem; min-height:220px; background:transparent;
                                   transition:background .2s, color .2s;"
                            data-bs-toggle="modal" data-bs-target="#modalSucursal"
                            onclick="nuevaSucursal()"
                            onmouseover="this.style.background='#f5f3ff';this.style.color='#4f46e5';"
                            onmouseout="this.style.background='transparent';this.style.color='';">
                        <i class="bi bi-plus-circle" style="font-size:2rem;"></i>
                        <span class="fw-semibold small">Agregar sucursal</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- Modal Nueva / Editar Sucursal -->
<div class="modal fade" id="modalSucursal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/configuracion/veterinaria/guardar" id="formSucursal">
                <input type="hidden" name="id" id="suc-id" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-building-fill text-primary me-2"></i>
                        <span id="suc-titulo">Nueva sucursal</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="suc-nombre"
                                   placeholder="Ej: Clínica Norte" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">RUC / CUIT</label>
                            <input type="text" class="form-control" name="ruc" id="suc-ruc"
                                   placeholder="Identificación fiscal">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control" name="telefono" id="suc-telefono"
                                       placeholder="555-0000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Correo electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="suc-email"
                                       placeholder="clinica@ejemplo.com">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Dirección</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" name="direccion" id="suc-direccion"
                                       placeholder="Calle, número, ciudad">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Horario de atención</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                <input type="text" class="form-control" name="horario" id="suc-horario"
                                       placeholder="Lun–Vie 8:00–18:00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Sitio web</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="url" class="form-control" name="sitio_web" id="suc-sitio_web"
                                       placeholder="https://…">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> <span id="suc-btn">Agregar sucursal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
<script>
const _campos = ['nombre','ruc','telefono','email','direccion','horario','sitio_web'];

function nuevaSucursal() {
    document.getElementById('suc-titulo').textContent = 'Nueva sucursal';
    document.getElementById('suc-btn').textContent    = 'Agregar sucursal';
    document.getElementById('suc-id').value = '0';
    _campos.forEach(f => { const el = document.getElementById('suc-' + f); if (el) el.value = ''; });
}

function editarSucursal(v) {
    document.getElementById('suc-titulo').textContent = 'Editar sucursal';
    document.getElementById('suc-btn').textContent    = 'Guardar cambios';
    document.getElementById('suc-id').value = v.id;
    _campos.forEach(f => {
        const el = document.getElementById('suc-' + f);
        if (el) el.value = v[f] || '';
    });
    new bootstrap.Modal(document.getElementById('modalSucursal')).show();
}
</script>
</body>
</html>
