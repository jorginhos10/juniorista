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
        const email    = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if (!email || !password) return;

        const btn     = document.getElementById('btnLogin');
        const btnText = btn.querySelector('.btn-text');
        const btnLoad = btn.querySelector('.btn-loading');

        btn.disabled      = true;
        btnText.classList.add('d-none');
        btnLoad.classList.remove('d-none');
    });
}

// Rellenar credenciales de demo
function fillDemo(email) {
    const emailInput = document.getElementById('email');
    const passInput  = document.getElementById('password');
    if (emailInput) emailInput.value = email;
    if (passInput)  passInput.value  = 'password';
    if (emailInput) emailInput.focus();
}
