<?php

require_once ROOT . '/app/controllers/TratamientoController.php';
require_once ROOT . '/app/models/PeluqueriaModel.php';

/** Registro diario de peluquería: misma pantalla que Tratamiento, con su propia tabla y textos. */
class PeluqueriaController extends TratamientoController {

    protected array $modulo = [
        'ruta'        => 'peluqueria',
        'titulo'      => 'Peluquería',
        'singular'    => 'servicio',
        'plural'      => 'servicios',
        'icono'       => 'scissors',
        'placeholder' => 'Ej: Baño y corte, corte de uñas, deslanado…',
    ];

    protected function crearModelo(): TratamientoModel {
        return new PeluqueriaModel();
    }

    /** Reparto negocio / peluquería del día y de la semana */
    protected function datosExtraDia(int $veterinaria_id, string $fecha, string $iniSemana, string $finSemana): array {
        $pct = $this->model->getPctSucursal($veterinaria_id);
        return ['reparto' => [
            'pct_peluqueria' => $pct,
            'pct_negocio'    => 100 - $pct,
            'dia'            => $this->model->getReparto($veterinaria_id, $fecha, $fecha),
            'semana'         => $this->model->getReparto($veterinaria_id, $iniSemana, $finSemana),
        ]];
    }
}
