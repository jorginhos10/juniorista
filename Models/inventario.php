<?php
class Inventario {
    public static function listar() {
        return [
            ['id' => 1, 'producto' => 'Teclado', 'cantidad' => 10],
            ['id' => 2, 'producto' => 'Mouse', 'cantidad' => 25],
            ['id' => 3, 'producto' => 'Monitor', 'cantidad' => 7],
        ];
    }
}