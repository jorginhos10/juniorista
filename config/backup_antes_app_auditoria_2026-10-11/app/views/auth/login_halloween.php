<?php
// Login con la apariencia Halloween (traído del login de Violet). Se usa cuando la cookie pc_tema = halloween.
$batPath = 'M27 1L29 5Q30 4.4 31 5L33 1L34 6C40 3 50 2 59 8Q55 10 51 16Q46 11 43 18Q39 13 35 21L32 24Q30 28 28 24L25 21Q21 13 17 18Q14 11 9 16Q5 10 1 8C10 2 20 3 26 6Z';
$webPath = 'M0 0L100.0 0.0M0 0L96.6 25.9M0 0L86.6 50.0M0 0L70.7 70.7M0 0L50.0 86.6M0 0L25.9 96.6M0 0L0.0 100.0M20.0 0.0Q15.9 2.1 19.3 5.2Q14.8 6.1 17.3 10.0Q12.7 9.7 14.1 14.1Q9.7 12.7 10.0 17.3Q6.1 14.8 5.2 19.3Q2.1 15.9 0.0 20.0M38.0 0.0Q30.1 4.0 36.7 9.8Q28.1 11.6 32.9 19.0Q24.1 18.5 26.9 26.9Q18.5 24.1 19.0 32.9Q11.6 28.1 9.8 36.7Q4.0 30.1 0.0 38.0M56.0 0.0Q44.4 5.8 54.1 14.5Q41.4 17.1 48.5 28.0Q35.5 27.3 39.6 39.6Q27.3 35.5 28.0 48.5Q17.1 41.4 14.5 54.1Q5.8 44.4 0.0 56.0M74.0 0.0Q58.7 7.7 71.5 19.2Q54.7 22.7 64.1 37.0Q47.0 36.0 52.3 52.3Q36.0 47.0 37.0 64.1Q22.7 54.7 19.2 71.5Q7.7 58.7 0.0 74.0M92.0 0.0Q73.0 9.6 88.9 23.8Q68.0 28.2 79.7 46.0Q58.4 44.8 65.1 65.1Q44.8 58.4 46.0 79.7Q28.2 68.0 23.8 88.9Q9.6 73.0 0.0 92.0';
$check   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="m8.5 12.5 2.2 2.2 4.8-4.8" /></svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetControl &mdash; Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Creepster&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/login-halloween.css" rel="stylesheet">
</head>
<body class="hwl-body">
<div class="hwl">

    <!-- Panel izquierdo -->
    <div class="hwl__brand">
        <div class="hwl__scene" aria-hidden="true">
            <div class="hwl__stars"></div>

            <svg class="hwl__moon" viewBox="0 0 240 240">
                <circle cx="120" cy="120" r="50" fill="#ffe6a0" />
                <circle cx="108" cy="108" r="30" fill="#fffbe6" opacity=".3" />
                <circle cx="120" cy="120" r="49" fill="none" stroke="#f5c65e" stroke-width="2" opacity=".55" />
                <circle cx="104" cy="106" r="4.5" fill="#e9b94f" opacity=".28" />
                <circle cx="132" cy="128" r="6" fill="#e9b94f" opacity=".24" />
                <circle cx="140" cy="98" r="2.5" fill="#e9b94f" opacity=".3" />
                <circle cx="110" cy="140" r="3" fill="#e9b94f" opacity=".26" />
            </svg>

            <svg class="hwl__castle" viewBox="0 -24 390 174">
                <g fill="#120a1e">
                    <rect x="150" y="70" width="92" height="70" />
                    <rect x="150" y="62" width="8" height="10" /><rect x="164" y="62" width="8" height="10" /><rect x="178" y="62" width="8" height="10" />
                    <rect x="192" y="62" width="8" height="10" /><rect x="206" y="62" width="8" height="10" /><rect x="220" y="62" width="8" height="10" />
                    <rect x="234" y="62" width="8" height="10" />
                    <rect x="128" y="52" width="26" height="88" /><polygon points="124,52 141,20 158,52" />
                    <rect x="238" y="52" width="26" height="88" /><polygon points="234,52 251,20 268,52" />
                    <rect x="176" y="32" width="38" height="108" /><polygon points="171,32 195,-4 219,32" />
                    <rect x="194" y="-22" width="2" height="20" /><polygon points="196,-22 210,-17 196,-12" />
                    <rect x="100" y="88" width="30" height="52" /><polygon points="96,88 115,64 134,88" />
                    <rect x="262" y="90" width="26" height="50" /><polygon points="258,90 275,68 292,90" />
                    <path d="M0 150V120Q100 96 195 118Q290 96 390 122V150Z" />
                </g>
                <g fill="#ff9a3c">
                    <rect class="win" x="191" y="52" width="8" height="14" rx="4" style="animation-delay: 0s" />
                    <rect class="win" x="137" y="74" width="6" height="12" rx="3" style="animation-delay: .7s" />
                    <rect class="win" x="245" y="74" width="6" height="12" rx="3" style="animation-delay: 1.4s" />
                    <rect class="win" x="160" y="92" width="7" height="12" rx="3.5" style="animation-delay: .3s" />
                    <rect class="win" x="222" y="92" width="7" height="12" rx="3.5" style="animation-delay: 1s" />
                    <rect class="win" x="108" y="104" width="6" height="11" rx="3" style="animation-delay: 1.8s" />
                    <rect class="win" x="271" y="106" width="6" height="11" rx="3" style="animation-delay: .5s" />
                    <rect class="win" x="191" y="98" width="8" height="14" rx="4" style="animation-delay: 1.2s" />
                </g>
            </svg>

            <svg class="hwl__wolf" viewBox="0 0 130 122" fill="#07030c">
                <path d="M0 122Q18 106 60 110T130 116V122Z" />
                <path d="M12 6L36 20L42 2L50 18L52 18L60 5L62 24Q68 34 70 48C84 48 100 52 108 64C116 72 118 82 113 92C120 96 126 104 127 110C118 108 112 102 106 100L105 112L91 112L92 100C80 94 66 93 58 94L60 112L46 112L45 98C40 86 36 74 33 64C30 54 30 44 31 38L30 32L20 27L15 20L24 16L14 12Z" />
            </svg>

            <svg class="hwl__bat hwl__bat--a" viewBox="0 0 60 28"><path fill="currentColor" d="<?= $batPath ?>" /></svg>
            <svg class="hwl__bat hwl__bat--b" viewBox="0 0 60 28"><path fill="currentColor" d="<?= $batPath ?>" /></svg>
            <svg class="hwl__bat hwl__bat--c" viewBox="0 0 60 28"><path fill="currentColor" d="<?= $batPath ?>" /></svg>

            <svg class="hwl__web hwl__web--tl" viewBox="0 0 100 100" fill="none" stroke-linecap="round"><path d="<?= $webPath ?>" /></svg>
        </div>

        <div class="hwl__brand-content">
            <div class="hwl__brandmark">
                <div class="hwl__brandmark-icon" aria-hidden="true">🎃</div>
                <div class="hwl__brandmark-name">PetControl</div>
            </div>

            <p class="hwl__subtitle">Sistema de gestión veterinaria integral para el cuidado de tus mascotas.</p>

            <ul class="hwl__features">
                <li><?= $check ?> Historial médico completo</li>
                <li><?= $check ?> Gestión de citas y turnos</li>
                <li><?= $check ?> Control de inventario</li>
                <li><?= $check ?> Facturación y reportes</li>
            </ul>
        </div>
    </div>

    <!-- Panel derecho -->
    <div class="hwl__form-side">
        <svg class="hwl__web hwl__web--tr" viewBox="0 0 100 100" fill="none" stroke-linecap="round" aria-hidden="true"><path d="<?= $webPath ?>" /></svg>

        <svg class="hwl__spider" viewBox="0 0 44 90" aria-hidden="true">
            <line x1="22" y1="0" x2="22" y2="58" stroke="rgba(234,220,255,.6)" stroke-width="1" />
            <path d="M17 58Q5 48 1 65M27 58Q39 48 43 65M17 62.5Q5 55.5 1 72.5M27 62.5Q39 55.5 43 72.5M17 67Q5 63 1 80M27 67Q39 63 43 80M17 71.5Q5 70.5 1 87.5M27 71.5Q39 70.5 43 87.5" stroke="#b78bff" stroke-width="1.6" fill="none" stroke-linecap="round" />
            <ellipse cx="22" cy="72" rx="9" ry="10.5" fill="#2b1646" stroke="#b78bff" stroke-width="1" />
            <path d="M22 67.5 l-2.6 5 l2.6 5 l2.6 -5 z" fill="#ff5a1f" />
            <circle cx="22" cy="60" r="5.5" fill="#2b1646" stroke="#b78bff" stroke-width="1" />
            <circle cx="19.8" cy="59" r="1.4" fill="#ffb347" />
            <circle cx="24.2" cy="59" r="1.4" fill="#ffb347" />
        </svg>

        <form class="hwl__form" method="POST" action="<?= BASE_URL ?>/" id="loginForm" novalidate>
            <h2 class="hwl__form-title">Bienvenido de vuelta</h2>
            <p class="hwl__form-subtitle">Ingresa tus credenciales para continuar</p>

            <?php if (!empty($error)): ?>
            <div class="hwl__alert" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /><path d="M12 9v4M12 17h.01" /></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <div class="hwl__field">
                <label for="email">Correo electrónico</label>
                <div class="hwl__control">
                    <svg class="hwl__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></svg>
                    <input type="email" id="email" name="email" placeholder="correo@ejemplo.com"
                           class="<?= !empty($error) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($email) ?>" autocomplete="email" required autofocus>
                </div>
            </div>

            <div class="hwl__field">
                <label for="password">Contraseña</label>
                <div class="hwl__control">
                    <svg class="hwl__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" /></svg>
                    <input type="password" id="password" name="password" placeholder="Tu contraseña"
                           autocomplete="current-password" required>
                    <button type="button" class="hwl__toggle" id="toggleClave" aria-label="Mostrar contraseña">
                        <svg class="ojo-abierto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8-10-8-10-8Z" /><circle cx="12" cy="12" r="3" /></svg>
                        <svg class="ojo-cerrado" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-6 0-10-6-10-8a13.4 13.4 0 0 1 3.17-4.4M9.9 4.24A10.6 10.6 0 0 1 12 4c6 0 10 6 10 8a13.5 13.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" /><path d="m1 1 22 22" /></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="hwl__submit" id="btnLogin">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="M10 17l5-5-5-5" /><path d="M15 12H3" /></svg>
                <span id="btnLoginTexto">Iniciar sesión</span>
            </button>

            <div class="hwl__demo">
                <p class="hwl__demo-label">Usuarios de prueba</p>
                <div class="hwl__demo-btns">
                    <button type="button" class="hwl__demo-btn" onclick="fillDemo('admin@petcontrol.com')">Admin</button>
                    <button type="button" class="hwl__demo-btn" onclick="fillDemo('veterinario@petcontrol.com')">Veterinario</button>
                    <button type="button" class="hwl__demo-btn" onclick="fillDemo('recepcion@petcontrol.com')">Recepción</button>
                </div>
                <p class="hwl__demo-label" style="margin:.6rem 0 0">Contraseña: <code>password</code></p>
            </div>

            <p class="hwl__footer">&copy; <?= date('Y') ?> PetControl. Todos los derechos reservados.</p>
        </form>
    </div>
</div>

<script>
document.getElementById('toggleClave').addEventListener('click', function () {
    var clave   = document.getElementById('password');
    var mostrar = clave.type === 'password';
    clave.type = mostrar ? 'text' : 'password';
    this.querySelector('.ojo-abierto').style.display = mostrar ? 'none' : '';
    this.querySelector('.ojo-cerrado').style.display = mostrar ? '' : 'none';
    this.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
});

document.getElementById('loginForm').addEventListener('submit', function () {
    if (!document.getElementById('email').value.trim() || !document.getElementById('password').value) return;
    document.getElementById('btnLogin').disabled = true;
    document.getElementById('btnLoginTexto').textContent = 'Ingresando…';
});

function fillDemo(email) {
    document.getElementById('email').value    = email;
    document.getElementById('password').value = 'password';
    document.getElementById('email').focus();
}
</script>
</body>
</html>
