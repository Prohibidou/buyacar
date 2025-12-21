<?php
namespace App\Http\Controllers;

use App\Models\Accesorio;

/**
 * Controlador de Accesorios
 */
class AccesorioController
{
    /**
     * Listar todos los accesorios disponibles
     */
    public function index(): array
    {
        $accesorios = Accesorio::disponibles();
        return [
            'success' => true,
            'accesorios' => array_map(fn($a) => $a->toArray(), $accesorios)
        ];
    }

    /**
     * Listar accesorios disponibles para un modelo específico (con precios)
     */
    public function porModelo(int $idModelo): array
    {
        $accesorios = Accesorio::porModelo($idModelo);
        return [
            'success' => true,
            'accesorios' => $accesorios
        ];
    }
}
