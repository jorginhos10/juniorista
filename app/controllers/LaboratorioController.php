<?php

require_once ROOT . '/app/controllers/TratamientoController.php';
require_once ROOT . '/app/models/LaboratorioModel.php';

/**
 * Laboratorios: misma pantalla que Tratamiento (listado con pestañas y abonos), con su propia tabla.
 * Cada examen lleva un costo (suma del catálogo configurado) y el valor cobrado no puede ser menor.
 */
class LaboratorioController extends TratamientoController {

    protected array $modulo = [
        'ruta'        => 'laboratorio',
        'titulo'      => 'Laboratorios',
        'tituloLista' => 'Laboratorios',
        'subtitulo'   => 'Registro de exámenes y sus abonos',
        'singular'    => 'examen',
        'plural'      => 'exámenes',
        'icono'       => 'eyedropper',
        'prefijo'     => 'LAB',
        'catalogo'    => true,
        'placeholder' => 'Ej: Hemograma, perfil renal, coprológico, raspado de piel…',
    ];

    protected function crearModelo(): TratamientoModel {
        return new LaboratorioModel();
    }

    /** Catálogo de exámenes (con su costo) y las órdenes de los doctores que caja aún no registra */
    protected function datosExtraLista(int $veterinaria_id): array {
        return [
            'examenes' => $this->model->getExamenes($veterinaria_id),
            'ordenes'  => $this->model->getOrdenes($veterinaria_id, null, 'pendiente'),
        ];
    }

    /** Si el registro viene de una orden del doctor, ya no puede registrarse otra vez */
    protected function despuesDeCrear(int $id, array $datos): void {
        $orden = (int)($_POST['orden_id'] ?? 0);
        if ($orden > 0) $this->model->cambiarEstadoOrden($orden, (int)$datos['veterinaria_id'], 'atendida', $id);
    }

    /** POST: caja descarta una orden del doctor (no genera cobro) */
    public function descartarOrden(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('laboratorio');
        $veterinaria_id = $this->veterinariaActual($this->vetModel->getAll());
        $ok = $this->model->cambiarEstadoOrden((int)($_POST['id'] ?? 0), $veterinaria_id, 'anulada');
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Orden descartada.' : 'No se pudo descartar la orden (ya fue atendida o anulada).';
        $this->redirect('laboratorio');
    }

    /**
     * Costo del examen = suma de los exámenes elegidos del catálogo (se recalcula aquí, no se confía en el navegador).
     * Si al editar no se vuelve a elegir ninguno, se conserva el costo guardado. El valor no puede ser menor al costo.
     */
    protected function ajustarDatos(array &$datos, int $veterinaria_id, int $id): string {
        $orden = (int)($_POST['orden_id'] ?? 0);
        if ($id === 0 && $orden > 0) {
            $o = $this->model->getOrden($orden, $veterinaria_id);
            if (!$o || $o['estado'] !== 'pendiente') return 'Esa orden ya fue atendida o anulada.';
        }

        $ids = array_map('intval', (array)($_POST['examenes'] ?? []));
        if ($ids) {
            $seleccion = $this->model->getExamenesPorIds($veterinaria_id, $ids);
            if ($error = $this->model->validarSeleccion($seleccion)) return $error;
            $costo = round(array_sum(array_map(fn($e) => (float)$e['costo'], $seleccion)), 2);
        } else {
            $costo = $id > 0 ? $this->model->getCosto($id, $veterinaria_id) : 0.0;
        }

        if ($datos['valor'] + 0.001 < $costo) {
            return 'El valor no puede ser menor al costo del laboratorio ($' . number_format($costo, 2) . ').';
        }
        $datos['costo'] = $costo;
        return '';
    }
}
