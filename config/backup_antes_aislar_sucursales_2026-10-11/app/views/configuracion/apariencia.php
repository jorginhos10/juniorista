<?php
$temas = [
    'original' => [
        'nombre' => 'Original',
        'desc'   => 'El aspecto clásico de PetControl: índigo, limpio y luminoso.',
        'icono'  => 'bi-heart-pulse-fill',
        'side'   => 'linear-gradient(180deg, #1e1b4b 0%, #312e81 100%)',
        'fondo'  => '#f1f5f9',
        'acento' => 'linear-gradient(135deg, #4f46e5, #7c3aed)',
        'chips'  => ['#4f46e5', '#7c3aed', '#16a34a'],
    ],
    'halloween' => [
        'nombre' => 'Halloween',
        'desc'   => 'Calabazas, murciélagos y tonos naranja y morado para la noche de brujas.',
        'icono'  => 'bi-moon-stars-fill',
        'side'   => 'linear-gradient(180deg, #0f0a14 0%, #2a0f3d 100%)',
        'fondo'  => '#fdf3e4',
        'acento' => 'linear-gradient(135deg, #f97316, #9333ea)',
        'chips'  => ['#f97316', '#9333ea', '#65a30d'],
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Apariencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .tema-card {
            display: block;
            cursor: pointer;
            background: #fff;
            border: 2px solid #e8eaf0;
            border-radius: 1rem;
            overflow: hidden;
            transition: border-color .18s, box-shadow .18s, transform .15s;
            height: 100%;
        }
        .tema-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,23,42,.08); }
        .tema-radio:checked + .tema-card { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79,70,229,.12); }
        .tema-radio { position: absolute; opacity: 0; pointer-events: none; }

        .tema-preview { display: flex; height: 170px; position: relative; }
        .tema-preview-side { width: 52px; display: flex; flex-direction: column; align-items: center; gap: 10px; padding-top: 14px; }
        .tema-preview-side span { width: 22px; height: 6px; border-radius: 3px; background: rgba(255,255,255,.25); }
        .tema-preview-side span:first-child { width: 26px; height: 26px; border-radius: 8px; margin-bottom: 6px; }
        .tema-preview-main { flex: 1; padding: 14px; display: flex; flex-direction: column; gap: 10px; }
        .tema-preview-top { height: 18px; border-radius: 6px; background: #fff; }
        .tema-preview-cards { display: flex; gap: 8px; }
        .tema-preview-cards div { flex: 1; height: 44px; background: #fff; border-radius: 8px; position: relative; }
        .tema-preview-cards div::after { content: ''; position: absolute; left: 8px; top: 10px; width: 18px; height: 18px; border-radius: 6px; background: var(--c); opacity: .85; }
        .tema-preview-btn { align-self: flex-end; width: 74px; height: 20px; border-radius: 6px; }
        .tema-preview-deco { position: absolute; font-size: 1.5rem; }

        .tema-body { padding: 1rem 1.25rem; display: flex; align-items: center; gap: .9rem; }
        .tema-check { margin-left: auto; font-size: 1.4rem; color: #d1d5db; }
        .tema-radio:checked + .tema-card .tema-check { color: var(--primary); }
        .tema-radio:checked + .tema-card .tema-check::before { content: "\f26a"; }
        .tema-activo-badge { font-size: .68rem; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">

    <?php require ROOT . '/app/views/layouts/sidebar.php'; ?>

    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <h5 class="topbar-title mb-0">Apariencias</h5>
                <p class="topbar-date mb-0">
                    <a href="<?= BASE_URL ?>/configuracion" class="text-decoration-none text-muted">Configuración</a>
                    <i class="bi bi-chevron-right mx-1" style="font-size:.7rem"></i> Tema visual
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

            <form method="POST" action="<?= BASE_URL ?>/configuracion/apariencia/guardar">
                <p class="text-muted small mb-3">
                    Elige el tema del sistema. Se guarda en tu cuenta y se aplica en todas las pantallas.
                </p>

                <div class="row g-3 mb-4">
                    <?php foreach ($temas as $clave => $t): ?>
                    <div class="col-md-6 col-xl-4">
                        <input type="radio" class="tema-radio" name="tema" id="tema-<?= $clave ?>"
                               value="<?= $clave ?>" <?= $temaActual === $clave ? 'checked' : '' ?>>
                        <label class="tema-card" for="tema-<?= $clave ?>">
                            <div class="tema-preview" style="background:<?= $t['fondo'] ?>">
                                <div class="tema-preview-side" style="background:<?= $t['side'] ?>">
                                    <span style="background:<?= $t['acento'] ?>"></span>
                                    <span></span><span></span><span></span><span></span>
                                </div>
                                <div class="tema-preview-main">
                                    <div class="tema-preview-top"></div>
                                    <div class="tema-preview-cards">
                                        <?php foreach ($t['chips'] as $c): ?>
                                        <div style="--c:<?= $c ?>"></div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="tema-preview-btn" style="background:<?= $t['acento'] ?>"></div>
                                </div>
                                <?php if ($clave === 'halloween'): ?>
                                <span class="tema-preview-deco" style="right:12px; bottom:6px;">🎃</span>
                                <span class="tema-preview-deco" style="right:52px; top:30px; font-size:1rem;">🦇</span>
                                <span class="tema-preview-deco" style="right:16px; top:52px; font-size:.8rem;">🦇</span>
                                <?php endif; ?>
                            </div>
                            <div class="tema-body">
                                <div class="cfg-item-icon d-flex align-items-center justify-content-center rounded-3"
                                     style="width:42px;height:42px;background:<?= $t['acento'] ?>;color:#fff;font-size:1.1rem;">
                                    <i class="bi <?= $t['icono'] ?>"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">
                                        <?= $t['nombre'] ?>
                                        <?php if ($temaActual === $clave): ?>
                                        <span class="badge bg-success-soft text-success tema-activo-badge ms-1">En uso</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small"><?= $t['desc'] ?></div>
                                </div>
                                <i class="bi bi-circle tema-check"></i>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/configuracion" class="btn btn-light">Volver</a>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-brush me-1"></i> Aplicar tema
                    </button>
                </div>
            </form>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
