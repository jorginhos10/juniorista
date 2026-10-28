<?php
$iconos = ['Perro' => '🐶', 'Gato' => '🐱', 'Ave' => '🐦', 'Conejo' => '🐰', 'Roedor' => '🐹', 'Reptil' => '🦎', 'Otro' => '🐾'];
$meses  = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

$fechaCorta = fn(?string $f) => $f ? date('j', strtotime($f)) . ' ' . $meses[date('n', strtotime($f)) - 1] . ' ' . date('Y', strtotime($f)) : '';
$edad = function (?string $nac): string {
    if (!$nac) return '';
    $diff = (new DateTime($nac))->diff(new DateTime('today'));
    if ($diff->y > 0) return $diff->y . ($diff->y === 1 ? ' año' : ' años');
    if ($diff->m > 0) return $diff->m . ($diff->m === 1 ? ' mes' : ' meses');
    return $diff->d . ($diff->d === 1 ? ' día' : ' días');
};
$esAdmin = $usuario['rol'] === 'admin';

// Datos de la ficha seleccionada
$pesos = [];
if ($mascota) {
    foreach (array_reverse($registros) as $r) {
        if ($r['peso'] !== null) $pesos[] = ['fecha' => $r['fecha'], 'peso' => (float)$r['peso']];
    }
}
$ultimoPeso = $pesos ? end($pesos) : null;
$rango      = $mascota ? ($rangos[$mascota['especie']] ?? null) : null;

$fueraDeRango = function (array $r, string $campo) use ($rango): bool {
    if (!$rango || !isset($rango[$campo]) || $r[$campo] === null) return false;
    return $r[$campo] < $rango[$campo][0] || $r[$campo] > $rango[$campo][1];
};

// Sparkline de peso (SVG)
$sparkline = '';
if (count($pesos) >= 2) {
    $vals = array_column($pesos, 'peso');
    [$min, $max] = [min($vals), max($vals)];
    $span = ($max - $min) ?: 1;
    $w = 220; $h = 46; $n = count($vals) - 1;
    $pts = [];
    foreach ($vals as $i => $v) {
        $pts[] = round($i / $n * ($w - 8) + 4, 1) . ',' . round($h - 6 - ($v - $min) / $span * ($h - 12), 1);
    }
    $sparkline = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" aria-hidden="true">'
        . '<polyline fill="none" stroke="var(--primary)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" points="' . implode(' ', $pts) . '"/>'
        . '<circle cx="' . explode(',', end($pts))[0] . '" cy="' . explode(',', end($pts))[1] . '" r="3.5" fill="var(--primary)"/></svg>';
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Historial médico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/historial.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Historial médico</h5>
                <p class="topbar-date mb-0">Historia clínica de cada paciente</p>
            </div>
            <div class="topbar-right">
                <?php require ROOT . '/app/views/layouts/vet_selector.php'; ?>
                <?php if ($mascota): ?>
                <button class="btn btn-primary-custom btn-sm" onclick="nuevoRegistro()">
                    <i class="bi bi-journal-plus me-1"></i> Nuevo registro
                </button>
                <?php endif; ?>
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

            <div class="row g-3">

                <!-- ── Columna izquierda: pacientes ───────────── -->
                <div class="col-lg-4 col-xxl-3">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-0 pt-3 pb-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="card-title mb-0 fw-semibold">Pacientes</h6>
                                <span class="badge bg-secondary-soft text-secondary"><?= count($mascotas) ?></span>
                            </div>
                            <input type="text" class="form-control form-control-sm" id="buscarPaciente" placeholder="Buscar mascota o dueño…">
                        </div>
                        <div class="paciente-lista" id="listaPacientes">
                            <?php if (empty($mascotas)): ?>
                            <div class="text-center text-muted small p-4">
                                No hay mascotas registradas.<br>
                                <a href="<?= BASE_URL ?>/mascotas">Registrar en Mascotas</a>
                            </div>
                            <?php endif; ?>
                            <?php foreach ($mascotas as $m): ?>
                            <a href="<?= BASE_URL ?>/historial?mascota=<?= $m['id'] ?>"
                               class="paciente-item <?= $mascota && (int)$mascota['id'] === (int)$m['id'] ? 'activo' : '' ?>">
                                <span class="paciente-ico"><?= $iconos[$m['especie']] ?? '🐾' ?></span>
                                <span class="flex-grow-1 min-w-0">
                                    <span class="d-block fw-semibold text-truncate"><?= htmlspecialchars($m['nombre']) ?></span>
                                    <span class="d-block text-muted small text-truncate"><?= htmlspecialchars($m['cliente_nombre'] ?: 'Sin dueño') ?></span>
                                </span>
                                <span class="text-end flex-shrink-0">
                                    <?php if ($m['total_registros'] > 0): ?>
                                    <span class="d-block small fw-semibold"><?= (int)$m['total_registros'] ?> <i class="bi bi-journal-medical text-muted"></i></span>
                                    <span class="d-block text-muted" style="font-size:.68rem"><?= $fechaCorta($m['ultima_visita']) ?></span>
                                    <?php else: ?>
                                    <span class="text-muted" style="font-size:.7rem">Sin registros</span>
                                    <?php endif; ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-3 pb-1">
                            <h6 class="card-title mb-0 fw-semibold"><i class="bi bi-bell-fill text-warning me-1"></i>Próximos controles</h6>
                        </div>
                        <div class="card-body pt-2">
                            <?php if (empty($proximos)): ?>
                            <div class="text-muted small">No hay controles ni refuerzos programados.</div>
                            <?php endif; ?>
                            <?php foreach ($proximos as $p):
                                $dias = (new DateTime($hoy))->diff(new DateTime($p['proximo_control']))->days; ?>
                            <a href="<?= BASE_URL ?>/historial?mascota=<?= $p['mascota_id'] ?>" class="proximo-item">
                                <span class="proximo-ico" style="--c:<?= $tipos[$p['tipo']]['color'] ?>"><i class="bi bi-<?= $tipos[$p['tipo']]['icon'] ?>"></i></span>
                                <span class="flex-grow-1 min-w-0">
                                    <span class="d-block fw-semibold small text-truncate"><?= htmlspecialchars($p['mascota_nombre']) ?></span>
                                    <span class="d-block text-muted text-truncate" style="font-size:.72rem">
                                        <?= $tipos[$p['tipo']]['label'] ?><?= $p['producto_aplicado'] ? ' · ' . htmlspecialchars($p['producto_aplicado']) : '' ?>
                                    </span>
                                </span>
                                <span class="badge <?= $dias <= 7 ? 'bg-warning-soft text-warning' : 'bg-secondary-soft text-secondary' ?>">
                                    <?= $dias === 0 ? 'Hoy' : ($dias === 1 ? 'Mañana' : "en $dias días") ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- ── Columna derecha: ficha + historia ──────── -->
                <div class="col-lg-8 col-xxl-9">
                    <?php if (!$mascota): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="historial-vacio-ico mb-3"><i class="bi bi-clipboard2-pulse"></i></div>
                            <h5 class="fw-bold">Selecciona un paciente</h5>
                            <p class="text-muted mb-0">
                                Elige una mascota de la lista para ver su historia clínica<br>y registrar consultas, vacunas, desparasitaciones y más.
                            </p>
                        </div>
                    </div>
                    <?php else: ?>

                    <!-- Ficha del paciente -->
                    <div class="card border-0 shadow-sm mb-3 ficha">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-3 align-items-start">
                                <div class="ficha-avatar"><?= $iconos[$mascota['especie']] ?? '🐾' ?></div>
                                <div class="flex-grow-1 min-w-0">
                                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($mascota['nombre']) ?></h4>
                                    <div class="ficha-datos">
                                        <span><i class="bi bi-tag"></i><?= htmlspecialchars($mascota['especie']) ?><?= $mascota['raza'] ? ' · ' . htmlspecialchars($mascota['raza']) : '' ?></span>
                                        <?php if ($mascota['sexo']): ?>
                                        <span><i class="bi bi-gender-<?= $mascota['sexo'] === 'macho' ? 'male' : 'female' ?>"></i><?= ucfirst($mascota['sexo']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($mascota['fecha_nacimiento']): ?>
                                        <span><i class="bi bi-cake2"></i><?= $edad($mascota['fecha_nacimiento']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="bi bi-person"></i><?= htmlspecialchars($mascota['cliente_nombre'] ?: 'Sin dueño') ?></span>
                                        <?php if ($mascota['cliente_telefono']): ?>
                                        <a href="tel:<?= htmlspecialchars($mascota['cliente_telefono']) ?>"><i class="bi bi-telephone"></i><?= htmlspecialchars($mascota['cliente_telefono']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($mascota['notas']): ?>
                                    <div class="ficha-alerta mt-2"><i class="bi bi-exclamation-diamond-fill me-1"></i><?= htmlspecialchars($mascota['notas']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="<?= BASE_URL ?>/historial/imprimir?mascota=<?= $mascota['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-printer me-1"></i>Imprimir historia
                                    </a>                                    <?php if (Permisos::puede('formulas')): ?>
                                    <a href="<?= BASE_URL ?>/formulas/nueva?mascota=<?= $mascota['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-prescription2 me-1"></i>Fórmula médica
                                    </a>
                                    <a href="<?= BASE_URL ?>/formulas?mascota=<?= $mascota['id'] ?>" class="btn btn-sm btn-light">
                                        <i class="bi bi-files me-1"></i>Fórmulas anteriores
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="abrirAdjuntar()" title="Adjuntar un PDF con su descripción">
                                        <i class="bi bi-paperclip me-1"></i>Adjuntar PDF
                                    </button>
                                    <a href="<?= BASE_URL ?>/citas?nueva=1" class="btn btn-sm btn-light">
                                        <i class="bi bi-calendar-plus me-1"></i>Agendar cita
                                    </a>
                                </div>
                            </div>

                            <div class="ficha-resumen">
                                <div>
                                    <div class="ficha-resumen-label">Registros</div>
                                    <div class="ficha-resumen-valor"><?= count($registros) ?></div>
                                </div>
                                <div>
                                    <div class="ficha-resumen-label">Última visita</div>
                                    <div class="ficha-resumen-valor"><?= $registros ? $fechaCorta($registros[0]['fecha']) : '—' ?></div>
                                </div>
                                <div>
                                    <div class="ficha-resumen-label">Último peso</div>
                                    <div class="ficha-resumen-valor"><?= $ultimoPeso ? rtrim(rtrim(number_format($ultimoPeso['peso'], 2), '0'), '.') . ' kg' : '—' ?></div>
                                </div>
                                <?php if ($sparkline): ?>
                                <div class="ficha-sparkline">
                                    <div class="ficha-resumen-label">Evolución del peso</div>
                                    <?= $sparkline ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos adjuntos generales (no ligados a un registro) -->
                    <?php if (!empty($adjuntosGenerales)): ?>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body py-3">
                            <div class="fw-semibold mb-2"><i class="bi bi-paperclip me-1"></i>Documentos adjuntos
                                <span class="badge bg-danger-subtle text-danger-emphasis ms-1"><?= count($adjuntosGenerales) ?></span></div>
                            <?php foreach ($adjuntosGenerales as $a): ?>
                            <div class="d-flex align-items-center gap-2 py-1 border-top">
                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
                                <div class="flex-grow-1 min-w-0">
                                    <a href="<?= BASE_URL ?>/historial/adjunto?id=<?= (int)$a['id'] ?>" target="_blank" class="fw-semibold text-decoration-none"><?= htmlspecialchars($a['descripcion'] ?: $a['nombre']) ?></a>
                                    <div class="small text-muted"><?= htmlspecialchars($a['nombre']) ?> · <?= $fechaCorta(substr($a['created_at'], 0, 10)) ?><?= $a['usuario_nombre'] ? ' · ' . htmlspecialchars($a['usuario_nombre']) : '' ?></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-accion" title="Quitar adjunto"
                                        onclick='quitarAdjunto(<?= (int)$a['id'] ?>, <?= json_encode($a['nombre'], $jsonFlags) ?>)'><i class="bi bi-trash-fill"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Filtro por tipo -->
                    <?php if ($registros): ?>
                    <div class="tipo-filtros mb-3" id="filtrosTipo">
                        <button class="tipo-filtro activo" data-tipo="">Todos <span><?= count($registros) ?></span></button>
                        <?php
                        $conteo = array_count_values(array_column($registros, 'tipo'));
                        foreach ($tipos as $k => $t): if (empty($conteo[$k])) continue; ?>
                        <button class="tipo-filtro" data-tipo="<?= $k ?>" style="--c:<?= $t['color'] ?>">
                            <i class="bi bi-<?= $t['icon'] ?>"></i><?= $t['label'] ?> <span><?= $conteo[$k] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Línea de tiempo -->
                    <div class="timeline">
                        <?php if (empty($registros)): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center py-5 text-muted">
                                <i class="bi bi-journal-medical fs-1 d-block mb-2 opacity-25"></i>
                                <?= htmlspecialchars($mascota['nombre']) ?> aún no tiene registros.
                                <div class="mt-2"><button class="btn btn-sm btn-primary-custom" onclick="nuevoRegistro()"><i class="bi bi-journal-plus me-1"></i>Crear el primer registro</button></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php foreach ($registros as $r): $t = $tipos[$r['tipo']]; ?>
                        <div class="tl-item" data-tipo="<?= $r['tipo'] ?>" style="--c:<?= $t['color'] ?>">
                            <div class="tl-marker"><i class="bi bi-<?= $t['icon'] ?>"></i></div>
                            <div class="tl-card card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <div class="min-w-0">
                                            <span class="tl-tipo"><?= $t['label'] ?></span>
                                            <h6 class="fw-bold mb-0 mt-1"><?= htmlspecialchars($r['motivo']) ?></h6>
                                            <div class="text-muted small mt-1">
                                                <i class="bi bi-calendar3 me-1"></i><?= $fechaCorta($r['fecha']) ?>
                                                <?php if ($r['veterinario_nombre']): ?> · <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($r['veterinario_nombre']) ?><?php endif; ?>
                                                <?php if ($r['veterinaria_nombre']): ?> · <i class="bi bi-building me-1"></i><?= htmlspecialchars(ucwords(mb_strtolower($r['veterinaria_nombre']))) ?><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 align-items-start">
                                            <a href="<?= BASE_URL ?>/historial/formato?id=<?= (int)$r['id'] ?>" target="_blank"
                                               class="btn btn-sm btn-outline-secondary btn-accion" title="Imprimir formato de historia clínica (incluye los PDF adjuntos)"><i class="bi bi-printer"></i></a>
                                            <button class="btn btn-sm btn-outline-primary btn-accion" title="Editar"
                                                    onclick='editarRegistro(<?= json_encode($r, $jsonFlags) ?>)'><i class="bi bi-pencil-fill"></i></button>
                                            <?php if ($esAdmin): ?>
                                            <button class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar"
                                                    onclick="eliminarRegistro(<?= (int)$r['id'] ?>)"><i class="bi bi-trash-fill"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php
                                    $vitales = array_filter([
                                        ['Peso', $r['peso'] !== null ? rtrim(rtrim($r['peso'], '0'), '.') . ' kg' : null, false],
                                        ['T°', $r['temperatura'] !== null ? $r['temperatura'] . ' °C' : null, $fueraDeRango($r, 'temperatura')],
                                        ['FC', $r['frecuencia_cardiaca'] !== null ? $r['frecuencia_cardiaca'] . ' lpm' : null, $fueraDeRango($r, 'frecuencia_cardiaca')],
                                        ['FR', $r['frecuencia_respiratoria'] !== null ? $r['frecuencia_respiratoria'] . ' rpm' : null, $fueraDeRango($r, 'frecuencia_respiratoria')],
                                        ['Mucosas', $r['mucosas'], $r['mucosas'] && $r['mucosas'] !== 'Rosadas'],
                                        ['TLLC', $r['tllc'], $r['tllc'] === '> 2 s'],
                                        ['Deshidratación', $r['hidratacion'], $r['hidratacion'] && $r['hidratacion'] !== 'Normal'],
                                        ['CC', $r['condicion_corporal'] !== null ? $r['condicion_corporal'] . '/9' : null, $r['condicion_corporal'] !== null && ($r['condicion_corporal'] < 4 || $r['condicion_corporal'] > 5)],
                                    ], fn($v) => $v[1] !== null);
                                    ?>
                                    <?php if ($vitales): ?>
                                    <div class="vitales mt-2">
                                        <?php foreach ($vitales as [$lbl, $val, $alerta]): ?>
                                        <span class="vital <?= $alerta ? 'alerta' : '' ?>" <?= $alerta ? 'title="Fuera del rango normal"' : '' ?>>
                                            <span class="vital-lbl"><?= $lbl ?></span><?= htmlspecialchars($val) ?><?= $alerta ? ' <i class="bi bi-exclamation-circle-fill"></i>' : '' ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php
                                    $secciones = array_filter([
                                        ['S', 'Anamnesis',            $r['anamnesis']],
                                        ['O', 'Examen físico',        $r['examen_fisico']],
                                        ['A', 'Diagnóstico',          $r['diagnostico']],
                                        ['P', 'Tratamiento / plan',   $r['tratamiento']],
                                        ['',  'Observaciones',        $r['observaciones']],
                                    ], fn($s) => $s[2] !== null && $s[2] !== '');
                                    ?>
                                    <?php if ($secciones): ?>
                                    <div class="soap mt-3">
                                        <?php foreach ($secciones as [$letra, $titulo, $texto]): ?>
                                        <div class="soap-fila">
                                            <span class="soap-letra <?= $letra ? '' : 'vacia' ?>"><?= $letra ?: '<i class="bi bi-chat-left-text"></i>' ?></span>
                                            <div>
                                                <div class="soap-titulo"><?= $titulo ?></div>
                                                <div class="soap-texto"><?= nl2br(htmlspecialchars($texto)) ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($r['producto_aplicado'] || $r['proximo_control']): ?>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <?php if ($r['producto_aplicado']): ?>
                                        <span class="badge bg-success-soft text-success fw-semibold">
                                            <i class="bi bi-capsule me-1"></i><?= htmlspecialchars($r['producto_aplicado']) ?><?= $r['lote'] ? ' · Lote ' . htmlspecialchars($r['lote']) : '' ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($r['proximo_control']): $vencido = $r['proximo_control'] < $hoy; ?>
                                        <span class="badge <?= $vencido ? 'bg-danger-soft text-danger' : 'bg-warning-soft text-warning' ?> fw-semibold">
                                            <i class="bi bi-bell me-1"></i><?= $vencido ? 'Control pendiente desde' : 'Próximo control:' ?> <?= $fechaCorta($r['proximo_control']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($r['adjuntos'])): ?>
                                    <div class="d-flex flex-wrap gap-2 mt-3 align-items-center">
                                        <span class="small text-muted"><i class="bi bi-paperclip me-1"></i>Adjuntos:</span>
                                        <?php foreach ($r['adjuntos'] as $a): ?>
                                        <span class="badge bg-danger-subtle text-danger-emphasis fw-semibold d-inline-flex align-items-center gap-1" style="font-size:.75rem">
                                            <a href="<?= BASE_URL ?>/historial/adjunto?id=<?= (int)$a['id'] ?>" target="_blank" class="text-decoration-none text-reset"
                                               title="<?= htmlspecialchars($a['descripcion'] ? $a['descripcion'] . ' — ' . $a['nombre'] : 'Abrir PDF') ?>">
                                                <i class="bi bi-file-earmark-pdf-fill me-1"></i><?= htmlspecialchars($a['descripcion'] ?: $a['nombre']) ?>
                                            </a>
                                            <button type="button" class="btn btn-link p-0 text-danger lh-1" title="Quitar adjunto"
                                                    onclick='quitarAdjunto(<?= (int)$a['id'] ?>, <?= json_encode($a['nombre'], $jsonFlags) ?>)'><i class="bi bi-x-circle-fill"></i></button>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php if ($mascota): ?>
<!-- ── MODAL: PLANTILLA SOAP ─────────────────────────── -->
<div class="modal fade" id="modalRegistro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/historial/guardar" id="formRegistro" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="id" id="h-id" value="0">
                <input type="hidden" name="mascota_id" value="<?= $mascota['id'] ?>">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-journal-medical text-primary me-2"></i><span id="registroTitulo">Nuevo registro clínico</span>
                        </h5>
                        <div class="text-muted small">
                            Formato de historia clínica de pequeños animales ·
                            <?= $iconos[$mascota['especie']] ?? '🐾' ?> <?= htmlspecialchars($mascota['nombre']) ?> · <?= htmlspecialchars($mascota['especie']) ?>
                            <?= $mascota['fecha_nacimiento'] ? ' · ' . $edad($mascota['fecha_nacimiento']) : '' ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Tipo -->
                    <div class="tipo-selector mb-3">
                        <?php foreach ($tipos as $k => $t): ?>
                        <input type="radio" class="btn-check" name="tipo" id="tipo-<?= $k ?>" value="<?= $k ?>" <?= $k === 'consulta' ? 'checked' : '' ?>>
                        <label class="tipo-opcion" for="tipo-<?= $k ?>" style="--c:<?= $t['color'] ?>">
                            <i class="bi bi-<?= $t['icon'] ?>"></i><?= $t['label'] ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    // ── Formato de historia clínica de pequeños animales ──
                    $grupoVac = $mascota['especie'] === 'Gato' ? 'felino' : 'canino';
                    $radios = function (string $name, array $opciones, string $extraClass = '') {
                        foreach ($opciones as $o) {
                            $id = 'r' . substr(md5($name . $o), 0, 8);
                            echo '<div class="form-check form-check-inline ' . $extraClass . '"><input class="form-check-input" type="radio" name="' . $name . '" id="' . $id . '" value="' . htmlspecialchars($o) . '">'
                               . '<label class="form-check-label" for="' . $id . '">' . htmlspecialchars($o) . '</label></div>';
                        }
                    };
                    $check = function (string $name, string $label) {
                        $id = 'c' . substr(md5($name), 0, 8);
                        echo '<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="' . $name . '" id="' . $id . '" value="1">'
                           . '<label class="form-check-label" for="' . $id . '">' . htmlspecialchars($label) . '</label></div>';
                    };
                    ?>

                    <!-- Admisión -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Admisión</div>
                        <div class="row g-2">
                            <div class="col-6 col-md-2">
                                <label class="form-label">Fecha de admisión <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="fecha" id="h-fecha" max="<?= $hoy ?>" required>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Hora</label>
                                <input type="time" class="form-control form-control-sm" name="f[admision][hora]">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">H.C.</label>
                                <input type="text" class="form-control form-control-sm" value="HC-<?= str_pad((string)$mascota['id'], 5, '0', STR_PAD_LEFT) ?>" readonly tabindex="-1">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Médico veterinario</label>
                                <select class="form-select form-select-sm" name="veterinario_id" id="h-veterinario_id">
                                    <option value="0">—</option>
                                    <?php foreach ($veterinarios as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label">T.P.</label>
                                <input type="text" class="form-control form-control-sm" name="f[admision][tp]" maxlength="40">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Chip</label>
                                <input type="text" class="form-control form-control-sm" name="f[admision][chip]" maxlength="40">
                            </div>
                        </div>
                    </div>

                    <!-- Reseña del paciente -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Reseña del paciente</div>
                        <div class="row g-2">
                            <div class="col-6 col-md-3"><label class="form-label">Nombre</label><input class="form-control form-control-sm" value="<?= htmlspecialchars($mascota['nombre']) ?>" readonly tabindex="-1"></div>
                            <div class="col-6 col-md-2"><label class="form-label">Especie</label><input class="form-control form-control-sm" value="<?= htmlspecialchars($mascota['especie']) ?>" readonly tabindex="-1"></div>
                            <div class="col-6 col-md-3"><label class="form-label">Raza</label><input class="form-control form-control-sm" value="<?= htmlspecialchars((string)$mascota['raza']) ?>" readonly tabindex="-1"></div>
                            <div class="col-6 col-md-2"><label class="form-label">Sexo</label><input class="form-control form-control-sm" value="<?= $mascota['sexo'] ? ($mascota['sexo'] === 'macho' ? 'Macho' : 'Hembra') : '' ?>" readonly tabindex="-1"></div>
                            <div class="col-6 col-md-2"><label class="form-label">Edad</label><input class="form-control form-control-sm" value="<?= $mascota['fecha_nacimiento'] ? $edad($mascota['fecha_nacimiento']) : '' ?>" readonly tabindex="-1"></div>
                            <div class="col-6 col-md-3"><label class="form-label">Color</label><input type="text" class="form-control form-control-sm" name="f[resena][color]" maxlength="60"></div>
                            <div class="col-12 col-md-6"><label class="form-label">Señas particulares</label><input type="text" class="form-control form-control-sm" name="f[resena][senas]" maxlength="200"></div>
                            <div class="col-12 col-md-3">
                                <label class="form-label d-block">Procedencia</label>
                                <?php $radios('f[resena][procedencia]', ['Urbana', 'Rural']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del propietario -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Datos del propietario</div>
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label">Nombre</label><input class="form-control form-control-sm" value="<?= htmlspecialchars((string)$mascota['cliente_nombre']) ?>" readonly tabindex="-1"></div>
                            <div class="col-6 col-md-2"><label class="form-label">Identificación</label><input class="form-control form-control-sm" value="<?= htmlspecialchars((string)($mascota['cliente_dni'] ?? '')) ?>" readonly tabindex="-1"></div>
                            <div class="col-6 col-md-2"><label class="form-label">Teléfono</label><input class="form-control form-control-sm" value="<?= htmlspecialchars((string)$mascota['cliente_telefono']) ?>" readonly tabindex="-1"></div>
                            <div class="col-md-4"><label class="form-label">Dirección</label><input class="form-control form-control-sm" value="<?= htmlspecialchars((string)($mascota['cliente_direccion'] ?? '')) ?>" readonly tabindex="-1"></div>
                            <div class="col-4 col-md-2"><label class="form-label">Estrato</label><input type="text" class="form-control form-control-sm" name="f[propietario][estrato]" maxlength="10"></div>
                            <div class="col-8 col-md-4"><label class="form-label">Municipio</label><input type="text" class="form-control form-control-sm" name="f[propietario][municipio]" maxlength="80"></div>
                            <div class="col-md-6"><label class="form-label">Ocupación</label><input type="text" class="form-control form-control-sm" name="f[propietario][ocupacion]" maxlength="80"></div>
                        </div>
                        <?php if (!$mascota['cliente_nombre']): ?><div class="form-text">Esta mascota no tiene propietario registrado; se asigna en Mascotas.</div><?php endif; ?>
                    </div>

                    <!-- Motivo y anamnésicos -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Motivo de la consulta</div>
                        <textarea class="form-control" name="motivo" id="h-motivo" rows="3" maxlength="200" required style="font-size:1rem; resize:vertical"></textarea>
                        <div class="sugerencias mt-1" id="sugerenciasMotivo"></div>
                        <label class="form-label mt-2">Anamnésicos</label>
                        <textarea class="form-control" name="anamnesis" id="h-anamnesis" rows="6" style="font-size:1rem; resize:vertical"
                                  placeholder="Inicio y evolución de los síntomas, apetito, vómitos, diarrea, actividad…"></textarea>
                    </div>

                    <!-- Historia del paciente -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Historia del paciente <small>se trae de la consulta anterior</small></div>
                        <div class="hc-fila">
                            <div class="hc-lbl">Vacunación <small><?= $grupoVac === 'felino' ? 'Felinos' : 'Caninos' ?></small></div>
                            <div class="flex-grow-1">
                                <?php $check('f[vacunacion][no]', 'No vacunado'); ?>
                                <div class="row g-2 mt-1">
                                    <?php foreach ($vacunas[$grupoVac] as $k => $lbl): ?>
                                    <div class="col-md-6 d-flex align-items-center gap-2">
                                        <div style="min-width:80px;"><?php $check("f[vacunacion][$k][si]", $lbl); ?></div>
                                        <input type="date" class="form-control form-control-sm" name="f[vacunacion][<?= $k ?>][fecha]" title="Fecha" style="max-width:160px;">
                                        <?php if ($k === 'otra'): ?><input type="text" class="form-control form-control-sm" name="f[vacunacion][otra][cual]" placeholder="¿Cuál?" maxlength="80"><?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="hc-fila">
                            <div class="hc-lbl">Última desparasitación</div>
                            <div class="flex-grow-1 d-flex flex-wrap align-items-center gap-2">
                                <?php $radios('f[desparasitacion][estado]', ['Sí', 'No']); ?>
                                <input type="text" class="form-control form-control-sm" name="f[desparasitacion][producto]" placeholder="Producto" maxlength="100" style="max-width:240px;">
                                <input type="date" class="form-control form-control-sm" name="f[desparasitacion][fecha]" title="Fecha" style="max-width:160px;">
                            </div>
                        </div>
                        <div class="hc-fila">
                            <div class="hc-lbl">Alimentación</div>
                            <div class="flex-grow-1 d-flex flex-wrap align-items-center gap-1">
                                <?php $radios('f[alimentacion][tipo]', $alimentaciones); ?>
                                <input type="text" class="form-control form-control-sm" name="f[alimentacion][otra]" placeholder="¿Cuál?" maxlength="100" style="max-width:220px;">
                            </div>
                        </div>
                        <div class="hc-fila">
                            <div class="hc-lbl">Estado reproductivo</div>
                            <div class="flex-grow-1">
                                <?php $radios('f[reproductivo][estado]', ['Castrado', 'Entero']); ?>
                                <?php $check('f[reproductivo][gestacion]', 'Gestación'); ?>
                                <?php $check('f[reproductivo][lactancia]', 'Lactancia'); ?>
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-6"><label class="form-label">Alergias</label><input type="text" class="form-control form-control-sm" name="f[alergias]" maxlength="300"></div>
                            <div class="col-md-6"><label class="form-label">Enfermedades anteriores</label><input type="text" class="form-control form-control-sm" name="f[enfermedades]" maxlength="300"></div>
                            <div class="col-md-6"><label class="form-label">Cirugías</label><input type="text" class="form-control form-control-sm" name="f[cirugias]" maxlength="300"></div>
                            <div class="col-md-6"><label class="form-label">Antecedentes familiares</label><input type="text" class="form-control form-control-sm" name="f[antecedentes]" maxlength="300"></div>
                        </div>
                        <div class="hc-fila mt-2">
                            <div class="hc-lbl">Hábitat</div>
                            <div class="flex-grow-1 d-flex flex-wrap align-items-center gap-1">
                                <?php $radios('f[habitat][tipo]', $habitats); ?>
                                <input type="text" class="form-control form-control-sm" name="f[habitat][otro]" placeholder="¿Cuál?" maxlength="100" style="max-width:220px;">
                            </div>
                        </div>
                    </div>

                    <!-- Constantes fisiológicas -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">
                            Constantes fisiológicas
                            <?php if ($rango): ?><span class="ms-auto rango-ayuda"><i class="bi bi-info-circle me-1"></i>Rangos normales: <?= htmlspecialchars($mascota['especie']) ?></span><?php endif; ?>
                        </div>
                        <div class="row g-2">
                            <div class="col-6 col-md-2">
                                <label class="form-label">T.Ll.C.</label>
                                <select class="form-select form-select-sm" name="tllc" id="h-tllc" title="Tiempo de llenado capilar">
                                    <option value="">—</option>
                                    <?php foreach ($tllcOpciones as $o): ?><option><?= $o ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">F.C. (lpm)</label>
                                <input type="number" class="form-control form-control-sm vital-input" name="frecuencia_cardiaca" id="h-frecuencia_cardiaca" step="1" min="10" max="400" data-rango="frecuencia_cardiaca">
                                <div class="vital-hint" data-hint="frecuencia_cardiaca"></div>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">F.R. (rpm)</label>
                                <input type="number" class="form-control form-control-sm vital-input" name="frecuencia_respiratoria" id="h-frecuencia_respiratoria" step="1" min="2" max="200" data-rango="frecuencia_respiratoria">
                                <div class="vital-hint" data-hint="frecuencia_respiratoria"></div>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Pulso</label>
                                <input type="text" class="form-control form-control-sm" name="f[constantes][pulso]" maxlength="40" placeholder="Ej: 110, fuerte">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Temperatura (°C)</label>
                                <input type="number" class="form-control form-control-sm vital-input" name="temperatura" id="h-temperatura" step="0.1" min="30" max="45" data-rango="temperatura">
                                <div class="vital-hint" data-hint="temperatura"></div>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Peso (kg)</label>
                                <input type="number" class="form-control form-control-sm" name="peso" id="h-peso" step="0.01" min="0.01" max="200">
                                <?php if ($ultimoPeso): ?><div class="vital-hint">Último: <?= rtrim(rtrim(number_format($ultimoPeso['peso'], 2), '0'), '.') ?> kg</div><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Examen clínico -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Examen clínico</div>
                        <div class="hc-fila"><div class="hc-lbl">Actitud</div><div><?php foreach ($actitudes as $o) $check('f[examen][actitud][' . $o . ']', $o); ?></div></div>
                        <div class="hc-fila"><div class="hc-lbl">Condición corporal</div><div><?php $radios('f[examen][condicion]', $condiciones); ?></div></div>
                        <div class="hc-fila"><div class="hc-lbl">Estado de hidratación</div><div><?php $radios('hidratacion', $hidratacion); ?></div></div>

                        <table class="table table-sm table-bordered align-middle mt-2 mb-0 hc-tabla">
                            <thead><tr><th style="width:190px;">Mucosas / sistema</th><th class="text-center" style="width:44px;" title="Normal">N</th><th class="text-center" style="width:44px;" title="Anormal">A</th><th>Observaciones</th></tr></thead>
                            <tbody>
                                <?php foreach (['mucosas' => $zonasMucosas, 'sistemas' => $sistemas] as $grupo => $items): ?>
                                <?php foreach ($items as $k => $lbl): ?>
                                <tr class="<?= $grupo === 'sistemas' ? 'hc-sistema' : '' ?>">
                                    <td class="fw-semibold small"><?= $grupo === 'mucosas' ? 'Mucosa ' . mb_strtolower($lbl) : $lbl ?></td>
                                    <td class="text-center"><input class="form-check-input" type="radio" name="f[<?= $grupo ?>][<?= $k ?>][estado]" value="N" title="Normal"></td>
                                    <td class="text-center"><input class="form-check-input hc-anormal" type="radio" name="f[<?= $grupo ?>][<?= $k ?>][estado]" value="A" title="Anormal"></td>
                                    <td><input type="text" class="form-control form-control-sm border-0" name="f[<?= $grupo ?>][<?= $k ?>][obs]" maxlength="300"></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-end mt-1">
                            <button type="button" class="btn btn-xs" id="btnTodoNormal"><i class="bi bi-check2-all me-1"></i>Marcar sin marcar como normal</button>
                        </div>
                        <label class="form-label mt-2">Otros hallazgos del examen</label>
                        <textarea class="form-control form-control-sm" name="examen_fisico" id="h-examen_fisico" rows="2"></textarea>
                    </div>

                    <!-- Lista de problemas -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Lista de problemas</div>
                        <table class="table table-sm table-bordered mb-1 hc-tabla" id="tablaProblemas">
                            <thead><tr><th>Lista de problemas</th><th>Lista maestra</th><th>Diagnóstico diferencial (DAMNVIT)</th></tr></thead>
                            <tbody></tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <div class="form-text m-0">D: Degenerativa · A: Anomalía congénita · M: Metabólica · N: Nutricional y neoplásica · V: Vascular · I: Infecciosa, inflamatoria o idiopática · T: Trauma</div>
                            <button type="button" class="btn btn-xs" onclick="agregarFila('problemas')"><i class="bi bi-plus-lg me-1"></i>Fila</button>
                        </div>
                    </div>

                    <!-- Plan diagnóstico -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Plan diagnóstico</div>
                        <table class="table table-sm table-bordered mb-1 hc-tabla" id="tablaPlan">
                            <thead><tr><th>Examen</th><th class="text-center" style="width:86px;">Autorizado</th><th class="text-center" style="width:106px;">No autorizado</th><th style="width:150px;">Fecha</th><th>Laboratorio</th><th>Resultados</th></tr></thead>
                            <tbody></tbody>
                        </table>
                        <div class="text-end"><button type="button" class="btn btn-xs" onclick="agregarFila('plan')"><i class="bi bi-plus-lg me-1"></i>Fila</button></div>
                    </div>

                    <!-- Diagnóstico y tratamiento -->
                    <div class="hc-sec">
                        <div class="hc-sec-titulo">Diagnóstico y tratamiento</div>
                        <label class="form-label">Diagnóstico</label>
                        <textarea class="form-control form-control-sm" name="diagnostico" id="h-diagnostico" rows="2" placeholder="Diagnóstico presuntivo o definitivo"></textarea>
                        <label class="form-label mt-2">Tratamiento / plan terapéutico</label>
                        <textarea class="form-control form-control-sm mb-2" name="tratamiento" id="h-tratamiento" rows="2"
                                  placeholder="Medicamento, dosis, vía, frecuencia y duración. Indicaciones para el dueño…"></textarea>
                        <div class="row g-2">
                            <div class="col-md-5 campo-producto">
                                <label class="form-label" id="lblProducto">Producto aplicado</label>
                                <input type="text" class="form-control form-control-sm" name="producto_aplicado" id="h-producto_aplicado" maxlength="150">
                            </div>
                            <div class="col-md-3 campo-producto">
                                <label class="form-label">Lote</label>
                                <input type="text" class="form-control form-control-sm" name="lote" id="h-lote" maxlength="60">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Próximo control / refuerzo</label>
                                <input type="date" class="form-control form-control-sm" name="proximo_control" id="h-proximo_control">
                                <div class="d-flex gap-1 mt-1 flex-wrap" id="atajosControl">
                                    <button type="button" class="btn btn-xs" data-dias="15">+15 d</button>
                                    <button type="button" class="btn btn-xs" data-dias="30">+1 mes</button>
                                    <button type="button" class="btn btn-xs" data-dias="90">+3 meses</button>
                                    <button type="button" class="btn btn-xs" data-dias="365">+1 año</button>
                                </div>
                            </div>
                        </div>
                        <label class="form-label mt-2">Observaciones</label>
                        <textarea class="form-control form-control-sm" name="observaciones" id="h-observaciones" rows="2" placeholder="Notas internas, comportamiento, recomendaciones…"></textarea>

                        <div id="h-adjuntos-existentes" class="mt-3"></div>
                        <label class="form-label mt-3" for="h-adjuntos"><i class="bi bi-paperclip me-1"></i>Adjuntar PDF <span class="text-muted fw-normal">(resultados, radiografías, informes…)</span></label>
                        <input type="file" class="form-control form-control-sm" name="adjuntos[]" id="h-adjuntos" accept="application/pdf,.pdf" multiple>
                        <input type="text" class="form-control form-control-sm mt-2" name="adjuntos_descripcion" maxlength="255" placeholder="Descripción de los adjuntos (ej: Hemograma del 22/10)">
                        <div id="h-adjuntos-lista" class="mt-2"></div>
                        <div class="form-text">Puedes elegir varios a la vez o ir añadiendo más: se suman a la lista. Hasta 10 PDF de 15 MB cada uno. Al imprimir la historia clínica se imprimen también los adjuntos. Los ya adjuntos se ven en la tarjeta del registro.</div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-save me-1"></i> <span id="registroBtnTexto">Guardar en el historial</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL: ADJUNTAR PDF A LA HISTORIA ─────────────── -->
<div class="modal fade" id="modalAdjuntar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/historial/adjuntar" enctype="multipart/form-data" autocomplete="off" id="formAdjuntar">
                <input type="hidden" name="mascota_id" value="<?= $mascota['id'] ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-paperclip text-danger me-2"></i>Adjuntar PDF a la historia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="a-archivos">Archivo PDF <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="adjuntos[]" id="a-archivos" accept="application/pdf,.pdf" multiple required>
                        <div id="a-archivos-lista" class="mt-2"></div>
                        <div class="form-text">Elige varios a la vez o añade más: se suman a la lista. Hasta 10 PDF de 15 MB cada uno.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="a-descripcion">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="descripcion" id="a-descripcion" rows="2" maxlength="255" required
                                  placeholder="Ej: Resultado de hemograma y perfil renal del 22/10"></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold" for="a-registro">Asociar a un registro <span class="text-muted fw-normal">(opcional)</span></label>
                        <select class="form-select" name="historial_id" id="a-registro">
                            <option value="0">General de la mascota (sin registro)</option>
                            <?php foreach ($registros as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= $fechaCorta($r['fecha']) ?> · <?= htmlspecialchars($tipos[$r['tipo']]['label']) ?> · <?= htmlspecialchars(mb_strimwidth($r['motivo'], 0, 50, '…')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Al imprimir la historia clínica se imprimen todos los adjuntos, con su descripción.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-paperclip me-1"></i>Adjuntar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/historial/adjunto/eliminar" id="formQuitarAdjunto" class="d-none">
    <input type="hidden" name="id">
    <input type="hidden" name="mascota_id" value="<?= $mascota['id'] ?>">
</form>
<form method="POST" action="<?= BASE_URL ?>/historial/eliminar" id="formEliminar" class="d-none">
    <input type="hidden" name="id">
    <input type="hidden" name="mascota_id" value="<?= $mascota['id'] ?>">
</form>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Buscador de pacientes
document.getElementById('buscarPaciente').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#listaPacientes .paciente-item').forEach(a => {
        a.style.display = a.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
document.querySelector('.paciente-item.activo')?.scrollIntoView({ block: 'nearest' });

<?php if ($mascota): ?>
const HOY      = '<?= $hoy ?>';
const RANGO    = <?= json_encode($rango, $jsonFlags) ?>;
const OLD      = <?= json_encode($old, $jsonFlags) ?>;
const USUARIO  = <?= (int)$usuario['id'] ?>;
const VET_IDS  = <?= json_encode(array_map('intval', array_column($veterinarios, 'id'))) ?>;
const CAMPOS   = ['fecha', 'veterinario_id', 'motivo', 'anamnesis', 'peso', 'temperatura', 'frecuencia_cardiaca',
                  'frecuencia_respiratoria', 'tllc', 'examen_fisico',
                  'diagnostico', 'tratamiento', 'producto_aplicado', 'lote', 'proximo_control', 'observaciones'];
// Formato de la última consulta: su historia, reseña, propietario y admisión se proponen en la nueva
const PREVIO = <?php
    $previo = null;
    foreach ($registros as $_r) { if (!empty($_r['formato'])) { $previo = json_decode($_r['formato'], true); break; } }
    $heredar = ['admision', 'resena', 'propietario', 'vacunacion', 'desparasitacion', 'alimentacion', 'reproductivo',
                'alergias', 'enfermedades', 'cirugias', 'antecedentes', 'habitat'];
    $previo = $previo ? array_intersect_key($previo, array_flip($heredar)) : null;
    if ($previo && isset($previo['admision'])) unset($previo['admision']['hora']);
    echo json_encode($previo, $jsonFlags);
?>;

// ── Formato: llenar y limpiar los campos f[...] ──
const FILAS = {
    problemas: { min: 4, campos: [['problema', 'text'], ['maestra', 'text'], ['diferencial', 'text']] },
    plan:      { min: 3, campos: [['examen', 'text'], ['autorizado', 'check'], ['no_autorizado', 'check'], ['fecha', 'date'], ['laboratorio', 'text'], ['resultados', 'text']] },
};
function agregarFila(tipo) {
    const tbody = document.querySelector(tipo === 'problemas' ? '#tablaProblemas tbody' : '#tablaPlan tbody');
    const i = tbody.rows.length, tr = tbody.insertRow();
    FILAS[tipo].campos.forEach(([c, t]) => {
        const td = tr.insertCell(), name = `f[${tipo}][${i}][${c}]`;
        td.innerHTML = t === 'check'
            ? `<div class="text-center"><input class="form-check-input" type="checkbox" name="${name}" value="1"></div>`
            : `<input type="${t}" class="form-control form-control-sm border-0" name="${name}" maxlength="300">`;
    });
}
function limpiarFormato() {
    document.querySelectorAll('#formRegistro [name^="f["]').forEach(el => {
        if (el.type === 'radio' || el.type === 'checkbox') el.checked = false; else el.value = '';
    });
    document.querySelectorAll('#formRegistro input[name="hidratacion"]').forEach(el => el.checked = false);
    Object.keys(FILAS).forEach(t => {
        const tbody = document.querySelector(t === 'problemas' ? '#tablaProblemas tbody' : '#tablaPlan tbody');
        tbody.innerHTML = '';
        for (let i = 0; i < FILAS[t].min; i++) agregarFila(t);
    });
}
function llenarFormato(obj, prefijo = 'f') {
    if (!obj || typeof obj !== 'object') return;
    Object.keys(FILAS).forEach(t => {
        const filas = prefijo === 'f' && obj[t] ? Object.keys(obj[t]).length : 0;
        const tbody = document.querySelector(t === 'problemas' ? '#tablaProblemas tbody' : '#tablaPlan tbody');
        while (tbody.rows.length < filas) agregarFila(t);
    });
    Object.entries(obj).forEach(([k, v]) => {
        const name = `${prefijo}[${k}]`;
        if (v && typeof v === 'object') { llenarFormato(v, name); return; }
        document.querySelectorAll(`#formRegistro [name="${CSS.escape(name)}"]`).forEach(el => {
            if (el.type === 'radio') el.checked = el.value === String(v);
            else if (el.type === 'checkbox') el.checked = !!v && v !== '0';
            else el.value = v;
        });
    });
}
// «Marcar como normal» las mucosas y sistemas que quedaron sin marcar
document.getElementById('btnTodoNormal')?.addEventListener('click', () => {
    document.querySelectorAll('#formRegistro .hc-tabla tbody tr').forEach(tr => {
        const radios = tr.querySelectorAll('input[type="radio"]');
        if (radios.length && ![...radios].some(r => r.checked)) radios[0].checked = true;
    });
});

// Sugerencias de motivo y ajustes por tipo de registro
const PLANTILLAS = {
    consulta:        { motivos: ['Decaimiento', 'Vómitos', 'Diarrea', 'Picazón / dermatitis', 'Cojera', 'Tos'], producto: null },
    control:         { motivos: ['Control post-tratamiento', 'Control post-quirúrgico', 'Retiro de puntos', 'Control de peso'], producto: null },
    vacunacion:      { motivos: ['Vacuna antirrábica', 'Vacuna séxtuple', 'Vacuna triple felina', 'Vacuna leucemia felina', 'Refuerzo anual'], producto: 'Vacuna aplicada', dias: 365 },
    desparasitacion: { motivos: ['Desparasitación interna', 'Desparasitación externa', 'Desparasitación interna y externa'], producto: 'Desparasitante', dias: 90 },
    cirugia:         { motivos: ['Esterilización', 'Castración', 'Profilaxis dental', 'Extracción de masa'], producto: null, dias: 10 },
    laboratorio:     { motivos: ['Hemograma', 'Bioquímica sanguínea', 'Urianálisis', 'Coproparasitológico', 'Raspado de piel'], producto: null },
    urgencia:        { motivos: ['Trauma', 'Intoxicación', 'Dificultad respiratoria', 'Convulsiones'], producto: null },
    otro:            { motivos: [], producto: null },
};

const $ = id => document.getElementById(id);
const modal = () => bootstrap.Modal.getOrCreateInstance($('modalRegistro'));
const tipoActual = () => document.querySelector('input[name="tipo"]:checked')?.value || 'consulta';

function sumarDias(fecha, dias) {
    const d = new Date(fecha + 'T12:00:00');
    d.setDate(d.getDate() + dias);
    return d.toISOString().slice(0, 10);
}

function aplicarTipo() {
    const p = PLANTILLAS[tipoActual()];
    $('sugerenciasMotivo').innerHTML = p.motivos.map(m => `<button type="button" class="sugerencia">${m}</button>`).join('');
    document.querySelectorAll('.campo-producto').forEach(el => el.style.display = p.producto ? '' : 'none');
    if (p.producto) $('lblProducto').textContent = p.producto;
}

document.querySelectorAll('input[name="tipo"]').forEach(r => r.addEventListener('change', () => {
    aplicarTipo();
    // Sugerir próximo control según el tipo (solo si está vacío)
    const p = PLANTILLAS[tipoActual()];
    if (p.dias && !$('h-proximo_control').value && $('h-fecha').value) {
        $('h-proximo_control').value = sumarDias($('h-fecha').value, p.dias);
    }
}));

$('sugerenciasMotivo').addEventListener('click', e => {
    const b = e.target.closest('.sugerencia');
    if (b) { $('h-motivo').value = b.textContent; $('h-motivo').focus(); }
});

$('atajosControl').addEventListener('click', e => {
    const b = e.target.closest('[data-dias]');
    if (b) $('h-proximo_control').value = sumarDias($('h-fecha').value || HOY, parseInt(b.dataset.dias, 10));
});

// Alerta en vivo de constantes fuera de rango
function revisarVital(input) {
    const campo = input.dataset.rango, hint = document.querySelector(`[data-hint="${campo}"]`);
    if (!RANGO || !RANGO[campo]) return;
    const [min, max] = RANGO[campo], v = parseFloat(input.value);
    const fuera = !isNaN(v) && (v < min || v > max);
    input.classList.toggle('is-invalid', fuera);
    hint.innerHTML = isNaN(v) ? `Normal: ${min}–${max}`
        : (fuera ? `<span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i> ${v < min ? 'Bajo' : 'Alto'} (normal ${min}–${max})</span>`
                 : `<span class="text-success"><i class="bi bi-check-circle-fill"></i> Normal</span>`);
}
document.querySelectorAll('.vital-input').forEach(i => i.addEventListener('input', () => revisarVital(i)));

function llenar(d, tipo) {
    $('h-id').value = d.id || 0;
    CAMPOS.forEach(c => { const el = $('h-' + c); if (el) el.value = d[c] ?? ''; });
    limpiarFormato();
    document.querySelectorAll('#formRegistro input[name="hidratacion"]').forEach(el => el.checked = el.value === (d.hidratacion || ''));
    let fmt = d.f || d.formato || null;
    if (typeof fmt === 'string') { try { fmt = JSON.parse(fmt); } catch (e) { fmt = null; } }
    llenarFormato(fmt);
    ($('tipo-' + tipo) || $('tipo-consulta')).checked = true;
    aplicarTipo();
    document.querySelectorAll('.vital-input').forEach(revisarVital);
    const editando = Number(d.id) > 0;
    $('registroTitulo').textContent   = editando ? 'Editar registro clínico' : 'Nuevo registro clínico';
    $('registroBtnTexto').textContent = editando ? 'Guardar cambios' : 'Guardar en el historial';
}

// Adjuntos ya cargados del registro que se edita: se pueden quitar desde aquí
function pintarAdjuntosExistentes(lista) {
    const h = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    $('h-adjuntos-existentes').innerHTML = lista && lista.length
        ? '<div class="small fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Adjuntos de este registro</div>' + lista.map(a => `
            <div class="d-flex align-items-center gap-2 small py-1 border-bottom">
                <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                <a href="<?= BASE_URL ?>/historial/adjunto?id=${a.id}" target="_blank" class="flex-grow-1 text-truncate text-decoration-none" title="Abrir PDF">${h(a.descripcion || a.nombre)}</a>
                <button type="button" class="btn btn-link btn-sm text-danger p-0 js-quitar" title="Quitar adjunto" data-id="${a.id}" data-nombre="${h(a.nombre)}"><i class="bi bi-trash-fill"></i></button>
            </div>`).join('')
        : '';
}
$('h-adjuntos-existentes').addEventListener('click', e => {
    const b = e.target.closest('.js-quitar');
    if (b) quitarAdjunto(b.dataset.id, b.dataset.nombre);
});

function nuevoRegistro() {
    pintarAdjuntosExistentes([]);
    archivosRegistro.limpiar();
    const ahora = new Date();
    const hora = String(ahora.getHours()).padStart(2, '0') + ':' + String(ahora.getMinutes()).padStart(2, '0');
    const fmt = JSON.parse(JSON.stringify(PREVIO || {}));
    fmt.admision = Object.assign({}, fmt.admision || {}, { hora });
    llenar({ fecha: HOY, veterinario_id: VET_IDS.includes(USUARIO) ? USUARIO : 0, f: fmt }, 'consulta');
    modal().show();
}

function editarRegistro(r) {
    pintarAdjuntosExistentes(r.adjuntos || []);
    archivosRegistro.limpiar();
    llenar(r, r.tipo);
    modal().show();
}

// Varios archivos: el selector nativo reemplaza la selección anterior; aquí cada elección se SUMA a la lista
// (se pueden quitar uno a uno) y se envían todos juntos. Devuelve {limpiar()} para vaciar la lista.
function acumularArchivos(input, lista, max = 10, maxBytes = 15 * 1024 * 1024) {
    let dt = new DataTransfer();
    const clave = f => f.name + '|' + f.size + '|' + f.lastModified;
    const h = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const pintar = () => {
        lista.innerHTML = [...dt.files].map((f, i) => `
            <div class="d-flex align-items-center gap-2 small py-1 border-bottom">
                <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                <span class="flex-grow-1 text-truncate" title="${h(f.name)}">${h(f.name)}</span>
                <span class="text-muted text-nowrap">${(f.size / 1048576).toFixed(1)} MB</span>
                <button type="button" class="btn btn-link btn-sm text-danger p-0" data-i="${i}" title="Quitar" aria-label="Quitar ${h(f.name)}"><i class="bi bi-x-circle-fill"></i></button>
            </div>`).join('');
    };
    input.addEventListener('change', () => {
        const avisos = [];
        for (const f of input.files) {
            if ([...dt.files].some(x => clave(x) === clave(f))) continue;   // ya estaba en la lista
            if (!/\.pdf$/i.test(f.name) && f.type !== 'application/pdf') avisos.push('«' + f.name + '» no es un PDF.');
            else if (f.size > maxBytes) avisos.push('«' + f.name + '» supera los 15 MB.');
            else if (dt.files.length >= max) avisos.push('Máximo ' + max + ' archivos: «' + f.name + '» no se agregó.');
            else dt.items.add(f);
        }
        input.files = dt.files;
        pintar();
        if (avisos.length) alert(avisos.join('\n'));
    });
    lista.addEventListener('click', e => {
        const b = e.target.closest('button[data-i]');
        if (!b) return;
        const nuevo = new DataTransfer();
        [...dt.files].forEach((f, i) => { if (i !== Number(b.dataset.i)) nuevo.items.add(f); });
        dt = nuevo;
        input.files = dt.files;
        pintar();
    });
    const limpiar = () => { dt = new DataTransfer(); input.files = dt.files; pintar(); };
    input.form?.addEventListener('reset', () => setTimeout(limpiar, 0));
    return { limpiar };
}
const archivosRegistro = acumularArchivos($('h-adjuntos'), $('h-adjuntos-lista'));
const archivosAdjuntar = acumularArchivos($('a-archivos'), $('a-archivos-lista'));

function abrirAdjuntar() {
    $('formAdjuntar').reset();
    bootstrap.Modal.getOrCreateInstance($('modalAdjuntar')).show();
}

function quitarAdjunto(id, nombre) {
    if (!confirm('¿Quitar el adjunto «' + nombre + '» del historial?')) return;
    const f = $('formQuitarAdjunto');
    f.id.value = id;
    f.submit();
}

function eliminarRegistro(id) {
    if (!confirm('¿Eliminar este registro del historial? Esta acción no se puede deshacer.')) return;
    const f = $('formEliminar');
    f.id.value = id;
    f.submit();
}

// Filtro por tipo en la línea de tiempo
document.getElementById('filtrosTipo')?.addEventListener('click', e => {
    const b = e.target.closest('.tipo-filtro');
    if (!b) return;
    document.querySelectorAll('.tipo-filtro').forEach(x => x.classList.toggle('activo', x === b));
    document.querySelectorAll('.tl-item').forEach(it => it.style.display = !b.dataset.tipo || it.dataset.tipo === b.dataset.tipo ? '' : 'none');
});

// Reabrir la plantilla si el servidor rechazó los datos
if (OLD) {
    llenar(OLD, OLD.tipo);
    modal().show();
}
<?php endif; ?>
</script>
</body>
</html>
