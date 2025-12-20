<?php
namespace App\Http\Controllers;

use App\Models\Venta;

/**
 * Controlador de Ventas
 */
class VentaController
{

    /**
     * Realizar una venta desde una reserva (G1 flow)
     */
    public function store(array $datos): array
    {
        if (!AuthController::hasRole('VENDEDOR')) {
            return ['success' => false, 'error' => 'Solo los vendedores pueden realizar ventas'];
        }

        $vendedorId = AuthController::id();

        // G1 Schema: la venta se realiza desde una reserva
        if (!isset($datos['reserva_id']) || empty($datos['reserva_id'])) {
            return ['success' => false, 'error' => 'Se requiere el ID de la reserva'];
        }

        return Venta::realizarDesdeReserva((int) $datos['reserva_id'], $vendedorId);
    }

    /**
     * Listar ventas del vendedor actual
     */
    public function misVentas(): array
    {
        if (!AuthController::hasRole('VENDEDOR')) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        // G1 Schema: obtener dniVendedor desde tabla vendedores usando idUsuario
        $vendedor = \App\Database::queryOne(
            "SELECT dniVendedor FROM vendedores WHERE idUsuario = ?",
            [AuthController::id()]
        );

        $ventas = $vendedor ? Venta::conDetalles($vendedor['dniVendedor']) : [];
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

        // G1 Schema: obtener dniCliente desde tabla clientes usando idUsuario
        $cliente = \App\Database::queryOne(
            "SELECT dniCliente FROM clientes WHERE idUsuario = ?",
            [AuthController::id()]
        );

        $compras = $cliente ? Venta::comprasCliente($cliente['dniCliente']) : [];
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
