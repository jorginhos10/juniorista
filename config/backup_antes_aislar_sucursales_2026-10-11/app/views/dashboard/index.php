<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <!-- ── CONTENIDO PRINCIPAL ──────────────────────── -->
    <div class="main-wrapper">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Dashboard</h5>
                <p class="topbar-date mb-0"><?= date('l, d \d\e F \d\e Y') ?></p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <a href="<?= BASE_URL ?>/citas?nueva=1" class="btn btn-primary-custom btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nueva cita
                </a>
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
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/configuracion"><i class="bi bi-gear me-2"></i>Configuración</a></li>
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

        <!-- Contenido -->
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

            <?php
            // Cada bloque se muestra solo si el usuario tiene permiso para el módulo correspondiente
            require_once ROOT . '/app/helpers/Permisos.php';
            $verCitas    = Permisos::puede('citas');
            $verIngresos = Permisos::puede('facturacion') || Permisos::puede('ventas');
            $tarjetas = array_filter([
                $verCitas ? ['calendar2-check', 'primary', count(array_filter($citasHoy, fn($c) => !in_array($c['estado'], ['cancelada', 'no_asistio'], true))), 'Citas hoy', ''] : null,
                Permisos::puede('mascotas') ? ['heart-fill', 'success', $totalMascotas, 'Mascotas registradas', ''] : null,
                Permisos::puede('clientes') ? ['people-fill', 'warning', $totalClientes, 'Clientes activos', ''] : null,
                $verIngresos ? ['currency-dollar', 'info', '$' . number_format($ingresosMes, 2), 'Ingresos del mes', 'Ventas, peluquería y abonos de tratamientos de la sucursal'] : null,
            ]);
            $accesos = array_filter([
                Permisos::puede('clientes')    ? ['clientes',      'person-plus',   'primary',   'Registrar cliente'] : null,
                Permisos::puede('mascotas')    ? ['mascotas',      'heart-fill',    'success',   'Nueva mascota'] : null,
                $verCitas                      ? ['citas?nueva=1', 'calendar-plus', 'warning',   'Agendar cita'] : null,
                Permisos::puede('facturacion') ? ['facturacion',   'receipt',       'info',      'Facturación'] : null,
                Permisos::puede('almacen')     ? ['almacen',       'box-seam',      'secondary', 'Inventario'] : null,
            ]);
            ?>

            <!-- Stat cards -->
            <?php if ($tarjetas): ?>
            <div class="row g-3 mb-4">
                <?php foreach ($tarjetas as [$icono, $color, $valor, $etiqueta, $ayuda]): ?>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-<?= $color ?>-soft">
                            <i class="bi bi-<?= $icono ?> text-<?= $color ?>"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= htmlspecialchars((string)$valor) ?></div>
                            <div class="stat-label" <?= $ayuda ? 'title="' . htmlspecialchars($ayuda) . '"' : '' ?>><?= $etiqueta ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Tabla + accesos -->
            <div class="row g-3">
                <?php if ($verCitas): ?>
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 fw-semibold">Citas de hoy</h6>
                            <a href="<?= BASE_URL ?>/citas" class="small text-decoration-none">Ver agenda <i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Hora</th>
                                            <th>Mascota</th>
                                            <th>Propietario</th>
                                            <th>Motivo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $estadoBadge = [
                                                'pendiente'  => 'bg-warning-soft text-warning', 'confirmada' => 'bg-primary-soft text-primary',
                                                'en_curso'   => 'bg-info-soft text-info',       'atendida'   => 'bg-success-soft text-success',
                                                'cancelada'  => 'bg-secondary-soft text-secondary', 'no_asistio' => 'bg-danger-soft text-danger',
                                            ];
                                        ?>
                                        <?php if (empty($citasHoy)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="bi bi-calendar-heart fs-3 d-block mb-1 opacity-25"></i>
                                                No hay citas para hoy. <a href="<?= BASE_URL ?>/citas?nueva=1">Agendar una</a>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php foreach ($citasHoy as $c): ?>
                                        <tr>
                                            <td class="ps-3 text-muted small"><?= substr($c['hora'], 0, 5) ?></td>
                                            <td><span class="fw-semibold"><?= htmlspecialchars($c['mascota_nombre']) ?></span> <span class="text-muted small">(<?= htmlspecialchars($c['mascota_raza'] ?: $c['mascota_especie']) ?>)</span></td>
                                            <td><?= htmlspecialchars($c['cliente_nombre']) ?></td>
                                            <td><?= htmlspecialchars($c['motivo'] ?: (CitaModel::TIPOS[$c['tipo']] ?? '')) ?></td>
                                            <td><span class="badge <?= $estadoBadge[$c['estado']] ?>"><?= CitaModel::ESTADOS[$c['estado']] ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($accesos): ?>
                <div class="<?= $verCitas ? 'col-lg-4' : 'col-lg-6' ?>">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 pt-3 pb-2">
                            <h6 class="card-title mb-0 fw-semibold">Accesos rápidos</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php foreach ($accesos as [$ruta, $icono, $color, $texto]): ?>
                                <a href="<?= BASE_URL ?>/<?= $ruta ?>" class="btn btn-light text-start d-flex align-items-center gap-2">
                                    <i class="bi bi-<?= $icono ?> text-<?= $color ?>"></i> <?= $texto ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$tarjetas && !$verCitas && !$accesos): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-5">
                            <i class="bi bi-shield-lock fs-1 d-block mb-2 opacity-25"></i>
                            Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?>. Usa el menú para ir a los módulos que tienes habilitados.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div><!-- /.main-wrapper -->

</div><!-- /.app-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
