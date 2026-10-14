<?php

require_once ROOT . '/app/controllers/TratamientoController.php';
require_once ROOT . '/app/models/LaboratorioModel.php';

/** Registro diario de laboratorios: misma pantalla por día que Peluquería, con su propia tabla y textos. */
class LaboratorioController extends TratamientoController {

    protected array $modulo = [
        'ruta'        => 'laboratorio',
        'titulo'      => 'Laboratorios',
        'singular'    => 'examen',
        'plural'      => 'exámenes',
        'icono'       => 'eyedropper',
        'placeholder' => 'Ej: Hemograma, perfil renal, coprológico, raspado de piel…',
    ];

    protected function crearModelo(): TratamientoModel {
        return new LaboratorioModel();
    }
}
