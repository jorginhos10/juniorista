<?php
$fmtPct = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Negocio / Peluquería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .reparto-card { max-width: 760px; }
        .parte {
            border: 2px solid var(--c); border-radius: 14px; padding: 1rem 1.1rem;
            background: color-mix(in srgb, var(--c) 6%, #fff);
        }
        .parte-titulo { font-weight: 700; display: flex; align-items: center; gap: .5rem; }
        .parte-titulo i { color: var(--c); font-size: 1.2rem; }
        .parte-desc { font-size: .78rem; color: #64748b; margin: .15rem 0 .75rem; }
        .parte .input-group input { font-size: 1.6rem; font-weight: 800; text-align: right; color: var(--c); }
        .parte .input-group-text { font-size: 1.2rem; font-weight: 700; }

        .barra-reparto { display: flex; height: 18px; border-radius: 9px; overflow: hidden; background: #e2e8f0; }
        .barra-reparto div { transition: width .2s; }
        .ejemplo { background: #f8fafc; border-radius: 12px; padding: 1rem 1.1rem; }
        .ejemplo .monto { font-variant-numeric: tabular-nums; font-weight: 800; font-size: 1.15rem; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Negocio / Peluquería</h5>
                <p class="topbar-date mb-0">
                    <a href="<?= BASE_URL ?>/configuracion" class="text-decoration-none text-muted">Configuración</a>
                    <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> Reparto de lo cobrado en peluquería
                </p>
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

            <div class="card border-0 shadow-sm reparto-card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1">Reparto de los servicios de peluquería</h6>
                    <p class="text-muted small mb-4">
                        Sucursal: <strong><?= htmlspecialchars($sucursal['nombre'] ?? '') ?></strong>.
                        Define qué parte de cada servicio cobrado es ganancia del negocio y qué parte le corresponde a la peluquería.
                    </p>

                    <form method="POST" action="<?= BASE_URL ?>/configuracion/peluqueria/guardar" id="formReparto">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="parte" style="--c:#4f46e5">
                                    <div class="parte-titulo"><i class="bi bi-shop"></i>Negocio</div>
                                    <div class="parte-desc">Lo que se obtiene como ganancia</div>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="pct_negocio" id="pctNegocio"
                                               min="0" max="100" step="0.01" value="<?= $fmtPct(100 - $pctPeluqueria) ?>" <?= $esAdmin ? '' : 'disabled' ?> required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="parte" style="--c:#db2777">
                                    <div class="parte-titulo"><i class="bi bi-scissors"></i>Peluquería</div>
                                    <div class="parte-desc">Lo que le corresponde a la peluquería</div>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="pct_peluqueria" id="pctPeluqueria"
                                               min="0" max="100" step="0.01" value="<?= $fmtPct($pctPeluqueria) ?>" <?= $esAdmin ? '' : 'disabled' ?> required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="range" class="form-range mb-1" id="rango" min="0" max="100" step="1"
                               value="<?= $fmtPct(100 - $pctPeluqueria) ?>" <?= $esAdmin ? '' : 'disabled' ?> aria-label="Porcentaje del negocio">
                        <div class="barra-reparto mb-1">
                            <div id="barNegocio" style="background:#4f46e5"></div>
                            <div id="barPelu" style="background:#db2777"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-4">
                            <span>Negocio <strong id="txtNegocio"></strong></span>
                            <span id="txtSuma"></span>
                            <span>Peluquería <strong id="txtPelu"></strong></span>
                        </div>

                        <div class="ejemplo mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <span class="small fw-semibold">Ejemplo: de un servicio de</span>
                                <div class="input-group input-group-sm" style="width:150px">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="ejemploValor" value="100" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="small text-muted">Le toca al negocio</div>
                                    <div class="monto" style="color:#4f46e5" id="ejNegocio"></div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Le toca a la peluquería</div>
                                    <div class="monto" style="color:#db2777" id="ejPelu"></div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 small">
                            <i class="bi bi-info-circle me-1"></i>
                            El porcentaje se guarda en cada servicio al registrarlo: si lo cambias, los servicios ya registrados conservan el reparto con el que se hicieron.
                        </div>

                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>/configuracion" class="btn btn-light">Volver</a>
                            <?php if ($esAdmin): ?>
                            <button type="submit" class="btn btn-primary-custom" id="btnGuardar"><i class="bi bi-save me-1"></i>Guardar reparto</button>
                            <?php else: ?>
                            <span class="small text-muted align-self-center"><i class="bi bi-lock me-1"></i>Solo un administrador puede cambiar el reparto.</span>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/peluqueria" class="btn btn-outline-secondary ms-auto"><i class="bi bi-scissors me-1"></i>Ir a Peluquería</a>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const neg = document.getElementById('pctNegocio'), pel = document.getElementById('pctPeluqueria'), rango = document.getElementById('rango');
const money = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const r2 = n => Math.round(n * 100) / 100;

function pintar() {
    const n = parseFloat(neg.value) || 0, p = parseFloat(pel.value) || 0, ok = Math.abs(n + p - 100) < 0.001;
    document.getElementById('barNegocio').style.width = Math.max(0, Math.min(100, n)) + '%';
    document.getElementById('barPelu').style.width    = Math.max(0, Math.min(100, p)) + '%';
    document.getElementById('txtNegocio').textContent = r2(n) + ' %';
    document.getElementById('txtPelu').textContent    = r2(p) + ' %';
    document.getElementById('txtSuma').innerHTML = ok ? '<i class="bi bi-check-circle-fill text-success"></i> Suma 100 %'
        : '<span class="text-danger"><i class="bi bi-exclamation-circle-fill"></i> Debe sumar 100 %</span>';
    const btn = document.getElementById('btnGuardar');
    if (btn) btn.disabled = !ok;

    const v = parseFloat(document.getElementById('ejemploValor').value) || 0;
    const parteP = r2(v * p / 100);
    document.getElementById('ejPelu').textContent    = money(parteP);
    document.getElementById('ejNegocio').textContent = money(v - parteP);
}

// Los dos porcentajes se complementan: al cambiar uno, el otro se ajusta para sumar 100
neg.addEventListener('input', () => { pel.value = r2(100 - (parseFloat(neg.value) || 0)); rango.value = neg.value; pintar(); });
pel.addEventListener('input', () => { neg.value = r2(100 - (parseFloat(pel.value) || 0)); rango.value = neg.value; pintar(); });
rango.addEventListener('input', () => { neg.value = rango.value; pel.value = 100 - rango.value; pintar(); });
document.getElementById('ejemploValor').addEventListener('input', pintar);
pintar();
</script>
</body>
</html>
