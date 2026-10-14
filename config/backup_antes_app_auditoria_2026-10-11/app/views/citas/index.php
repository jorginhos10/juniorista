<?php
$diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$diasLargos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$meses      = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

$iconoEspecie = function (?string $especie): string {
    $e = mb_strtolower($especie ?? '');
    if (str_contains($e, 'gat'))                          return 'bi-emoji-smile';
    if (str_contains($e, 'ave') || str_contains($e, 'pájar')) return 'bi-feather';
    if (str_contains($e, 'pez'))                          return 'bi-water';
    return 'bi-heart-fill';
};
$horaFin = fn(array $c): string => date('H:i', strtotime($c['hora']) + (int)$c['duracion'] * 60);

// Agrupar citas por día
$porDia = [];
for ($i = 0; $i < 7; $i++) {
    $porDia[date('Y-m-d', strtotime("$iniSemana +$i day"))] = [];
}
foreach ($citas as $c) {
    $porDia[$c['fecha']][] = $c;
}

$tsIni = strtotime($iniSemana);
$tsFin = strtotime($finSemana);
$rango = date('j', $tsIni) . ' ' . $meses[date('n', $tsIni) - 1]
       . ' – ' . date('j', $tsFin) . ' ' . $meses[date('n', $tsFin) - 1] . ' ' . date('Y', $tsFin);

$semanaPrev = date('Y-m-d', strtotime("$fecha -7 day"));
$semanaSig  = date('Y-m-d', strtotime("$fecha +7 day"));
$esAdmin    = ($usuario['rol'] ?? '') === 'admin';

// Acciones de estado disponibles según el estado actual
$transiciones = [
    'pendiente'  => [['confirmada', 'Confirmar', 'check2-circle'], ['cancelada', 'Cancelar', 'x-circle'], ['no_asistio', 'No asistió', 'person-x']],
    'confirmada' => [['en_curso', 'Iniciar atención', 'play-circle'], ['cancelada', 'Cancelar', 'x-circle'], ['no_asistio', 'No asistió', 'person-x']],
    'en_curso'   => [['atendida', 'Marcar atendida', 'check2-all']],
    'atendida'   => [['en_curso', 'Volver a «En curso»', 'arrow-counterclockwise']],
    'cancelada'  => [['pendiente', 'Reactivar', 'arrow-counterclockwise']],
    'no_asistio' => [['pendiente', 'Reprogramar / reactivar', 'arrow-counterclockwise']],
];
// Acción principal (botón visible) por estado
$accionPrincipal = [
    'pendiente'  => ['confirmada', 'Confirmar', 'check2', 'btn-outline-primary'],
    'confirmada' => ['en_curso', 'Atender', 'play-fill', 'btn-outline-info'],
    'en_curso'   => ['atendida', 'Finalizar', 'check2-all', 'btn-outline-success'],
];

$citasJson = [];
foreach ($citas as $c) {
    $citasJson[$c['id']] = $c;
}
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/citas.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Citas</h5>
                <p class="topbar-date mb-0">Agenda de atenciones de la sucursal</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>

                <button class="btn btn-primary-custom btn-sm" onclick="nuevaCita()">
                    <i class="bi bi-calendar-plus me-1"></i> Nueva cita
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
                            <i class="bi bi-calendar2-check text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['hoy'] ?? 0 ?></div>
                            <div class="stat-label">Citas hoy</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-hourglass-split text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['hoy_por_atender'] ?? 0 ?></div>
                            <div class="stat-label">Por atender hoy</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info-soft">
                            <i class="bi bi-calendar-week text-info"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['semana'] ?? 0 ?></div>
                            <div class="stat-label">
                                Esta semana
                                <?php if (!empty($totales['por_confirmar'])): ?>
                                · <span class="text-warning fw-semibold"><?= $totales['por_confirmar'] ?> por confirmar</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-clipboard2-check text-success"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?= $totales['atendidas_mes'] ?? 0 ?></div>
                            <div class="stat-label">Atendidas este mes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Semana -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <div class="semana-toolbar">
                        <div class="semana-nav">
                            <a href="<?= BASE_URL ?>/citas?fecha=<?= $semanaPrev ?>" class="btn btn-sm btn-light" title="Semana anterior">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/citas?fecha=<?= $hoy ?>" class="btn btn-sm btn-light fw-semibold">Hoy</a>
                            <a href="<?= BASE_URL ?>/citas?fecha=<?= $semanaSig ?>" class="btn btn-sm btn-light" title="Semana siguiente">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            <span class="semana-rango"><?= $rango ?></span>
                            <input type="date" class="form-control form-control-sm" style="width:140px;"
                                   value="<?= $fecha ?>" title="Ir a una fecha"
                                   onchange="if (this.value) location.href='<?= BASE_URL ?>/citas?fecha=' + this.value">
                        </div>
                        <div class="semana-filtros">
                            <select class="form-select form-select-sm" id="filtroEstado" style="width:150px;">
                                <option value="">Todos los estados</option>
                                <option value="activas">Solo activas</option>
                                <?php foreach ($estados as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select form-select-sm" id="filtroVet" style="width:170px;">
                                <option value="">Todos los veterinarios</option>
                                <option value="0">Sin asignar</option>
                                <?php foreach ($veterinarios as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-control form-control-sm" id="filtroTexto"
                                   placeholder="Buscar mascota o dueño…" style="width:190px;">
                        </div>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div class="semana-grid">
                        <?php $i = 0; foreach ($porDia as $dia => $lista): ?>
                        <?php
                            $clases = 'dia-col';
                            if ($dia === $hoy)   $clases .= ' es-hoy';
                            if ($dia === $fecha) $clases .= ' seleccionado';
                            if ($dia < $hoy)     $clases .= ' es-pasado';
                        ?>
                        <div class="<?= $clases ?>" data-dia="<?= $dia ?>">
                            <div class="dia-head" onclick="seleccionarDia('<?= $dia ?>')" title="Ver agenda del día">
                                <div>
                                    <div class="dia-nombre"><?= $diasSemana[$i] ?></div>
                                    <div class="dia-num"><?= date('j', strtotime($dia)) ?></div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="dia-count" data-count="<?= $dia ?>"><?= count($lista) ?></span>
                                    <?php if ($dia >= $hoy): ?>
                                    <button type="button" class="dia-add" title="Agendar este día"
                                            onclick="event.stopPropagation(); nuevaCita('<?= $dia ?>')">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="dia-body">
                                <?php foreach ($lista as $c): ?>
                                <button type="button" class="cita-chip est-<?= $c['estado'] ?> js-cita"
                                        data-estado="<?= $c['estado'] ?>"
                                        data-vet="<?= (int)$c['veterinario_id'] ?>"
                                        data-texto="<?= htmlspecialchars(mb_strtolower($c['mascota_nombre'] . ' ' . $c['cliente_nombre'] . ' ' . ($c['motivo'] ?? ''))) ?>"
                                        onclick="verCita(<?= $c['id'] ?>)">
                                    <span class="chip-hora"><?= substr($c['hora'], 0, 5) ?></span>
                                    <span class="chip-mascota"><?= htmlspecialchars($c['mascota_nombre']) ?></span>
                                    <span class="chip-tipo"><?= htmlspecialchars($tipos[$c['tipo']] ?? $c['tipo']) ?></span>
                                </button>
                                <?php endforeach; ?>
                                <div class="dia-vacio" <?= $lista ? 'style="display:none"' : '' ?>>
                                    <i class="bi bi-calendar4 d-block fs-5 opacity-25 mb-1"></i>Libre
                                </div>
                            </div>
                        </div>
                        <?php $i++; endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Agenda del día -->
            <div class="card border-0 shadow-sm">
                <?php $i = 0; foreach ($porDia as $dia => $lista): ?>
                <div class="agenda-dia" data-agenda="<?= $dia ?>" <?= $dia !== $fecha ? 'style="display:none"' : '' ?>>
                    <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pt-3 pb-2 gap-2 flex-wrap">
                        <h6 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-list-task text-primary me-1"></i>
                            <?= $diasLargos[$i] ?> <?= date('j', strtotime($dia)) ?> de <?= $meses[date('n', strtotime($dia)) - 1] ?>
                            <?php if ($dia === $hoy): ?><span class="badge bg-primary-soft text-primary ms-1">Hoy</span><?php endif; ?>
                        </h6>
                        <?php if ($dia >= $hoy): ?>
                        <button class="btn btn-sm btn-light" onclick="nuevaCita('<?= $dia ?>')">
                            <i class="bi bi-plus-lg me-1"></i>Agendar
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($lista as $c): ?>
                        <div class="agenda-item est-<?= $c['estado'] ?> js-cita"
                             data-estado="<?= $c['estado'] ?>"
                             data-vet="<?= (int)$c['veterinario_id'] ?>"
                             data-texto="<?= htmlspecialchars(mb_strtolower($c['mascota_nombre'] . ' ' . $c['cliente_nombre'] . ' ' . ($c['motivo'] ?? ''))) ?>">
                            <div class="agenda-hora">
                                <?= substr($c['hora'], 0, 5) ?>
                                <small>hasta <?= $horaFin($c) ?></small>
                            </div>
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="mascota-ico"><i class="bi <?= $iconoEspecie($c['mascota_especie']) ?>"></i></span>
                                <div class="min-w-0">
                                    <div>
                                        <span class="agenda-mascota"><?= htmlspecialchars($c['mascota_nombre']) ?></span>
                                        <span class="text-muted small">
                                            (<?= htmlspecialchars($c['mascota_especie'] . ($c['mascota_raza'] ? ' · ' . $c['mascota_raza'] : '')) ?>)
                                        </span>
                                        <span class="badge badge-estado ms-1"><?= $estados[$c['estado']] ?></span>
                                    </div>
                                    <div class="agenda-meta">
                                        <span><i class="bi bi-tag me-1"></i><?= htmlspecialchars($tipos[$c['tipo']] ?? $c['tipo']) ?><?= $c['motivo'] ? ' — ' . htmlspecialchars($c['motivo']) : '' ?></span>
                                        <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($c['cliente_nombre']) ?></span>
                                        <?php if ($c['cliente_telefono']): ?>
                                        <a href="tel:<?= htmlspecialchars($c['cliente_telefono']) ?>"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($c['cliente_telefono']) ?></a>
                                        <?php endif; ?>
                                        <span><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($c['veterinario_nombre'] ?? 'Sin asignar') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="agenda-acciones">
                                <?php if (isset($accionPrincipal[$c['estado']])): [$est, $txt, $ico, $btn] = $accionPrincipal[$c['estado']]; ?>
                                <button class="btn btn-sm <?= $btn ?>" onclick="cambiarEstado(<?= $c['id'] ?>, '<?= $est ?>')">
                                    <i class="bi bi-<?= $ico ?> me-1"></i><?= $txt ?>
                                </button>
                                <?php endif; ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary btn-accion" data-bs-toggle="dropdown" title="Más acciones">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li><a class="dropdown-item" href="#" onclick="verCita(<?= $c['id'] ?>); return false;"><i class="bi bi-eye me-2"></i>Ver detalle</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="editarCita(<?= $c['id'] ?>); return false;"><i class="bi bi-pencil me-2"></i>Editar / reprogramar</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php foreach ($transiciones[$c['estado']] as [$est, $txt, $ico]): ?>
                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $c['id'] ?>, '<?= $est ?>'); return false;"><i class="bi bi-<?= $ico ?> me-2"></i><?= $txt ?></a></li>
                                        <?php endforeach; ?>
                                        <?php if ($esAdmin): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="eliminarCita(<?= $c['id'] ?>); return false;"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="agenda-vacia" <?= $lista ? 'style="display:none"' : '' ?>>
                            <i class="bi bi-calendar-heart fs-1 d-block mb-2 opacity-25"></i>
                            <span class="js-vacia-texto">No hay citas para este día.</span>
                        </div>
                    </div>
                </div>
                <?php $i++; endforeach; ?>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL CITA (crear / editar) ───────────────────── -->
<div class="modal fade" id="modalCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/citas/guardar" id="formCita" autocomplete="off">
                <input type="hidden" name="id" id="c-id" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-calendar-plus text-primary me-2"></i>
                        <span id="citaTitulo">Nueva cita</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Propietario <span class="text-danger">*</span></label>
                            <div class="ac-wrap">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="c-cliente-txt" placeholder="Buscar por nombre, DNI o teléfono…">
                                </div>
                                <input type="hidden" name="cliente_id" id="c-cliente">
                                <div class="ac-list" id="c-cliente-list"></div>
                            </div>
                            <div class="form-text">
                                ¿No está registrado? <a href="<?= BASE_URL ?>/clientes">Crear cliente</a>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mascota <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select" name="mascota_id" id="c-mascota" disabled required>
                                    <option value="">Primero elige el propietario</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="btnNuevaMascota" disabled title="Registrar mascota">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Alta rápida de mascota -->
                        <div class="col-12" id="boxNuevaMascota" style="display:none">
                            <div class="mascota-nueva">
                                <div class="fw-semibold small mb-2"><i class="bi bi-heart-fill text-danger me-1"></i>Nueva mascota</div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm" id="m-nombre" placeholder="Nombre *">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select form-select-sm" id="m-especie">
                                            <option>Perro</option><option>Gato</option><option>Ave</option>
                                            <option>Conejo</option><option>Roedor</option><option>Reptil</option><option>Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control form-control-sm" id="m-raza" placeholder="Raza">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select form-select-sm" id="m-sexo">
                                            <option value="">Sexo</option><option value="macho">Macho</option><option value="hembra">Hembra</option>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-light" onclick="toggleNuevaMascota(false)">Cancelar</button>
                                        <button type="button" class="btn btn-sm btn-primary-custom" id="btnGuardarMascota">
                                            <i class="bi bi-check2 me-1"></i>Guardar mascota
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha" id="c-fecha" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Hora <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="hora" id="c-hora" step="300" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Duración</label>
                            <select class="form-select" name="duracion" id="c-duracion">
                                <?php foreach ([15, 20, 30, 45, 60, 90, 120, 180] as $m): ?>
                                <option value="<?= $m ?>" <?= $m === 30 ? 'selected' : '' ?>><?= $m < 60 ? "$m min" : ($m / 60) . ' h' . ($m % 60 ? ' ' . ($m % 60) . ' min' : '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12" id="ocupadosBox" style="display:none">
                            <div class="small text-muted">
                                <i class="bi bi-clock-history me-1"></i>Ocupado ese día:
                                <span id="ocupadosLista"></span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select class="form-select" name="tipo" id="c-tipo">
                                <?php foreach ($tipos as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Veterinario</label>
                            <select class="form-select" name="veterinario_id" id="c-vet">
                                <option value="0">Sin asignar</option>
                                <?php foreach ($veterinarios as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="estado" id="c-estado">
                                <?php foreach ($estados as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Motivo</label>
                            <input type="text" class="form-control" name="motivo" id="c-motivo" maxlength="200"
                                   placeholder="Ej: vacuna antirrábica, cojea de la pata trasera…">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas</label>
                            <textarea class="form-control" name="notas" id="c-notas" rows="2"
                                      placeholder="Indicaciones, ayuno previo, recordatorios…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> <span id="citaBtnTexto">Agendar cita</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL DETALLE ─────────────────────────────────── -->
<div class="modal fade" id="modalDetalleCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-calendar2-heart text-primary me-2"></i>Detalle de la cita
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleCitaBody"></div>
            <div class="modal-footer border-0 flex-wrap gap-2" id="detalleCitaFooter"></div>
        </div>
    </div>
</div>

<!-- Formularios ocultos para acciones -->
<form method="POST" action="<?= BASE_URL ?>/citas/estado" id="formEstado" class="d-none">
    <input type="hidden" name="id"><input type="hidden" name="estado"><input type="hidden" name="fecha">
</form>
<form method="POST" action="<?= BASE_URL ?>/citas/eliminar" id="formEliminar" class="d-none">
    <input type="hidden" name="id"><input type="hidden" name="fecha">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE      = '<?= BASE_URL ?>';
const HOY       = '<?= $hoy ?>';
const ES_ADMIN  = <?= $esAdmin ? 'true' : 'false' ?>;
const CITAS     = <?= json_encode($citasJson ?: new stdClass, $jsonFlags) ?>;
const CLIENTES  = <?= json_encode($clientes, $jsonFlags) ?>;
const TIPOS     = <?= json_encode($tipos, $jsonFlags) ?>;
const ESTADOS   = <?= json_encode($estados, $jsonFlags) ?>;
const TRANS     = <?= json_encode($transiciones, $jsonFlags) ?>;
const OLD       = <?= json_encode($old, $jsonFlags) ?>;
let   diaActual = '<?= $fecha ?>';

const esc = s => String(s ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
const hhmm = h => String(h).slice(0, 5);
const finDe = c => {
    const [h, m] = c.hora.split(':').map(Number);
    const t = h * 60 + m + Number(c.duracion);
    return String(Math.floor(t / 60) % 24).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');
};
const fechaLarga = f => new Date(f + 'T12:00:00').toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

// ── Selección de día ──────────────────────────────────
function seleccionarDia(dia) {
    diaActual = dia;
    document.querySelectorAll('.dia-col').forEach(el => el.classList.toggle('seleccionado', el.dataset.dia === dia));
    document.querySelectorAll('.agenda-dia').forEach(el => el.style.display = el.dataset.agenda === dia ? '' : 'none');
    history.replaceState(null, '', BASE + '/citas?fecha=' + dia);
}

// ── Filtros ───────────────────────────────────────────
function aplicarFiltros() {
    const est = document.getElementById('filtroEstado').value;
    const vet = document.getElementById('filtroVet').value;
    const q   = document.getElementById('filtroTexto').value.trim().toLowerCase();
    const filtrando = est || vet !== '' || q;

    document.querySelectorAll('.js-cita').forEach(el => {
        let ok = true;
        if (est === 'activas')  ok = !['cancelada', 'no_asistio'].includes(el.dataset.estado);
        else if (est)           ok = el.dataset.estado === est;
        if (ok && vet !== '')   ok = el.dataset.vet === vet;
        if (ok && q)            ok = el.dataset.texto.includes(q);
        el.style.display = ok ? '' : 'none';
    });

    document.querySelectorAll('.dia-col').forEach(col => {
        const n = [...col.querySelectorAll('.js-cita')].filter(e => e.style.display !== 'none').length;
        col.querySelector('.dia-count').textContent = n;
        col.querySelector('.dia-vacio').style.display = n ? 'none' : '';
    });
    document.querySelectorAll('.agenda-dia').forEach(ag => {
        const n = [...ag.querySelectorAll('.js-cita')].filter(e => e.style.display !== 'none').length;
        const vacia = ag.querySelector('.agenda-vacia');
        vacia.style.display = n ? 'none' : '';
        vacia.querySelector('.js-vacia-texto').textContent = filtrando
            ? 'Ninguna cita coincide con los filtros.' : 'No hay citas para este día.';
    });
}
['filtroEstado', 'filtroVet'].forEach(id => document.getElementById(id).addEventListener('change', aplicarFiltros));
document.getElementById('filtroTexto').addEventListener('input', aplicarFiltros);

// ── Acciones de estado ────────────────────────────────
function cambiarEstado(id, estado) {
    const c = CITAS[id];
    const peligrosos = { cancelada: '¿Cancelar la cita de ', no_asistio: '¿Marcar que no asistió ' };
    if (peligrosos[estado] && !confirm(peligrosos[estado] + c.mascota_nombre + '?')) return;
    const f = document.getElementById('formEstado');
    f.id.value = id; f.estado.value = estado; f.fecha.value = c.fecha;
    f.submit();
}

function eliminarCita(id) {
    const c = CITAS[id];
    if (!confirm('¿Eliminar definitivamente la cita de ' + c.mascota_nombre + '? Esta acción no se puede deshacer.')) return;
    const f = document.getElementById('formEliminar');
    f.id.value = id; f.fecha.value = c.fecha;
    f.submit();
}

// ── Detalle ───────────────────────────────────────────
function verCita(id) {
    const c = CITAS[id];
    const fila = (label, val, icon) =>
        `<div class="detalle-fila">
            <span class="detalle-label"><i class="bi bi-${icon} me-1"></i>${label}</span>
            <span class="detalle-valor">${val || '<span class="text-muted">—</span>'}</span>
         </div>`;

    document.getElementById('detalleCitaBody').innerHTML = `
        <div class="detalle-header mb-3 est-${c.estado}">
            <span class="mascota-ico" style="width:52px;height:52px;font-size:1.4rem"><i class="bi bi-heart-fill"></i></span>
            <div>
                <div class="fw-bold fs-5">${esc(c.mascota_nombre)}
                    <span class="badge badge-estado align-middle ms-1">${esc(ESTADOS[c.estado])}</span>
                </div>
                <div class="text-muted small">${esc(c.mascota_especie)}${c.mascota_raza ? ' · ' + esc(c.mascota_raza) : ''}</div>
            </div>
        </div>
        <div class="detalle-grid">
            ${fila('Fecha', '<span class="text-capitalize">' + esc(fechaLarga(c.fecha)) + '</span>', 'calendar3')}
            ${fila('Horario', hhmm(c.hora) + ' – ' + finDe(c) + ' <span class="text-muted">(' + c.duracion + ' min)</span>', 'clock')}
            ${fila('Tipo', esc(TIPOS[c.tipo]), 'tag')}
            ${fila('Motivo', esc(c.motivo), 'chat-left-text')}
            ${fila('Propietario', esc(c.cliente_nombre), 'person')}
            ${fila('Teléfono', c.cliente_telefono ? `<a href="tel:${esc(c.cliente_telefono)}">${esc(c.cliente_telefono)}</a>` : '', 'telephone')}
            ${fila('Veterinario', esc(c.veterinario_nombre || 'Sin asignar'), 'person-badge')}
            ${c.notas ? fila('Notas', esc(c.notas).replace(/\n/g, '<br>'), 'sticky') : ''}
        </div>`;

    const acciones = (TRANS[c.estado] || []).map(([est, txt, ico]) =>
        `<button class="btn btn-sm btn-light" onclick="cambiarEstado(${c.id}, '${est}')"><i class="bi bi-${ico} me-1"></i>${txt}</button>`
    ).join('');
    document.getElementById('detalleCitaFooter').innerHTML = `
        ${ES_ADMIN ? `<button class="btn btn-sm btn-outline-danger me-auto" onclick="eliminarCita(${c.id})"><i class="bi bi-trash"></i></button>` : '<span class="me-auto"></span>'}
        ${acciones}
        <button class="btn btn-sm btn-primary-custom" onclick="editarCita(${c.id})"><i class="bi bi-pencil me-1"></i>Editar</button>`;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalleCita')).show();
}

// ── Formulario de cita ────────────────────────────────
const modalCita = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCita'));
const $ = id => document.getElementById(id);

function horaSugerida(dia) {
    if (dia !== HOY) return '09:00';
    const d = new Date();
    let m = d.getHours() * 60 + d.getMinutes();
    m = Math.ceil((m + 5) / 15) * 15;               // siguiente cuarto de hora
    if (m >= 24 * 60) return '09:00';
    return String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
}

function llenarForm(d) {
    $('c-id').value       = d.id || 0;
    $('c-fecha').value    = d.fecha;
    $('c-hora').value     = hhmm(d.hora);
    $('c-duracion').value = d.duracion || 30;
    $('c-tipo').value     = d.tipo || 'consulta';
    $('c-vet').value      = d.veterinario_id || 0;
    $('c-estado').value   = d.estado || 'pendiente';
    $('c-motivo').value   = d.motivo || '';
    $('c-notas').value    = d.notas || '';
    $('c-fecha').min      = Number(d.id) > 0 ? '' : HOY;
    toggleNuevaMascota(false);

    const cli = CLIENTES.find(x => String(x.id) === String(d.cliente_id));
    seleccionarCliente(cli || null, d.mascota_id);
    actualizarOcupados();

    const editando = Number(d.id) > 0;
    $('citaTitulo').textContent   = editando ? 'Editar cita' : 'Nueva cita';
    $('citaBtnTexto').textContent = editando ? 'Guardar cambios' : 'Agendar cita';
}

function nuevaCita(dia) {
    dia = dia || (diaActual >= HOY ? diaActual : HOY);
    llenarForm({ fecha: dia, hora: horaSugerida(dia), estado: 'pendiente' });
    modalCita().show();
    setTimeout(() => $('c-cliente-txt').focus(), 300);
}

function editarCita(id) {
    bootstrap.Modal.getInstance($('modalDetalleCita'))?.hide();
    llenarForm(CITAS[id]);
    modalCita().show();
}

// Autocompletado de propietario
(function () {
    const input = $('c-cliente-txt'), list = $('c-cliente-list');
    let activo = -1;

    function render() {
        const q = input.value.trim().toLowerCase();
        const res = CLIENTES.filter(c =>
            !q || c.nombre_completo.toLowerCase().includes(q) ||
            (c.dni || '').toLowerCase().includes(q) || (c.telefono || '').includes(q)
        ).slice(0, 30);
        activo = -1;
        list.innerHTML = res.length
            ? res.map(c => `<div class="ac-item" data-id="${c.id}">${esc(c.nombre_completo)}
                   <small class="ms-1">${esc([c.dni, c.telefono].filter(Boolean).join(' · '))}</small></div>`).join('')
            : '<div class="ac-empty">Sin resultados</div>';
        list.style.display = 'block';
    }

    input.addEventListener('focus', render);
    input.addEventListener('input', () => { $('c-cliente').value = ''; resetMascotas(); render(); });
    input.addEventListener('blur', () => setTimeout(() => list.style.display = 'none', 150));
    input.addEventListener('keydown', e => {
        const items = [...list.querySelectorAll('.ac-item')];
        if (!items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            activo = (activo + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
            items.forEach((it, i) => it.classList.toggle('activo', i === activo));
            items[activo].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && list.style.display === 'block') {
            e.preventDefault();
            (items[activo] || items[0]).dispatchEvent(new MouseEvent('mousedown'));
        }
    });
    list.addEventListener('mousedown', e => {
        const it = e.target.closest('.ac-item');
        if (!it) return;
        e.preventDefault?.();
        seleccionarCliente(CLIENTES.find(c => String(c.id) === it.dataset.id));
        list.style.display = 'none';
        $('c-mascota').focus();
    });
})();

function resetMascotas(msg = 'Primero elige el propietario') {
    const sel = $('c-mascota');
    sel.innerHTML = `<option value="">${msg}</option>`;
    sel.disabled = true;
    $('btnNuevaMascota').disabled = true;
}

async function seleccionarCliente(cli, mascotaId) {
    $('c-cliente').value     = cli ? cli.id : '';
    $('c-cliente-txt').value = cli ? cli.nombre_completo : '';
    if (!cli) { resetMascotas(); return; }

    resetMascotas('Cargando…');
    $('btnNuevaMascota').disabled = false;
    try {
        const r = await fetch(BASE + '/citas/mascotas?cliente_id=' + cli.id);
        const mascotas = await r.json();
        if ($('c-cliente').value !== String(cli.id)) return;   // cambió mientras cargaba
        cargarMascotas(mascotas, mascotaId);
    } catch {
        resetMascotas('Error al cargar mascotas');
        $('btnNuevaMascota').disabled = false;
    }
}

function cargarMascotas(mascotas, seleccion) {
    const sel = $('c-mascota');
    if (!mascotas.length) {
        sel.innerHTML = '<option value="">Sin mascotas — registra una con +</option>';
        sel.disabled = true;
        toggleNuevaMascota(true);
        return;
    }
    sel.innerHTML = (mascotas.length > 1 ? '<option value="">Selecciona…</option>' : '') +
        mascotas.map(m => `<option value="${m.id}">${esc(m.nombre)} — ${esc(m.especie)}${m.raza ? ' (' + esc(m.raza) + ')' : ''}</option>`).join('');
    sel.disabled = false;
    if (seleccion) sel.value = String(seleccion);
}

function toggleNuevaMascota(mostrar) {
    $('boxNuevaMascota').style.display = mostrar ? '' : 'none';
    if (mostrar) {
        ['m-nombre', 'm-raza'].forEach(i => $(i).value = '');
        $('m-sexo').value = '';
        setTimeout(() => $('m-nombre').focus(), 50);
    }
}
$('btnNuevaMascota').addEventListener('click', () => toggleNuevaMascota($('boxNuevaMascota').style.display === 'none'));
$('m-nombre').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); $('btnGuardarMascota').click(); } });

$('btnGuardarMascota').addEventListener('click', async function () {
    const nombre = $('m-nombre').value.trim();
    if (!nombre) { $('m-nombre').focus(); return; }
    const body = new FormData();
    body.append('cliente_id', $('c-cliente').value);
    body.append('nombre', nombre);
    body.append('especie', $('m-especie').value);
    body.append('raza', $('m-raza').value.trim());
    body.append('sexo', $('m-sexo').value);

    this.disabled = true;
    try {
        const r = await fetch(BASE + '/citas/mascota/guardar', { method: 'POST', body });
        const data = await r.json();
        if (!r.ok) throw new Error(data.error || 'Error');
        const sel = $('c-mascota');
        if (sel.disabled) sel.innerHTML = '';
        sel.insertAdjacentHTML('beforeend',
            `<option value="${data.id}">${esc(data.nombre)} — ${esc(data.especie)}${data.raza ? ' (' + esc(data.raza) + ')' : ''}</option>`);
        sel.disabled = false;
        sel.value = String(data.id);
        toggleNuevaMascota(false);
    } catch (err) {
        alert(err.message);
    } finally {
        this.disabled = false;
    }
});

// Horarios ocupados del día elegido (de la semana visible)
function actualizarOcupados() {
    const f = $('c-fecha').value, id = String($('c-id').value);
    const ocupadas = Object.values(CITAS)
        .filter(c => c.fecha === f && String(c.id) !== id && !['cancelada', 'no_asistio'].includes(c.estado))
        .sort((a, b) => a.hora.localeCompare(b.hora));
    $('ocupadosBox').style.display = ocupadas.length ? '' : 'none';
    $('ocupadosLista').innerHTML = ocupadas.map(c =>
        `<span class="badge bg-secondary-soft text-secondary me-1">${hhmm(c.hora)}–${finDe(c)} ${esc(c.mascota_nombre)}</span>`
    ).join('');
}
$('c-fecha').addEventListener('change', actualizarOcupados);

$('formCita').addEventListener('submit', e => {
    if (!$('c-cliente').value) { e.preventDefault(); alert('Selecciona un propietario de la lista.'); $('c-cliente-txt').focus(); return; }
    if (!$('c-mascota').value) { e.preventDefault(); alert('Selecciona o registra la mascota.'); }
});

// Reabrir el formulario si el servidor rechazó los datos, o si se pidió "nueva"
document.addEventListener('DOMContentLoaded', () => {
    if (OLD) {
        llenarForm(OLD);
        modalCita().show();
    } else if (<?= $abrirNueva ? 'true' : 'false' ?>) {
        nuevaCita();
    }
});
</script>
</body>
</html>
