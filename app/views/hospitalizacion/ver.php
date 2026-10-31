<?php
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$e = fn($v) => htmlspecialchars((string)$v);
$fmt = fn($f) => $f ? date('d/m/Y H:i', strtotime($f)) : '—';
$edad = function (?string $nac): string {
    if (!$nac) return '—';
    $d = (new DateTime($nac))->diff(new DateTime('today'));
    if ($d->y > 0) return $d->y . ($d->y === 1 ? ' año' : ' años');
    if ($d->m > 0) return $d->m . ($d->m === 1 ? ' mes' : ' meses');
    return $d->d . ($d->d === 1 ? ' día' : ' días');
};
$dias = max(1, (int)(new DateTime(date('Y-m-d', strtotime($h['fecha_ingreso']))))->diff(new DateTime(date('Y-m-d', strtotime($h['fecha_salida'] ?: 'now'))))->days + 1);
[$etq, $color] = $estados[$h['estado']] ?? [$h['estado'], 'secondary'];
$icono = strtolower((string)$h['especie']) === 'gato' ? '🐱' : '🐶';
$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; ECOP <?= $e($h['mascota_nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .ficha { display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap; }
        .ficha-ico { width: 56px; height: 56px; border-radius: 14px; background: #fef3c7; display: flex; align-items: center; justify-content: center; font-size: 1.9rem; flex-shrink: 0; }
        .dato-lbl { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
        .dato-val { font-size: .9rem; font-weight: 600; }
        .datos { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .6rem 1.2rem; flex: 1; min-width: 280px; }

        .ecop-registro { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: .75rem; padding: .9rem 1rem; }
        .ecop-registro.editando { background: #fffbeb; border-color: #fde68a; }
        .ecop-registro label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin-bottom: .15rem; }
        .ecop-tabla th { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; white-space: nowrap; }
        .ecop-tabla td { font-size: .86rem; vertical-align: middle; }
        .ecop-dia td { background: #f8fafc; font-size: .75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .05em; padding: .3rem .75rem; }
        .ecop-tabla tr.fila-nueva { animation: resaltar 1.6s ease-out; }
        @keyframes resaltar { from { background: #bbf7d0; } to { background: transparent; } }
        .via-badge { background: #e0e7ff; color: #3730a3; border-radius: .4rem; padding: .05rem .45rem; font-size: .75rem; font-weight: 700; }
        .bitacora { position: relative; margin-left: .6rem; padding-left: 1.4rem; border-left: 2px solid #e2e8f0; }
        .bit-dia { margin: .9rem 0 .5rem -1.4rem; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        .bit-dia:first-child { margin-top: 0; }
        .bit-nota { position: relative; background: #fff; border: 1px solid #e5e7eb; border-radius: .6rem; padding: .6rem .85rem; margin-bottom: .6rem; }
        .bit-nota::before { content: ''; position: absolute; left: -1.78rem; top: .9rem; width: 12px; height: 12px; border-radius: 50%; background: var(--c, #64748b); border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1; }
        .bit-nota.fila-nueva { animation: resaltar 1.6s ease-out; }
        .bit-cab { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; font-size: .78rem; color: #64748b; margin-bottom: .25rem; }
        .bit-texto { white-space: pre-wrap; font-size: .9rem; }
        .kpi { background: #fff; border: 1px solid #e5e7eb; border-radius: .6rem; padding: .35rem .8rem; font-size: .8rem; }
        .kpi b { font-size: .95rem; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Hoja ECOP · <?= $e($h['mascota_nombre']) ?></h5>
                <p class="topbar-date mb-0"><a href="<?= BASE_URL ?>/hospitalizacion" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Hospitalización</a></p>
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
                                <div class="fw-semibold"><?= $e($usuario['nombre']) ?></div>
                                <div class="text-muted small"><?= $e($usuario['email']) ?></div>
                                <span class="badge badge-rol mt-1"><?= $e(ucfirst($usuario['rol'])) ?></span>
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
            <div class="alert alert-success py-2 mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= $e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $e($error) ?></div>
            <?php endif; ?>

            <!-- Ficha del paciente -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="ficha">
                        <span class="ficha-ico"><?= $icono ?></span>
                        <div class="datos">
                            <div><div class="dato-lbl">Mascota</div><div class="dato-val"><?= $e($h['mascota_nombre']) ?> <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis ms-1"><?= $etq ?></span></div></div>
                            <div><div class="dato-lbl">Especie / raza</div><div class="dato-val"><?= $e($h['especie']) ?><?= $h['raza'] ? ' · ' . $e($h['raza']) : '' ?></div></div>
                            <div><div class="dato-lbl">Sexo / edad</div><div class="dato-val"><?= $h['sexo'] ? $e(ucfirst($h['sexo'])) : '—' ?> · <?= $edad($h['fecha_nacimiento']) ?></div></div>
                            <div><div class="dato-lbl">Peso</div><div class="dato-val"><?= $h['peso'] !== null ? rtrim(rtrim($h['peso'], '0'), '.') . ' kg' : '—' ?></div></div>
                            <div style="grid-column: span 2"><div class="dato-lbl">Diagnóstico</div><div class="dato-val"><?= $e($h['diagnostico']) ?></div></div>
                            <div><div class="dato-lbl">Fecha de ingreso</div><div class="dato-val"><?= $fmt($h['fecha_ingreso']) ?></div></div>
                            <div><div class="dato-lbl">Fecha de salida</div><div class="dato-val"><?= $fmt($h['fecha_salida']) ?></div></div>
                            <div><div class="dato-lbl">Estancia</div><div class="dato-val">Día <?= $dias ?></div></div>
                            <div><div class="dato-lbl">Propietario</div><div class="dato-val"><?= $e($h['cliente_nombre'] ?: '—') ?><?= $h['cliente_telefono'] ? ' · ' . $e($h['cliente_telefono']) : '' ?></div></div>
                            <div><div class="dato-lbl">Veterinario</div><div class="dato-val"><?= $e($h['veterinario_nombre'] ?: '—') ?></div></div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= BASE_URL ?>/hospitalizacion/imprimir?id=<?= (int)$h['id'] ?>"><i class="bi bi-printer me-1"></i>Imprimir ECOP</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/historial?mascota=<?= (int)$h['mascota_id'] ?>"><i class="bi bi-clipboard2-pulse me-1"></i>Historia médica</a>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalDatos"><i class="bi bi-pencil me-1"></i>Editar datos</button>
                            <?php if ($editable): ?>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalSalida"><i class="bi bi-box-arrow-right me-1"></i>Dar salida</button>
                            <?php else: ?>
                            <form method="POST" action="<?= BASE_URL ?>/hospitalizacion/estado" onsubmit="return confirm('¿Reabrir la hoja para seguir registrando aplicaciones?')">
                                <input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><input type="hidden" name="estado" value="internado">
                                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir hoja</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($h['notas']): ?><div class="small text-muted mt-2"><i class="bi bi-sticky me-1"></i><?= $e($h['notas']) ?></div><?php endif; ?>
                </div>
            </div>

            <!-- Páginas de la hoja -->
            <ul class="nav nav-tabs mb-3" id="paginas">
                <li class="nav-item"><button type="button" class="nav-link active" data-pagina="ecop"><i class="bi bi-capsule me-1"></i>ECOP · medicamentos <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" id="tabEcop">0</span></button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-pagina="const"><i class="bi bi-activity me-1"></i>Constancias fisiológicas <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" id="tabConst">0</span></button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-pagina="obs"><i class="bi bi-journal-text me-1"></i>Observaciones <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" id="tabObs">0</span></button></li>
            </ul>

            <!-- ECOP -->
            <div class="card border-0 shadow-sm" id="cardEcop">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold mb-0">ECOP <span class="text-muted fw-normal small">· registro de medicamentos</span></h6>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="kpi">Hoy: <b id="kHoy">0</b></span>
                        <span class="kpi">Total: <b id="kTotal">0</b></span>
                        <span class="kpi">Última: <b id="kUltima">—</b></span>
                        <button class="btn btn-sm btn-light" id="btnOrden" title="Cambiar el orden de la lista"><i class="bi bi-sort-down me-1"></i><span>Más recientes primero</span></button>
                    </div>
                </div>
                <div class="card-body">

                    <?php if ($editable): ?>
                    <!-- Registro rápido: fecha y hora ya vienen con el momento actual; Enter registra -->
                    <form class="ecop-registro mb-3" id="formAplicacion" autocomplete="off">
                        <input type="hidden" id="a-id" value="0">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-2"><label>Fecha</label><input type="date" class="form-control form-control-sm" id="a-fecha" required></div>
                            <div class="col-6 col-md-1"><label>Hora</label><input type="time" class="form-control form-control-sm" id="a-hora" required></div>
                            <div class="col-12 col-md-3"><label>Medicamento *</label><input type="text" class="form-control form-control-sm" id="a-med" list="a-meds" maxlength="150" required placeholder="Busca o escribe…"></div>
                            <div class="col-6 col-md-1"><label>Dosis</label><input type="text" class="form-control form-control-sm" id="a-dosis" maxlength="60" placeholder="0.5 ml"></div>
                            <div class="col-6 col-md-1"><label>Vía</label>
                                <select class="form-select form-select-sm" id="a-via"><option value="">—</option><?php foreach ($vias as $v): ?><option><?= $e($v) ?></option><?php endforeach; ?></select>
                            </div>
                            <div class="col-12 col-md-2"><label>Frecuencia</label><input type="text" class="form-control form-control-sm" id="a-frec" list="a-frecs" maxlength="60" placeholder="BID, cada 8 h…"></div>
                            <div class="col-12 col-md-2"><label>Observaciones</label><input type="text" class="form-control form-control-sm" id="a-obs" maxlength="255"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                            <span class="small text-muted"><i class="bi bi-pen me-1"></i>Firma: <b><?= $e($usuario['nombre']) ?></b> · al medicamento ya usado se le completan dosis, vía y frecuencia</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light d-none" id="btnCancelarEdicion">Cancelar edición</button>
                                <button type="button" class="btn btn-sm btn-light" id="btnAhora" title="Poner la fecha y hora actuales"><i class="bi bi-clock me-1"></i>Ahora</button>
                                <button type="submit" class="btn btn-sm btn-primary-custom" id="btnRegistrar"><i class="bi bi-check2-circle me-1"></i><span>Registrar</span></button>
                            </div>
                        </div>
                        <div class="small text-danger mt-1 d-none" id="aError"></div>
                        <datalist id="a-meds"><?php foreach ($medicamentos as $m): ?><option value="<?= $e($m) ?>"><?php endforeach; ?></datalist>
                        <datalist id="a-frecs"><?php foreach ($frecuencias as $f): ?><option value="<?= $e($f) ?>"><?php endforeach; ?></datalist>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-secondary py-2 small mb-3"><i class="bi bi-lock me-1"></i>La hoja está cerrada (<?= $e(strtolower($etq)) ?>). Usa «Reabrir hoja» si necesitas registrar más aplicaciones.</div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0 ecop-tabla">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th><th>Hora</th><th>Medicamento</th><th>Dosis</th><th>Vía</th><th>Frecuencia</th><th>Firma</th><th>Observaciones</th>
                                    <?php if ($editable): ?><th class="text-end" style="width:110px"></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="filas"></tbody>
                        </table>
                    </div>
                    <div class="text-center text-muted py-4 d-none" id="vacio">
                        <i class="bi bi-capsule fs-2 d-block mb-1 opacity-25"></i>Aún no hay medicamentos registrados en esta hoja.
                    </div>
                </div>
            </div>

            <!-- Constancias fisiológicas (página 2) -->
            <div class="card border-0 shadow-sm d-none" id="cardConst">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold mb-0">Constancias fisiológicas <span class="text-muted fw-normal small">· TEMP, F.C., F.R., P.S.D., S.O., GLU., VOM., DEP., OR.</span></h6>
                    <?php if ($rangos): ?><span class="small text-muted">En rojo: fuera del rango normal de <?= $e(strtolower($h['especie'])) ?> (temp. <?= $rangos['temperatura'][0] ?>–<?= $rangos['temperatura'][1] ?> °C, F.C. <?= $rangos['frecuencia_cardiaca'][0] ?>–<?= $rangos['frecuencia_cardiaca'][1] ?>, F.R. <?= $rangos['frecuencia_respiratoria'][0] ?>–<?= $rangos['frecuencia_respiratoria'][1] ?>)</span><?php endif; ?>
                </div>
                <div class="card-body">

                    <?php if ($editable): ?>
                    <form class="ecop-registro mb-3" id="formConst" autocomplete="off">
                        <input type="hidden" id="c-id" value="0">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-2"><label>Fecha</label><input type="date" class="form-control form-control-sm" id="c-fecha" required></div>
                            <div class="col-6 col-md-1"><label>Hora</label><input type="time" class="form-control form-control-sm" id="c-hora" required></div>
                            <div class="col-4 col-md-1"><label>Temp °C</label><input type="text" inputmode="decimal" class="form-control form-control-sm" id="c-temp" maxlength="12" placeholder="38.5"></div>
                            <div class="col-4 col-md-1"><label>F.C.</label><input type="text" inputmode="numeric" class="form-control form-control-sm" id="c-fc" maxlength="12" placeholder="lpm"></div>
                            <div class="col-4 col-md-1"><label>F.R.</label><input type="text" inputmode="numeric" class="form-control form-control-sm" id="c-fr" maxlength="12" placeholder="rpm"></div>
                            <div class="col-4 col-md-1"><label>P.S.D.</label><input type="text" class="form-control form-control-sm" id="c-psd" maxlength="16" placeholder="120/80"></div>
                            <div class="col-4 col-md-1"><label>S.O. %</label><input type="text" inputmode="numeric" class="form-control form-control-sm" id="c-so" maxlength="12" placeholder="98"></div>
                            <div class="col-4 col-md-1"><label>Glu.</label><input type="text" inputmode="numeric" class="form-control form-control-sm" id="c-glu" maxlength="12" placeholder="mg/dl"></div>
                            <div class="col-4 col-md-1"><label>Vom.</label><input type="text" class="form-control form-control-sm" id="c-vom" list="c-vom-l" maxlength="30"></div>
                            <div class="col-6 col-md-1"><label>Dep.</label><input type="text" class="form-control form-control-sm" id="c-dep" list="c-dep-l" maxlength="30"></div>
                            <div class="col-6 col-md-1"><label>Or.</label><input type="text" class="form-control form-control-sm" id="c-orina" list="c-or-l" maxlength="30"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                            <span class="small text-muted"><i class="bi bi-pen me-1"></i>Firma: <b><?= $e($usuario['nombre']) ?></b> · Tab pasa de casilla en casilla y Enter registra</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light d-none" id="btnCancelarConst">Cancelar edición</button>
                                <button type="button" class="btn btn-sm btn-light" id="btnAhoraConst"><i class="bi bi-clock me-1"></i>Ahora</button>
                                <button type="submit" class="btn btn-sm btn-primary-custom" id="btnRegistrarConst"><i class="bi bi-check2-circle me-1"></i><span>Registrar</span></button>
                            </div>
                        </div>
                        <div class="small text-danger mt-1 d-none" id="cError"></div>
                        <datalist id="c-vom-l"><option value="No"><option value="1"><option value="2"><option value="3"><option value="Espuma"><option value="Bilis"><option value="Alimento"></datalist>
                        <datalist id="c-dep-l"><option value="No"><option value="Normal"><option value="Blanda"><option value="Líquida"><option value="Con sangre"><option value="Melena"></datalist>
                        <datalist id="c-or-l"><option value="No"><option value="Normal"><option value="Poca"><option value="Abundante"><option value="Con sangre"></datalist>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-secondary py-2 small mb-3"><i class="bi bi-lock me-1"></i>La hoja está cerrada (<?= $e(strtolower($etq)) ?>).</div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0 ecop-tabla">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th><th>Hora</th><th>Temp.</th><th>F.C.</th><th>F.R.</th><th>P.S.D.</th><th>S.O.</th><th>Glu.</th><th>Vom.</th><th>Dep.</th><th>Or.</th><th>Firma</th>
                                    <?php if ($editable): ?><th class="text-end" style="width:110px"></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="filasConst"></tbody>
                        </table>
                    </div>
                    <div class="text-center text-muted py-4 d-none" id="vacioConst">
                        <i class="bi bi-activity fs-2 d-block mb-1 opacity-25"></i>Aún no hay constancias registradas en esta hoja.
                    </div>
                </div>
            </div>

            <!-- Observaciones (bitácora) -->
            <div class="card border-0 shadow-sm d-none" id="cardObs">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold mb-0">Bitácora <span class="text-muted fw-normal small">· observaciones del paciente</span></h6>
                    <div class="d-flex gap-1 flex-wrap" id="filtrosObs"></div>
                </div>
                <div class="card-body">

                    <?php if ($editable): ?>
                    <form class="ecop-registro mb-4" id="formObs" autocomplete="off">
                        <input type="hidden" id="o-id" value="0">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-2"><label>Fecha</label><input type="date" class="form-control form-control-sm" id="o-fecha" required></div>
                            <div class="col-6 col-md-2"><label>Hora</label><input type="time" class="form-control form-control-sm" id="o-hora" required></div>
                            <div class="col-12 col-md-3"><label>Tipo</label>
                                <select class="form-select form-select-sm" id="o-cat"><?php foreach ($categorias as $k => [$etiq]): ?><option value="<?= $k ?>"><?= $e($etiq) ?></option><?php endforeach; ?></select>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label>Observación *</label>
                            <textarea class="form-control" id="o-texto" rows="3" maxlength="2000" required placeholder="Evolución, apetito, actitud, curaciones, llamada al propietario…"></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                            <span class="small text-muted"><i class="bi bi-pen me-1"></i>Firma: <b><?= $e($usuario['nombre']) ?></b> · Ctrl+Enter registra</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light d-none" id="btnCancelarObs">Cancelar edición</button>
                                <button type="button" class="btn btn-sm btn-light" id="btnAhoraObs"><i class="bi bi-clock me-1"></i>Ahora</button>
                                <button type="submit" class="btn btn-sm btn-primary-custom" id="btnRegistrarObs"><i class="bi bi-check2-circle me-1"></i><span>Registrar</span></button>
                            </div>
                        </div>
                        <div class="small text-danger mt-1 d-none" id="oError"></div>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-secondary py-2 small mb-3"><i class="bi bi-lock me-1"></i>La hoja está cerrada (<?= $e(strtolower($etq)) ?>).</div>
                    <?php endif; ?>

                    <div class="bitacora" id="bitacora"></div>
                    <div class="text-center text-muted py-4 d-none" id="vacioObs">
                        <i class="bi bi-journal-text fs-2 d-block mb-1 opacity-25"></i>Aún no hay observaciones en la bitácora.
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ── MODAL EDITAR DATOS ───────────────────────────── -->
<div class="modal fade" id="modalDatos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/hospitalizacion/guardar" autocomplete="off">
                <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                <input type="hidden" name="mascota_id" value="<?= (int)$h['mascota_id'] ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil text-primary me-2"></i>Datos de la hospitalización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label fw-semibold">Diagnóstico / motivo</label>
                            <input type="text" class="form-control" name="diagnostico" maxlength="255" required value="<?= $e($h['diagnostico']) ?>"></div>
                        <div class="col-md-7"><label class="form-label fw-semibold">Fecha y hora de ingreso</label>
                            <input type="datetime-local" class="form-control" name="fecha_ingreso" value="<?= date('Y-m-d\TH:i', strtotime($h['fecha_ingreso'])) ?>"></div>
                        <div class="col-md-5"><label class="form-label fw-semibold">Peso (kg)</label>
                            <input type="number" class="form-control" name="peso" step="0.01" min="0" value="<?= $e($h['peso']) ?>"></div>
                        <div class="col-12"><label class="form-label fw-semibold">Veterinario responsable</label>
                            <input type="text" class="form-control" id="d-vet" list="d-vets" value="<?= $e($h['veterinario_nombre']) ?>">
                            <input type="hidden" name="veterinario_id" id="d-vet-id" value="<?= (int)$h['veterinario_id'] ?>">
                            <datalist id="d-vets"><?php foreach ($veterinarios as $v): ?><option value="<?= $e($v['nombre']) ?>"><?php endforeach; ?></datalist></div>
                        <div class="col-12"><label class="form-label fw-semibold">Notas</label>
                            <input type="text" class="form-control" name="notas" maxlength="255" value="<?= $e($h['notas']) ?>"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL DAR SALIDA ─────────────────────────────── -->
<div class="modal fade" id="modalSalida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= BASE_URL ?>/hospitalizacion/estado">
                <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-right text-success me-2"></i>Dar salida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <label class="form-label fw-semibold">Motivo de salida</label>
                    <select class="form-select mb-3" name="estado">
                        <option value="alta">Alta médica</option>
                        <option value="referido">Referido a otra clínica</option>
                        <option value="fallecido">Fallecido</option>
                    </select>
                    <label class="form-label fw-semibold">Fecha y hora de salida</label>
                    <input type="datetime-local" class="form-control" name="fecha_salida" value="<?= date('Y-m-d\TH:i') ?>">
                    <div class="form-text">La hoja queda cerrada y lista para imprimir.</div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar salida</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $id = id => document.getElementById(id);
const BASE_URL = '<?= BASE_URL ?>';
const HID =<?= (int)$h['id'] ?>;
const EDITABLE = <?= $editable ? 'true' : 'false' ?>;
let filas = <?= json_encode($aplicaciones, $jsonFlags) ?>;
let recientesPrimero = true;
let ultimoId = 0;

const pad = n => String(n).padStart(2, '0');
const hoyISO = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
const ahoraHM = () => { const d = new Date(); return `${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const fechaCorta = iso => iso.split('-').reverse().join('/');
const nombreDia = iso => new Date(iso + 'T00:00').toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long' });

// ── Lista ───────────────────────────────────────────────
function pintar() {
    const orden = [...filas];
    if (recientesPrimero) orden.reverse();
    let html = '', diaActual = null;
    orden.forEach(f => {
        if (f.fecha !== diaActual) {
            diaActual = f.fecha;
            html += `<tr class="ecop-dia"><td colspan="${EDITABLE ? 9 : 8}">${escHtml(nombreDia(f.fecha))}</td></tr>`;
        }
        html += `<tr data-id="${f.id}" class="${f.id === ultimoId ? 'fila-nueva' : ''}">
            <td class="text-nowrap">${fechaCorta(f.fecha)}</td>
            <td class="text-nowrap fw-semibold">${escHtml(f.hora)}</td>
            <td class="fw-semibold">${escHtml(f.medicamento)}</td>
            <td>${escHtml(f.dosis) || '—'}</td>
            <td>${f.via ? `<span class="via-badge">${escHtml(f.via)}</span>` : '—'}</td>
            <td>${escHtml(f.frecuencia) || '—'}</td>
            <td class="small text-muted">${escHtml(f.firma)}</td>
            <td class="small">${escHtml(f.observaciones)}</td>
            ${EDITABLE ? `<td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-success btn-accion" title="Repetir (nueva aplicación ahora)" onclick="repetir(${f.id})"><i class="bi bi-arrow-repeat"></i></button>
                <button class="btn btn-sm btn-outline-secondary btn-accion" title="Editar" onclick="editar(${f.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar" onclick="eliminar(${f.id})"><i class="bi bi-trash"></i></button>
            </td>` : ''}
        </tr>`;
    });
    $id('filas').innerHTML = html;
    $id('vacio').classList.toggle('d-none', filas.length > 0);
    $id('tabEcop').textContent = filas.length;
    kpis();
}

function kpis() {
    $id('kTotal').textContent = filas.length;
    $id('kHoy').textContent = filas.filter(f => f.fecha === hoyISO()).length;
    const u = filas[filas.length - 1];
    if (!u) { $id('kUltima').textContent = '—'; return; }
    const min = Math.round((Date.now() - new Date(`${u.fecha}T${u.hora}`)) / 60000);
    $id('kUltima').textContent = min < 0 ? u.hora : min < 60 ? `hace ${min} min` : min < 1440 ? `hace ${Math.floor(min / 60)} h ${min % 60} min` : `${fechaCorta(u.fecha)} ${u.hora}`;
}
setInterval(kpis, 60000);

$id('btnOrden').addEventListener('click', () => {
    recientesPrimero = !recientesPrimero;
    $id('btnOrden').querySelector('span').textContent = recientesPrimero ? 'Más recientes primero' : 'Orden cronológico';
    pintar();
});

// ── Registro rápido ─────────────────────────────────────
function ahora() { $id('a-fecha').value = hoyISO(); $id('a-hora').value = ahoraHM(); }

function limpiarForm() {
    $id('a-id').value = 0;
    ['a-med', 'a-dosis', 'a-frec', 'a-obs'].forEach(i => $id(i).value = '');
    $id('a-via').value = '';
    ahora();
    $id('formAplicacion').classList.remove('editando');
    $id('btnRegistrar').querySelector('span').textContent = 'Registrar';
    $id('btnCancelarEdicion').classList.add('d-none');
    $id('aError').classList.add('d-none');
}

function cargar(f, comoNueva) {
    $id('a-id').value    = comoNueva ? 0 : f.id;
    $id('a-fecha').value = comoNueva ? hoyISO() : f.fecha;
    $id('a-hora').value  = comoNueva ? ahoraHM() : f.hora;
    $id('a-med').value   = f.medicamento;
    $id('a-dosis').value = f.dosis;
    $id('a-via').value   = f.via;
    $id('a-frec').value  = f.frecuencia;
    $id('a-obs').value   = comoNueva ? '' : f.observaciones;
    $id('formAplicacion').classList.toggle('editando', !comoNueva);
    $id('btnRegistrar').querySelector('span').textContent = comoNueva ? 'Registrar' : 'Guardar cambios';
    $id('btnCancelarEdicion').classList.toggle('d-none', comoNueva);
    $id('formAplicacion').scrollIntoView({ behavior: 'smooth', block: 'center' });
    $id(comoNueva ? 'btnRegistrar' : 'a-med').focus();
}
const repetir = id => cargar(filas.find(f => f.id === id), true);
const editar  = id => cargar(filas.find(f => f.id === id), false);

// POST a la hoja: siempre devuelve {ok, error, filas}; si el servidor no responde JSON, explica por qué
async function postHoja(ruta, datos) {
    const fd = new FormData();
    fd.append('hospitalizacion_id', HID);
    Object.entries(datos).forEach(([k, v]) => fd.append(k, v));
    let r;
    try {
        r = await fetch(`${BASE_URL}/hospitalizacion/${ruta}`, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
    } catch (e) {
        return { ok: false, error: 'Sin conexión con el servidor. Revisa tu internet e intenta de nuevo.' };
    }
    try {
        const j = await r.json();
        if (!j.ok && !j.error) j.error = `El servidor respondió con un error (${r.status}).`;
        return j;
    } catch (e) {
        return { ok: false, error: r.redirected || r.status === 401 ? 'La sesión expiró. Recarga la página e inicia sesión.' : `El servidor devolvió una respuesta inesperada (${r.status}). Recarga la página.` };
    }
}

const enviar = datos => postHoja('aplicacion', datos);

async function eliminar(id) {
    const f = filas.find(x => x.id === id);
    if (!confirm(`¿Eliminar «${f.medicamento}» de las ${f.hora}?`)) return;
    const r = await enviar({ accion: 'eliminar', id });
    if (r.ok) { filas = r.filas; ultimoId = 0; pintar(); } else alert(r.error);
}

if (EDITABLE) {
    ahora();
    // Al elegir un medicamento ya usado se completan dosis, vía y frecuencia con su última aplicación
    $id('a-med').addEventListener('change', () => {
        if ($id('a-id').value !== '0') return;
        const previa = [...filas].reverse().find(f => f.medicamento.toLowerCase() === $id('a-med').value.trim().toLowerCase());
        if (!previa) return;
        if (!$id('a-dosis').value) $id('a-dosis').value = previa.dosis;
        if (!$id('a-via').value)   $id('a-via').value   = previa.via;
        if (!$id('a-frec').value)  $id('a-frec').value  = previa.frecuencia;
    });
    $id('btnAhora').addEventListener('click', ahora);
    $id('btnCancelarEdicion').addEventListener('click', limpiarForm);

    $id('formAplicacion').addEventListener('submit', async e => {
        e.preventDefault();
        const btn = $id('btnRegistrar');
        btn.disabled = true;
        const editando = $id('a-id').value !== '0';
        const r = await enviar({
            accion: 'guardar', id: $id('a-id').value, fecha: $id('a-fecha').value, hora: $id('a-hora').value,
            medicamento: $id('a-med').value, dosis: $id('a-dosis').value, via: $id('a-via').value,
            frecuencia: $id('a-frec').value, observaciones: $id('a-obs').value,
        }).catch(e => ({ ok: false, error: 'Error en la página: ' + e.message }));
        btn.disabled = false;
        if (!r.ok) { $id('aError').textContent = r.error; $id('aError').classList.remove('d-none'); return; }
        const previos = new Set(filas.map(f => f.id));
        filas = r.filas;
        ultimoId = editando ? Number($id('a-id').value) : (filas.find(f => !previos.has(f.id)) || {}).id || 0;
        limpiarForm();
        pintar();
        $id('a-med').focus();
    });
}

$id('d-vet').addEventListener('input', () => {
    const lista = <?= json_encode(array_map(fn($v) => ['id' => (int)$v['id'], 'nombre' => $v['nombre']], $veterinarios), $jsonFlags) ?>;
    const v = lista.find(x => x.nombre.toLowerCase() === $id('d-vet').value.trim().toLowerCase());
    $id('d-vet-id').value = v ? v.id : 0;
});

pintar();

// ── Páginas de la hoja (pestañas) ───────────────────────
function irPagina(p) {
    $id('cardEcop').classList.toggle('d-none', p !== 'ecop');
    $id('cardConst').classList.toggle('d-none', p !== 'const');
    $id('cardObs').classList.toggle('d-none', p !== 'obs');
    document.querySelectorAll('#paginas .nav-link').forEach(b => b.classList.toggle('active', b.dataset.pagina === p));
    history.replaceState(null, '', p === 'const' ? '#constancias' : p === 'obs' ? '#observaciones' : location.pathname + location.search);
}
document.querySelectorAll('#paginas .nav-link').forEach(b => b.addEventListener('click', () => irPagina(b.dataset.pagina)));

// ── Constancias fisiológicas ────────────────────────────
let constancias = <?= json_encode($constancias, $jsonFlags) ?>;
let ultimaConst = 0;
const RANGOS = <?= json_encode($rangos, $jsonFlags) ?>;
const CAMPOS_C = ['temp', 'fc', 'fr', 'psd', 'so', 'glu', 'vom', 'dep', 'orina'];

// Marca en rojo lo que sale del rango normal de la especie
function fuera(campo, valor) {
    if (!RANGOS || valor === '') return false;
    const r = { temp: RANGOS.temperatura, fc: RANGOS.frecuencia_cardiaca, fr: RANGOS.frecuencia_respiratoria }[campo];
    const n = parseFloat(String(valor).replace(',', '.'));
    return !!r && !isNaN(n) && (n < r[0] || n > r[1]);
}

function pintarConst() {
    const orden = [...constancias];
    if (recientesPrimero) orden.reverse();
    let html = '', dia = null;
    orden.forEach(f => {
        if (f.fecha !== dia) {
            dia = f.fecha;
            html += `<tr class="ecop-dia"><td colspan="${EDITABLE ? 13 : 12}">${escHtml(nombreDia(f.fecha))}</td></tr>`;
        }
        const celda = c => `<td class="${fuera(c, f[c]) ? 'text-danger fw-bold' : ''}">${escHtml(f[c]) || '—'}</td>`;
        html += `<tr class="${f.id === ultimaConst ? 'fila-nueva' : ''}">
            <td class="text-nowrap">${fechaCorta(f.fecha)}</td><td class="fw-semibold">${escHtml(f.hora)}</td>
            ${CAMPOS_C.map(celda).join('')}
            <td class="small text-muted">${escHtml(f.firma)}</td>
            ${EDITABLE ? `<td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-secondary btn-accion" title="Editar" onclick="editarConst(${f.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-accion" title="Eliminar" onclick="eliminarConst(${f.id})"><i class="bi bi-trash"></i></button>
            </td>` : ''}
        </tr>`;
    });
    $id('filasConst').innerHTML = html;
    $id('vacioConst').classList.toggle('d-none', constancias.length > 0);
    $id('tabConst').textContent = constancias.length;
    $id('tabEcop').textContent = filas.length;
}

const enviarConst = datos => postHoja('constancia', datos);

async function eliminarConst(id) {
    const f = constancias.find(x => x.id === id);
    if (!confirm(`¿Eliminar las constancias de las ${f.hora}?`)) return;
    const r = await enviarConst({ accion: 'eliminar', id });
    if (r.ok) { constancias = r.filas; ultimaConst = 0; pintarConst(); } else alert(r.error);
}

if (EDITABLE) {
    const ahoraConst = () => { $id('c-fecha').value = hoyISO(); $id('c-hora').value = ahoraHM(); };
    const limpiarConst = () => {
        $id('c-id').value = 0;
        CAMPOS_C.forEach(c => $id('c-' + c).value = '');
        ahoraConst();
        $id('formConst').classList.remove('editando');
        $id('btnRegistrarConst').querySelector('span').textContent = 'Registrar';
        $id('btnCancelarConst').classList.add('d-none');
        $id('cError').classList.add('d-none');
    };
    window.editarConst = id => {
        const f = constancias.find(x => x.id === id);
        $id('c-id').value = f.id; $id('c-fecha').value = f.fecha; $id('c-hora').value = f.hora;
        CAMPOS_C.forEach(c => $id('c-' + c).value = f[c]);
        $id('formConst').classList.add('editando');
        $id('btnRegistrarConst').querySelector('span').textContent = 'Guardar cambios';
        $id('btnCancelarConst').classList.remove('d-none');
        $id('formConst').scrollIntoView({ behavior: 'smooth', block: 'center' });
        $id('c-temp').focus();
    };
    ahoraConst();
    $id('btnAhoraConst').addEventListener('click', ahoraConst);
    $id('btnCancelarConst').addEventListener('click', limpiarConst);
    $id('formConst').addEventListener('submit', async e => {
        e.preventDefault();
        const btn = $id('btnRegistrarConst');
        btn.disabled = true;
        const editando = $id('c-id').value !== '0';
        const datos = { accion: 'guardar', id: $id('c-id').value, fecha: $id('c-fecha').value, hora: $id('c-hora').value };
        CAMPOS_C.forEach(c => datos[c] = $id('c-' + c).value);
        const r = await enviarConst(datos).catch(e => ({ ok: false, error: 'Error en la página: ' + e.message }));
        btn.disabled = false;
        if (!r.ok) { $id('cError').textContent = r.error; $id('cError').classList.remove('d-none'); return; }
        const previos = new Set(constancias.map(f => f.id));
        constancias = r.filas;
        ultimaConst = editando ? Number($id('c-id').value) : (constancias.find(f => !previos.has(f.id)) || {}).id || 0;
        limpiarConst();
        pintarConst();
        $id('c-temp').focus();
    });
}

// El botón de orden aplica a las dos páginas
$id('btnOrden').addEventListener('click', pintarConst);
pintarConst();
if (location.hash === '#constancias') irPagina('const');

// ── Bitácora de observaciones ───────────────────────────
let observaciones = <?= json_encode($observaciones, $jsonFlags) ?>;
const CATEGORIAS = <?= json_encode($categorias, $jsonFlags) ?>;   // clave => [etiqueta, color bootstrap, ícono]
const COLOR_CAT = { primary: '#4f46e5', success: '#16a34a', info: '#0891b2', danger: '#dc2626', warning: '#d97706', secondary: '#64748b' };
let filtroObs = '';
let ultimaObs = 0;

function pintarObs() {
    // filtros por tipo (solo los que tienen notas)
    const usadas = [...new Set(observaciones.map(o => o.categoria))];
    $id('filtrosObs').innerHTML = usadas.length > 1
        ? [['', 'Todas'], ...usadas.map(k => [k, (CATEGORIAS[k] || CATEGORIAS.otro)[0]])].map(([k, t]) =>
            `<button type="button" class="btn btn-sm ${filtroObs === k ? 'btn-primary' : 'btn-light'}" data-cat="${k}">${escHtml(t)}</button>`).join('')
        : '';

    const lista = observaciones.filter(o => !filtroObs || o.categoria === filtroObs);
    if (recientesPrimero) lista.reverse();
    let html = '', dia = null;
    lista.forEach(o => {
        if (o.fecha !== dia) { dia = o.fecha; html += `<div class="bit-dia">${escHtml(nombreDia(o.fecha))}</div>`; }
        const [etq, color, icono] = CATEGORIAS[o.categoria] || CATEGORIAS.otro;
        html += `<div class="bit-nota ${o.id === ultimaObs ? 'fila-nueva' : ''}" style="--c:${COLOR_CAT[color] || '#64748b'}">
            <div class="bit-cab">
                <b class="text-dark">${escHtml(o.hora)}</b>
                <span class="badge bg-${color}-subtle text-${color}-emphasis"><i class="bi bi-${icono} me-1"></i>${escHtml(etq)}</span>
                <span>${escHtml(o.firma)}</span>
                ${EDITABLE ? `<span class="ms-auto text-nowrap">
                    <button class="btn btn-sm btn-link text-secondary p-0 me-2" title="Editar" onclick="editarObs(${o.id})"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-link text-danger p-0" title="Eliminar" onclick="eliminarObs(${o.id})"><i class="bi bi-trash"></i></button>
                </span>` : ''}
            </div>
            <div class="bit-texto">${escHtml(o.texto)}</div>
        </div>`;
    });
    $id('bitacora').innerHTML = html;
    $id('bitacora').classList.toggle('d-none', !observaciones.length);
    $id('vacioObs').classList.toggle('d-none', observaciones.length > 0);
    $id('tabObs').textContent = observaciones.length;
}

$id('filtrosObs').addEventListener('click', e => {
    const b = e.target.closest('button[data-cat]');
    if (b) { filtroObs = b.dataset.cat; pintarObs(); }
});

const enviarObs = datos => postHoja('observacion', datos);

async function eliminarObs(id) {
    if (!confirm('¿Eliminar esta observación de la bitácora?')) return;
    const r = await enviarObs({ accion: 'eliminar', id });
    if (r.ok) { observaciones = r.filas; ultimaObs = 0; pintarObs(); } else alert(r.error);
}

if (EDITABLE) {
    const ahoraObs = () => { $id('o-fecha').value = hoyISO(); $id('o-hora').value = ahoraHM(); };
    const limpiarObs = () => {
        $id('o-id').value = 0;
        $id('o-texto').value = '';
        ahoraObs();
        $id('formObs').classList.remove('editando');
        $id('btnRegistrarObs').querySelector('span').textContent = 'Registrar';
        $id('btnCancelarObs').classList.add('d-none');
        $id('oError').classList.add('d-none');
    };
    window.editarObs = id => {
        const o = observaciones.find(x => x.id === id);
        $id('o-id').value = o.id; $id('o-fecha').value = o.fecha; $id('o-hora').value = o.hora;
        $id('o-cat').value = o.categoria; $id('o-texto').value = o.texto;
        $id('formObs').classList.add('editando');
        $id('btnRegistrarObs').querySelector('span').textContent = 'Guardar cambios';
        $id('btnCancelarObs').classList.remove('d-none');
        $id('formObs').scrollIntoView({ behavior: 'smooth', block: 'center' });
        $id('o-texto').focus();
    };
    ahoraObs();
    $id('btnAhoraObs').addEventListener('click', ahoraObs);
    $id('btnCancelarObs').addEventListener('click', limpiarObs);
    $id('o-texto').addEventListener('keydown', e => { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); $id('formObs').requestSubmit(); } });
    $id('formObs').addEventListener('submit', async e => {
        e.preventDefault();
        const btn = $id('btnRegistrarObs');
        btn.disabled = true;
        const editando = $id('o-id').value !== '0';
        const r = await enviarObs({
            accion: 'guardar', id: $id('o-id').value, fecha: $id('o-fecha').value, hora: $id('o-hora').value,
            categoria: $id('o-cat').value, texto: $id('o-texto').value,
        }).catch(err => ({ ok: false, error: 'Error en la página: ' + err.message }));
        btn.disabled = false;
        if (!r.ok) { $id('oError').textContent = r.error; $id('oError').classList.remove('d-none'); return; }
        const previos = new Set(observaciones.map(o => o.id));
        observaciones = r.filas;
        ultimaObs = editando ? Number($id('o-id').value) : (observaciones.find(o => !previos.has(o.id)) || {}).id || 0;
        limpiarObs();
        pintarObs();
        $id('o-texto').focus();
    });
}

$id('btnOrden').addEventListener('click', pintarObs);
pintarObs();
if (location.hash === '#observaciones') irPagina('obs');
</script>
</body>
</html>
