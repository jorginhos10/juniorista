<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Configuración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .cfg-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.1rem 1.25rem;
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: .875rem;
            text-decoration: none;
            color: inherit;
            transition: box-shadow .18s, border-color .18s, transform .15s;
        }
        .cfg-item:hover {
            box-shadow: 0 6px 20px rgba(79,70,229,.1);
            border-color: #c7d2fe;
            transform: translateY(-2px);
            color: inherit;
        }
        .cfg-item-icon {
            width: 46px; height: 46px; border-radius: .75rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; flex-shrink: 0;
        }
        .cfg-item-title { font-weight: 700; font-size: .92rem; line-height: 1.3; }
        .cfg-item-desc  { font-size: .76rem; color: #9ca3af; margin: 0; line-height: 1.3; }
        .cfg-item-arrow { margin-left: auto; color: #d1d5db; font-size: .9rem; flex-shrink: 0; }
        .cfg-item:hover .cfg-item-arrow { color: #4f46e5; }
        .cfg-item-off { opacity: .6; cursor: default; }
        .cfg-item-off:hover { box-shadow: none; border-color: #e8eaf0; transform: none; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Configuración</h5>
                <p class="topbar-date mb-0">Administra las preferencias del sistema</p>
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

            <div class="row g-3">

                <div class="col-sm-6 col-xl-4">
                    <div class="cfg-item cfg-item-off" title="Esta opción estará disponible próximamente">
                        <div class="cfg-item-icon" style="background:#eef2ff;">
                            <i class="bi bi-building-fill" style="color:#4f46e5;"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">General</div>
                            <p class="cfg-item-desc">Sucursales y datos de la clínica</p>
                        </div>
                        <span class="badge bg-secondary-soft text-secondary ms-auto">Próximamente</span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <a class="cfg-item" href="<?= BASE_URL ?>/configuracion/sucursales">
                        <div class="cfg-item-icon" style="background:#e0f2fe;">
                            <i class="bi bi-diagram-3-fill" style="color:#0284c7;"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Sucursales</div>
                            <p class="cfg-item-desc">Gestiona tus veterinarias y sedes</p>
                        </div>
                        <i class="bi bi-chevron-right cfg-item-arrow"></i>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <a class="cfg-item" href="<?= BASE_URL ?>/usuarios">
                        <div class="cfg-item-icon bg-primary-soft">
                            <i class="bi bi-people-fill text-primary"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Usuarios</div>
                            <p class="cfg-item-desc">Cuentas, roles y sucursales asignadas</p>
                        </div>
                        <i class="bi bi-chevron-right cfg-item-arrow"></i>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <a class="cfg-item" href="<?= BASE_URL ?>/proveedores">
                        <div class="cfg-item-icon" style="background:#fff7ed;">
                            <i class="bi bi-truck-front-fill" style="color:#ea580c;"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Proveedores</div>
                            <p class="cfg-item-desc">Gestiona tus proveedores de productos</p>
                        </div>
                        <i class="bi bi-chevron-right cfg-item-arrow"></i>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="cfg-item cfg-item-off" title="Esta opción estará disponible próximamente">
                        <div class="cfg-item-icon bg-warning-soft">
                            <i class="bi bi-person-fill text-warning"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Mi perfil</div>
                            <p class="cfg-item-desc">Nombre, correo y contraseña</p>
                        </div>
                        <span class="badge bg-secondary-soft text-secondary ms-auto">Próximamente</span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="cfg-item cfg-item-off" title="Esta opción estará disponible próximamente">
                        <div class="cfg-item-icon bg-danger-soft">
                            <i class="bi bi-shield-lock-fill text-danger"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Seguridad</div>
                            <p class="cfg-item-desc">Sesiones activas y acceso</p>
                        </div>
                        <span class="badge bg-secondary-soft text-secondary ms-auto">Próximamente</span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="cfg-item cfg-item-off" title="Esta opción estará disponible próximamente">
                        <div class="cfg-item-icon bg-success-soft">
                            <i class="bi bi-bell-fill text-success"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Notificaciones</div>
                            <p class="cfg-item-desc">Alertas y preferencias de aviso</p>
                        </div>
                        <span class="badge bg-secondary-soft text-secondary ms-auto">Próximamente</span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="cfg-item cfg-item-off" title="Esta opción estará disponible próximamente">
                        <div class="cfg-item-icon" style="background:#fdf2f8;">
                            <i class="bi bi-download" style="color:#a855f7;"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Exportar datos</div>
                            <p class="cfg-item-desc">Descarga clientes, ventas y productos</p>
                        </div>
                        <span class="badge bg-secondary-soft text-secondary ms-auto">Próximamente</span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="cfg-item cfg-item-off" title="Esta opción estará disponible próximamente">
                        <div class="cfg-item-icon" style="background:#f0fdf4;">
                            <i class="bi bi-database-fill-up" style="color:#16a34a;"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Copias de seguridad</div>
                            <p class="cfg-item-desc">Backup y restauración de datos</p>
                        </div>
                        <span class="badge bg-secondary-soft text-secondary ms-auto">Próximamente</span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <a class="cfg-item" href="<?= BASE_URL ?>/configuracion/peluqueria">
                        <div class="cfg-item-icon" style="background:#fce7f3;">
                            <i class="bi bi-scissors" style="color:#db2777;"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Negocio / Peluquería</div>
                            <p class="cfg-item-desc">Porcentaje de ganancia y de la peluquería</p>
                        </div>
                        <i class="bi bi-chevron-right cfg-item-arrow"></i>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <a class="cfg-item" href="<?= BASE_URL ?>/configuracion/laboratorios">
                        <div class="cfg-item-icon" style="background:#ede9fe;">
                            <i class="bi bi-eyedropper" style="color:#7c3aed;"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Laboratorios</div>
                            <p class="cfg-item-desc">Exámenes y su costo (valor mínimo a cobrar)</p>
                        </div>
                        <i class="bi bi-chevron-right cfg-item-arrow"></i>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <a class="cfg-item" href="<?= BASE_URL ?>/configuracion/apariencia">
                        <div class="cfg-item-icon" style="background:#ffedd5;">
                            <i class="bi bi-palette-fill" style="color:#ea580c;"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Apariencias</div>
                            <p class="cfg-item-desc">Temas visuales: Original y Halloween</p>
                        </div>
                        <i class="bi bi-chevron-right cfg-item-arrow"></i>
                    </a>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="cfg-item cfg-item-off" title="Esta opción estará disponible próximamente">
                        <div class="cfg-item-icon bg-secondary-soft">
                            <i class="bi bi-cpu-fill text-secondary"></i>
                        </div>
                        <div>
                            <div class="cfg-item-title">Sistema</div>
                            <p class="cfg-item-desc">Información del entorno y caché</p>
                        </div>
                        <span class="badge bg-secondary-soft text-secondary ms-auto">Próximamente</span>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
