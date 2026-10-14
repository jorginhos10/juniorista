<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .suc-toggles { display: flex; flex-wrap: wrap; gap: .5rem; }
        .suc-toggle {
            display: inline-flex; align-items: center; gap: .45rem; cursor: pointer; user-select: none;
            border: 1.5px solid #e2e8f0; border-radius: 999px; padding: .45rem .9rem;
            font-size: .82rem; font-weight: 600; color: #64748b; background: #fff;
            transition: background .15s, border-color .15s, color .15s;
        }
        .suc-toggle:hover { border-color: var(--primary-light); color: #1e293b; }
        .suc-toggle .suc-check { display: none; }
        .btn-check:checked + .suc-toggle { background: var(--primary); border-color: var(--primary); color: #fff; }
        .btn-check:checked + .suc-toggle .suc-check { display: inline; }
        .btn-check:focus-visible + .suc-toggle { box-shadow: 0 0 0 .2rem rgba(79, 70, 229, .25); }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Usuarios</h5>
                <p class="topbar-date mb-0">Gestión de cuentas del sistema</p>
            </div>
            <div class="topbar-right">
                <!-- Veterinaria -->
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario"
                        onclick="nuevoUsuario()">
                    <i class="bi bi-person-plus-fill me-1"></i> Nuevo usuario
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
                            <div class="stat-label">Total usuarios</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-person-check-fill text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['activos'] ?? 0 ?></div>
                            <div class="stat-label">Activos</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-shield-fill text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['admins'] ?? 0 ?></div>
                            <div class="stat-label">Administradores</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft">
                            <i class="bi bi-heart-pulse-fill text-info"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= ($totales['veterinarios'] ?? 0) + ($totales['recepcionistas'] ?? 0) ?></div>
                            <div class="stat-label">Staff clínico</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2">
                    <h6 class="card-title mb-0 fw-semibold">Listado de usuarios</h6>
                    <input type="text" class="form-control form-control-sm" id="buscador"
                           placeholder="Buscar por nombre o correo…" style="width:220px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th class="text-center">Rol</th>
                                    <th class="text-center">Sucursal</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Registro</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php
                                $rolConfig = [
                                    'admin'       => ['label' => 'Admin',        'color' => 'danger'],
                                    'veterinario' => ['label' => 'Veterinario',  'color' => 'primary'],
                                    'recepcion'   => ['label' => 'Recepción',    'color' => 'secondary'],
                                ];
                                ?>
                                <?php foreach ($usuarios as $i => $u): ?>
                                <?php
                                    $rc  = $rolConfig[$u['rol']] ?? ['label' => ucfirst($u['rol']), 'color' => 'secondary'];
                                    $esMismo = (int)$u['id'] === (int)$usuario['id'];
                                ?>
                                <tr class="<?= !$u['activo'] ? 'fila-bloqueada' : '' ?>">
                                    <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="cliente-avatar" style="background:var(--primary-color)">
                                                <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold <?= !$u['activo'] ? 'text-muted' : '' ?>">
                                                    <?= htmlspecialchars($u['nombre']) ?>
                                                    <?php if ($esMismo): ?>
                                                    <span class="badge bg-primary-soft text-primary ms-1" style="font-size:.65rem;">Tú</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $rc['color'] ?>-subtle text-<?= $rc['color'] ?> border border-<?= $rc['color'] ?>-subtle">
                                            <?= $rc['label'] ?>
                                        </span>
                                        <?php $nMod = $u['rol'] === 'admin' ? count($modulos) : count(Permisos::decodificar($u['permisos'])); ?>
                                        <div class="small mt-1 <?= $nMod < count($modulos) ? 'text-warning' : 'text-muted' ?>" style="font-size:.72rem">
                                            <?= $nMod === count($modulos) ? 'Acceso total' : "$nMod de " . count($modulos) . ' módulos' ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $_asig = array_filter($listaSucursales, fn($v) => in_array((int)$v['id'], Permisos::decodificarSucursales($u['sucursales']), true));
                                        ?>
                                        <?php if ($_asig): ?>
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <?php foreach ($_asig as $_v): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:.72rem;">
                                                <i class="bi bi-building me-1"></i><?= htmlspecialchars($_v['nombre']) ?>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted small">Todas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($u['activo']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Activo
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <i class="bi bi-circle me-1" style="font-size:.5rem;"></i>Inactivo
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick="editarUsuario(<?= htmlspecialchars(json_encode($u)) ?>)">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary btn-accion" title="Cambiar contraseña"
                                                    onclick="abrirPassword(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['nombre'])) ?>)">
                                                <i class="bi bi-key-fill"></i>
                                            </button>
                                            <?php if ($u['rol'] === 'admin'): ?>
                                            <span title="El administrador tiene acceso a todos los módulos">
                                                <button class="btn btn-sm btn-outline-info btn-accion" disabled><i class="bi bi-shield-check"></i></button>
                                            </span>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-outline-info btn-accion" title="Permisos de módulos"
                                                    onclick="abrirPermisos(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['nombre'])) ?>, <?= htmlspecialchars(json_encode(Permisos::decodificar($u['permisos']))) ?>)">
                                                <i class="bi bi-shield-lock-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (!$esMismo): ?>
                                            <a href="<?= BASE_URL ?>/usuarios/toggle?id=<?= $u['id'] ?>"
                                               class="btn btn-sm btn-accion <?= $u['activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                               title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>"
                                               onclick="return confirm(<?= htmlspecialchars(json_encode(($u['activo'] ? '¿Desactivar' : '¿Activar') . ' a ' . $u['nombre'] . '?')) ?>)">
                                                <i class="bi <?= $u['activo'] ? 'bi-person-dash-fill' : 'bi-person-check-fill' ?>"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar"
                                                    onclick="eliminarUsuario(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode($u['nombre'])) ?>)">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                            <?php endif; ?>
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

<!-- ── MODAL USUARIO (crear / editar) ─────────────────── -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/usuarios/guardar" id="formUsuario">
                <input type="hidden" name="id" id="inp-id" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-fill text-primary me-2"></i>
                        <span id="modalTitulo">Nuevo usuario</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="inp-nombre"
                                   placeholder="Ej: Ana García" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="inp-email"
                                       placeholder="correo@ejemplo.com" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" name="rol" id="inp-rol">
                                <option value="recepcion">Recepción</option>
                                <option value="veterinario">Veterinario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                Sucursales asignadas
                                <span class="badge bg-secondary-soft text-secondary fw-semibold" id="suc-resumen"></span>
                            </label>
                            <div class="suc-toggles">
                                <?php foreach ($listaSucursales as $v): ?>
                                <input type="checkbox" class="btn-check js-sucursal" name="sucursales[]" value="<?= (int)$v['id'] ?>"
                                       id="suc-<?= (int)$v['id'] ?>" autocomplete="off">
                                <label class="suc-toggle" for="suc-<?= (int)$v['id'] ?>">
                                    <i class="bi bi-building"></i>
                                    <span><?= htmlspecialchars($v['nombre']) ?></span>
                                    <i class="bi bi-check-circle-fill suc-check"></i>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text" id="suc-ayuda"></div>
                        </div>

                        <div class="col-12" id="campoPassword">
                            <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" name="password" id="inp-password"
                                       placeholder="Mínimo 6 caracteres">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleVer('inp-password')">
                                    <i class="bi bi-eye" id="eye-password"></i>
                                </button>
                            </div>
                            <div class="form-text">Solo se requiere al crear un nuevo usuario.</div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> <span id="btnTexto">Crear usuario</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL CAMBIAR CONTRASEÑA ───────────────────────── -->
<div class="modal fade" id="modalPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/usuarios/password">
                <input type="hidden" name="id" id="pass-id" value="">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-key-fill text-warning me-2"></i>
                        Cambiar contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <p class="text-muted small mb-3">
                        Cambiando contraseña de <strong id="pass-nombre"></strong>
                    </p>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="inp-nueva-pass"
                               placeholder="Nueva contraseña" required minlength="6">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleVer('inp-nueva-pass')">
                            <i class="bi bi-eye" id="eye-nueva-pass"></i>
                        </button>
                    </div>
                    <div class="form-text">Mínimo 6 caracteres.</div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL PERMISOS ────────────────────────────────── -->
<div class="modal fade" id="modalPermisos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/usuarios/permisos" id="formPermisos">
                <input type="hidden" name="id" id="perm-id">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold"><i class="bi bi-shield-lock-fill text-info me-2"></i>Permisos de módulos</h5>
                        <div class="text-muted small">Usuario: <strong id="perm-nombre"></strong> · Dashboard y Configuración siempre están disponibles.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <span class="small text-muted"><span id="perm-cuenta" class="fw-semibold"></span> módulos activos</span>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-light" onclick="marcarPermisos(true)"><i class="bi bi-toggle-on me-1"></i>Activar todos</button>
                            <button type="button" class="btn btn-sm btn-light" onclick="marcarPermisos(false)"><i class="bi bi-toggle-off me-1"></i>Apagar todos</button>
                        </div>
                    </div>
                    <div class="row g-3">
                        <?php
                        $porCategoria = [];
                        foreach ($modulos as $clave => [$nombre, $icono, $categoria]) {
                            $porCategoria[$categoria][$clave] = [$nombre, $icono];
                        }
                        foreach ($porCategoria as $categoria => $items): ?>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-uppercase fw-bold text-muted" style="font-size:.7rem;letter-spacing:.06em"><?= $categoria ?></span>
                                </div>
                                <?php foreach ($items as $clave => [$nombre, $icono]): ?>
                                <div class="form-check form-switch d-flex align-items-center gap-2 py-1 mb-0">
                                    <input class="form-check-input js-permiso" type="checkbox" role="switch" name="modulos[]"
                                           value="<?= $clave ?>" id="perm-<?= $clave ?>">
                                    <label class="form-check-label d-flex align-items-center gap-2" for="perm-<?= $clave ?>">
                                        <i class="bi bi-<?= $icono ?> text-muted"></i><?= $nombre ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save me-1"></i>Guardar permisos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/usuarios/eliminar" id="formEliminarUsuario" class="d-none">
    <input type="hidden" name="id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
function nuevoUsuario() {
    document.getElementById('modalTitulo').textContent = 'Nuevo usuario';
    document.getElementById('btnTexto').textContent    = 'Crear usuario';
    document.getElementById('inp-id').value       = '0';
    document.getElementById('inp-nombre').value   = '';
    document.getElementById('inp-email').value    = '';
    document.getElementById('inp-rol').value      = 'recepcion';
    marcarSucursales([]);
    document.getElementById('inp-password').value = '';
    document.getElementById('campoPassword').style.display = '';
    document.getElementById('inp-password').required = true;
}

function editarUsuario(u) {
    document.getElementById('modalTitulo').textContent = 'Editar usuario';
    document.getElementById('btnTexto').textContent    = 'Guardar cambios';
    document.getElementById('inp-id').value       = u.id;
    document.getElementById('inp-nombre').value   = u.nombre;
    document.getElementById('inp-email').value    = u.email;
    document.getElementById('inp-rol').value      = u.rol;
    marcarSucursales(JSON.parse(u.sucursales || '[]'));
    document.getElementById('inp-password').value = '';
    document.getElementById('campoPassword').style.display = 'none';
    document.getElementById('inp-password').required = false;
    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}

function eliminarUsuario(id, nombre) {
    if (!confirm('¿Eliminar a ' + nombre + '?\n\nYa no podrá iniciar sesión ni aparecerá en el listado. '
               + 'Sus ventas, abonos y demás registros conservarán su nombre. Esta acción no se puede deshacer.')) return;
    const f = document.getElementById('formEliminarUsuario');
    f.id.value = id;
    f.submit();
}

// ── Sucursales asignadas (interruptores) ──────────────
function marcarSucursales(ids) {
    document.querySelectorAll('.js-sucursal').forEach(c => c.checked = ids.map(String).includes(c.value));
    resumenSucursales();
}

function resumenSucursales() {
    const todas = document.querySelectorAll('.js-sucursal');
    const marcadas = [...todas].filter(c => c.checked);
    const n = marcadas.length;
    const resumen = document.getElementById('suc-resumen'), ayuda = document.getElementById('suc-ayuda');
    if (n === 0 || n === todas.length) {
        resumen.textContent = 'Todas';
        ayuda.innerHTML = '<i class="bi bi-info-circle me-1"></i>Sin restricción: puede trabajar en todas las sucursales y cambiar entre ellas.';
    } else if (n === 1) {
        resumen.textContent = '1 sucursal';
        ayuda.innerHTML = '<i class="bi bi-lock-fill me-1"></i>Queda fijo en esta sucursal; no podrá cambiarla.';
    } else {
        resumen.textContent = n + ' sucursales';
        ayuda.innerHTML = '<i class="bi bi-arrow-left-right me-1"></i>Podrá cambiar solo entre las sucursales marcadas.';
    }
}
document.querySelectorAll('.js-sucursal').forEach(c => c.addEventListener('change', resumenSucursales));

function abrirPermisos(id, nombre, habilitados) {
    document.getElementById('perm-id').value = id;
    document.getElementById('perm-nombre').textContent = nombre;
    document.querySelectorAll('.js-permiso').forEach(c => c.checked = habilitados.includes(c.value));
    contarPermisos();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPermisos')).show();
}

function marcarPermisos(estado) {
    document.querySelectorAll('.js-permiso').forEach(c => c.checked = estado);
    contarPermisos();
}

function contarPermisos() {
    const todos = document.querySelectorAll('.js-permiso');
    const activos = [...todos].filter(c => c.checked).length;
    document.getElementById('perm-cuenta').textContent = activos + ' de ' + todos.length;
}
document.querySelectorAll('.js-permiso').forEach(c => c.addEventListener('change', contarPermisos));

function abrirPassword(id, nombre) {
    document.getElementById('pass-id').value      = id;
    document.getElementById('pass-nombre').textContent = nombre;
    document.getElementById('inp-nueva-pass').value   = '';
    new bootstrap.Modal(document.getElementById('modalPassword')).show();
}

function toggleVer(inputId) {
    const inp = document.getElementById(inputId);
    const eye = document.getElementById('eye-' + inputId);
    if (inp.type === 'password') {
        inp.type = 'text';
        eye.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        eye.className = 'bi bi-eye';
    }
}

// Buscador
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaUsuarios tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
