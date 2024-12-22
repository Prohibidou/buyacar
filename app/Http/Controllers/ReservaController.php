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
     */
    public function store(int $vehiculoId): array
    {
        if (!AuthController::hasRole('CLIENTE')) {
            return ['success' => false, 'error' => 'Debe iniciar sesión como cliente'];
        }

        $clienteId = AuthController::id();
        return Reserva::crear($clienteId, $vehiculoId);
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

        // Verificar que la reserva pertenece al cliente actual
        if ($reserva->cliente_id != AuthController::id() && !AuthController::hasRole('ADMINISTRADOR')) {
            return ['success' => false, 'error' => 'No autorizado'];
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
