<?php
namespace App\Http\Controllers;

use App\Models\Venta;

/**
 * Controlador de Ventas
 */
class VentaController
{

    /**
     * Realizar una venta
     */
    public function store(array $datos): array
    {
        if (!AuthController::hasRole('VENDEDOR')) {
            return ['success' => false, 'error' => 'Solo los vendedores pueden realizar ventas'];
        }

        $vendedorId = AuthController::id();

        return Venta::realizar(
            (int) $datos['cliente_id'],
            $vendedorId,
            (int) $datos['vehiculo_id'],
            $datos['metodo_pago'],
            isset($datos['reserva_id']) ? (int) $datos['reserva_id'] : null
        );
    }

    /**
     * Listar ventas del vendedor actual
     */
    public function misVentas(): array
    {
        if (!AuthController::hasRole('VENDEDOR')) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        $ventas = Venta::conDetalles(AuthController::id());
        return [
            'success' => true,
            'ventas' => $ventas
        ];
    }

    /**
     * Listar compras del cliente actual
     */
    public function misCompras(): array
    {
        if (!AuthController::hasRole('CLIENTE')) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        $compras = Venta::comprasCliente(AuthController::id());
        return [
            'success' => true,
            'compras' => $compras
        ];
    }

    /**
     * Listar todas las ventas (admin)
     */
    public function index(): array
    {
        if (!AuthController::hasRole('ADMINISTRADOR')) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        $ventas = Venta::conDetalles();
        return [
            'success' => true,
            'ventas' => $ventas
        ];
    }

    /**
     * Mostrar detalle de una venta
     */
    public function show(int $id): array
    {
        $venta = Venta::find($id);

        if (!$venta) {
            return ['success' => false, 'error' => 'Venta no encontrada'];
        }

        return [
            'success' => true,
            'venta' => $venta->toArray()
        ];
    }
}
