<?php
namespace App\Http\Controllers;

use App\Models\Vehiculo;

/**
 * Controlador de Vehículos
 */
class VehiculoController
{

    /**
     * Listar todos los vehículos disponibles
     */
    public function index(): array
    {
        $vehiculos = Vehiculo::disponibles();
        return [
            'success' => true,
            'vehiculos' => array_map(fn($v) => $v->toArray(), $vehiculos)
        ];
    }

    /**
     * Filtrar vehículos
     */
    public function filtrar(?string $marca, ?string $tipo, ?float $precioMin, ?float $precioMax): array
    {
        $vehiculos = Vehiculo::filtrar($marca, $tipo, $precioMin, $precioMax);
        return [
            'success' => true,
            'vehiculos' => array_map(fn($v) => $v->toArray(), $vehiculos)
        ];
    }

    /**
     * Mostrar detalle de un vehículo
     */
    public function show(int $id): array
    {
        $vehiculo = Vehiculo::find($id);

        if (!$vehiculo) {
            return ['success' => false, 'error' => 'Vehículo no encontrado'];
        }

        return [
            'success' => true,
            'vehiculo' => $vehiculo->toArray()
        ];
    }

    /**
     * Crear nuevo vehículo (admin)
     */
    public function store(array $datos): array
    {
        if (!AuthController::hasRole('ADMINISTRADOR')) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        $vehiculo = Vehiculo::create([
            'marca' => $datos['marca'],
            'modelo' => $datos['modelo'],
            'anio' => $datos['anio'],
            'precio' => $datos['precio'],
            'estado' => 'DISPONIBLE',
            'tipo' => $datos['tipo'],
            'kilometraje' => $datos['kilometraje'] ?? 0,
            'color' => $datos['color'] ?? '',
            'descripcion' => $datos['descripcion'] ?? '',
            'imagen_url' => $datos['imagen_url'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'success' => true,
            'vehiculo_id' => $vehiculo->id
        ];
    }
}
