// Mostrar/ocultar contraseña
const togglePassword = document.getElementById('togglePassword');
if (togglePassword) {
    togglePassword.addEventListener('click', () => {
        const input = document.getElementById('password');
        const icon  = document.getElementById('toggleIcon');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        icon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
}

// Loading state al enviar formulario
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
        const usuario  = document.getElementById('usuario').value.trim();
        const password = document.getElementById('password').value;

        if (!usuario || !password) return;

        const btn     = document.getElementById('btnLogin');
        const btnText = btn.querySelector('.btn-text');
        const btnLoad = btn.querySelector('.btn-loading');

        btn.disabled      = true;
        btnText.classList.add('d-none');
        btnLoad.classList.remove('d-none');
    });
}

// Rellenar credenciales de demo
function fillDemo(usuario) {
    const usuarioInput = document.getElementById('usuario');
    const passInput    = document.getElementById('password');
    if (usuarioInput) usuarioInput.value = usuario;
    if (passInput)    passInput.value    = 'password';
    if (usuarioInput) usuarioInput.focus();
}
