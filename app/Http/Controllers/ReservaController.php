<?php
namespace App\Http\Controllers;

use App\Models\Reserva;

/**
 * Controlador de Reservas
 */
class ReservaController
{

    /**
     * Crear una reserva
     * @param int $vehiculoId ID del vehículo
     * @param array $accesorioIds IDs de accesorios seleccionados
     */
    public function store(int $vehiculoId, array $accesorioIds = []): array
    {
        if (!AuthController::hasRole('CLIENTE')) {
            return ['success' => false, 'error' => 'Debe iniciar sesión como cliente'];
        }

        $clienteId = AuthController::id();
        return Reserva::crear($clienteId, $vehiculoId, $accesorioIds);
    }

    /**
     * Listar reservas del cliente actual
     */
    public function misReservas(): array
    {
        if (!AuthController::hasRole('CLIENTE')) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        $reservas = Reserva::conVehiculo(AuthController::id());
        return [
            'success' => true,
            'reservas' => $reservas
        ];
    }

    /**
     * Cancelar una reserva
     */
    public function cancelar(int $reservaId): array
    {
        if (!AuthController::check()) {
            return ['success' => false, 'error' => 'Debe iniciar sesión'];
        }

        $reserva = Reserva::find($reservaId);
        if (!$reserva) {
            return ['success' => false, 'error' => 'Reserva no encontrada'];
        }

        // G1 Schema: Verificar propiedad a través de cotización -> cliente
        $userId = AuthController::id();
        $esAdmin = AuthController::hasRole('ADMINISTRADOR');

        if (!$esAdmin) {
            // Obtener el dniCliente de la cotización asociada a esta reserva
            $cotizacion = \App\Database::queryOne(
                "SELECT c.dniCliente FROM cotizaciones c 
                 JOIN reservas r ON r.idCotizacion = c.idCotizacion 
                 WHERE r.nroReserva = ?",
                [$reservaId]
            );

            if (!$cotizacion) {
                return ['success' => false, 'error' => 'Cotización no encontrada'];
            }

            // Verificar que el cliente actual es el dueño de la cotización
            $cliente = \App\Database::queryOne(
                "SELECT dniCliente FROM clientes WHERE idUsuario = ?",
                [$userId]
            );

            if (!$cliente || $cliente['dniCliente'] !== $cotizacion['dniCliente']) {
                return ['success' => false, 'error' => 'No autorizado'];
            }
        }

        return $reserva->cancelar();
    }

    /**
     * Mostrar detalle de una reserva
     */
    public function show(int $id): array
    {
        $reserva = Reserva::find($id);

        if (!$reserva) {
            return ['success' => false, 'error' => 'Reserva no encontrada'];
        }

        return [
            'success' => true,
            'reserva' => $reserva->toArray()
        ];
    }
}
