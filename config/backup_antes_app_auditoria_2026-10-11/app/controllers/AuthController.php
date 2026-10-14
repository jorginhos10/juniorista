<?php

require_once ROOT . '/app/models/UsuarioModel.php';

class AuthController {

    private UsuarioModel $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    public function login(): void {
        if ($this->estaAutenticado()) {
            $this->redirect('dashboard');
        }

        $datos = ['error' => '', 'email' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = $this->procesarLogin();
            if ($resultado['exitoso']) {
                $this->redirect('dashboard');
            }
            $datos['error'] = $resultado['error'];
            $datos['email'] = $resultado['email'];
        }

        // El tema se recuerda en una cookie para mostrar el login con la apariencia del último usuario
        $vista = ($_COOKIE['pc_tema'] ?? '') === 'halloween' ? 'auth/login_halloween' : 'auth/login';
        $this->render($vista, $datos);
    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
        $this->redirect('');
    }

    private function procesarLogin(): array {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return ['exitoso' => false, 'error' => 'Por favor completa todos los campos.', 'email' => $email];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['exitoso' => false, 'error' => 'El correo electrónico no es válido.', 'email' => $email];
        }

        $usuario = $this->model->findByEmail($email);

        if ($usuario && $this->model->verificarPassword($password, $usuario['password'])) {
            // Nuevo identificador de sesión al autenticarse (evita fijación de sesión) y nuevo token anti-CSRF
            session_regenerate_id(true);
            unset($_SESSION['csrf']);
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_rol']    = $usuario['rol'];
            $_SESSION['tema']           = $usuario['tema'] ?? 'original';
            self::recordarTema($_SESSION['tema']);

            // Última sucursal usada; si no está entre las asignadas, el router toma la primera permitida
            $_SESSION['veterinaria_id'] = (int)($usuario['ultima_veterinaria'] ?? 0);
            return ['exitoso' => true, 'error' => '', 'email' => $email];
        }

        return ['exitoso' => false, 'error' => 'Correo o contraseña incorrectos.', 'email' => $email];
    }

    public static function recordarTema(string $tema): void {
        setcookie('pc_tema', $tema, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => BASE_URL . '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function estaAutenticado(): bool {
        return isset($_SESSION['usuario_id']);
    }

    private function redirect(string $ruta): void {
        header('Location: ' . BASE_URL . '/' . $ruta);
        exit;
    }

    private function render(string $vista, array $datos = []): void {
        extract($datos);
        require ROOT . '/app/views/' . $vista . '.php';
    }
}
