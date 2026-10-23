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

    /** Catálogo de exámenes de la sucursal (con su costo) para elegirlos en el formulario */
    protected function datosExtraLista(int $veterinaria_id): array {
        return ['examenes' => $this->model->getExamenes($veterinaria_id)];
    }

    /**
     * Costo del examen = suma de los exámenes elegidos del catálogo (se recalcula aquí, no se confía en el navegador).
     * Si al editar no se vuelve a elegir ninguno, se conserva el costo guardado. El valor no puede ser menor al costo.
     */
    protected function ajustarDatos(array &$datos, int $veterinaria_id, int $id): string {
        $ids = array_map('intval', (array)($_POST['examenes'] ?? []));
        if ($ids) {
            $costo = round(array_sum(array_map(fn($e) => (float)$e['costo'], $this->model->getExamenesPorIds($veterinaria_id, $ids))), 2);
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
