<?php

require_once ROOT . '/app/models/TratamientoModel.php';

/** Registro diario de laboratorios (exámenes): mismos campos que peluquería, en su propia tabla y con recaudo directo. */
class LaboratorioModel extends TratamientoModel {

    protected string $tabla = 'laboratorios';

    // Los exámenes de laboratorio se cobran completos el mismo día
    protected bool $conAbonos = false;
}
