<?php

require_once ROOT . '/app/models/VeterinariaModel.php';
require_once ROOT . '/app/models/CitaModel.php';

class DashboardController {

    private VeterinariaModel $vetModel;

    public function __construct() {
        $this->requiereAutenticacion();
        $this->vetModel = new VeterinariaModel();
    }

    public function index(): void {
        $veterinarias   = $this->vetModel->getAll();
        $veterinaria_id = (int)($_SESSION['veterinaria_id'] ?? 0);
        if ($veterinaria_id === 0 && !empty($veterinarias)) {
            $veterinaria_id = (int)$veterinarias[0]['id'];
            $_SESSION['veterinaria_id'] = $veterinaria_id;
        }

        $datos = [
            'activePage'     => 'dashboard',
            'veterinarias'   => $veterinarias,
            'veterinaria_id' => $veterinaria_id,
            'citasHoy'       => (new CitaModel())->getRango($veterinaria_id, date('Y-m-d'), date('Y-m-d')),
            'totalMascotas'  => $this->contar('SELECT COUNT(*) FROM mascotas WHERE activo = 1 AND veterinaria_id = ' . $veterinaria_id),
            'totalClientes'  => $this->contar('SELECT COUNT(*) FROM clientes WHERE activo = 1 AND veterinaria_id = ' . $veterinaria_id),
            'ingresosMes'    => $this->ingresosMes($veterinaria_id),
            'usuario' => [
                'nombre' => $_SESSION['usuario_nombre'],
                'email'  => $_SESSION['usuario_email'],
                'rol'    => $_SESSION['usuario_rol'],
            ],
            // Mensajes de otras pantallas que redirigen aquí (p. ej. módulo sin permiso)
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error']   ?? '',
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $this->render('dashboard/index', $datos);
    }

    private function contar(string $sql): int {
        require_once ROOT . '/config/Database.php';
        return (int)(Database::getInstance()->getConnection()->query($sql)->fetch_row()[0] ?? 0);
    }

    /** Ingresos del mes en curso: ventas no anuladas + peluquería + abonos de tratamientos (lo mismo que el balance del reporte) */
    private function ingresosMes(int $veterinaria_id): float {
        require_once ROOT . '/app/models/VentaModel.php';
        require_once ROOT . '/app/models/PeluqueriaModel.php';
        require_once ROOT . '/app/models/LaboratorioModel.php';
        $desde = date('Y-m-01');
        $hasta = date('Y-m-d');
        return (float)((new VentaModel())->getResumenReporte($veterinaria_id, $desde, $hasta)['total_ingresos'] ?? 0)
             + (new PeluqueriaModel())->getTotalRango($veterinaria_id, $desde, $hasta)['negocio']
             + (new LaboratorioModel())->getTotalRango($veterinaria_id, $desde, $hasta)['total']
             + (new TratamientoModel())->getTotalRango($veterinaria_id, $desde, $hasta)['total'];
    }

    private function requiereAutenticacion(): void {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    private function render(string $vista, array $datos = []): void {
        extract($datos);
        require ROOT . '/app/views/' . $vista . '.php';
    }
}
