<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Reserva
 */
class Reserva extends Model
{
    protected static string $table = 'reservas';

    protected array $fillable = [
        'cliente_id',
        'vehiculo_id',
        'fecha_expiracion',
        'estado',
        'monto_sena'
    ];

    const ESTADOS = ['ACTIVA', 'COMPLETADA', 'CANCELADA', 'VENCIDA'];
    const DIAS_VALIDEZ = 7;
    const PORCENTAJE_SENA = 5;

    public static function calcularSena(float $precioVehiculo): float
    {
        return $precioVehiculo * (self::PORCENTAJE_SENA / 100);
    }

    public function estaActiva(): bool
    {
        return $this->estado === 'ACTIVA';
    }

    public function estaVencida(): bool
    {
        return strtotime($this->fecha_expiracion) < time();
    }

    /**
     * Crear una nueva reserva
     */
    public static function crear(int $clienteId, int $vehiculoId): array
    {
        try {
            Database::beginTransaction();

            $vehiculo = Vehiculo::find($vehiculoId);
            if (!$vehiculo || !$vehiculo->estaDisponible()) {
                throw new \Exception("El vehículo no está disponible para reserva.");
            }

            $montoSena = self::calcularSena((float) $vehiculo->precio);
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+' . self::DIAS_VALIDEZ . ' days'));

            $reserva = self::create([
                'cliente_id' => $clienteId,
                'vehiculo_id' => $vehiculoId,
                'fecha_expiracion' => $fechaExpiracion,
                'estado' => 'ACTIVA',
                'monto_sena' => $montoSena,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $vehiculo->actualizarEstado('RESERVADO');

            Database::commit();

            return [
                'success' => true,
                'reserva_id' => $reserva->id,
                'monto_sena' => $montoSena,
                'fecha_expiracion' => $fechaExpiracion
            ];
        } catch (\Exception $e) {
            Database::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancelar reserva
     */
    public function cancelar(): array
    {
        try {
            Database::beginTransaction();

            if (!$this->estaActiva()) {
                throw new \Exception("La reserva no puede ser cancelada.");
            }

            $sql = "UPDATE reservas SET estado = 'CANCELADA', updated_at = NOW() WHERE id = ?";
            Database::query($sql, [$this->id]);

            $vehiculo = Vehiculo::find($this->vehiculo_id);
            $vehiculo->actualizarEstado('DISPONIBLE');

            Database::commit();

            return ['success' => true, 'monto_devuelto' => $this->monto_sena];
        } catch (\Exception $e) {
            Database::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Completar reserva (al concretar venta)
     */
    public function completar(): bool
    {
        $sql = "UPDATE reservas SET estado = 'COMPLETADA', updated_at = NOW() WHERE id = ?";
        Database::query($sql, [$this->id]);
        return true;
    }

    /**
     * Obtener reservas con datos de vehículo
     */
    public static function conVehiculo(int $clienteId): array
    {
        $sql = "SELECT r.*, v.marca, v.modelo, v.anio, v.precio as precio_vehiculo
                FROM reservas r
                JOIN vehiculos v ON r.vehiculo_id = v.id
                WHERE r.cliente_id = ?
                ORDER BY r.created_at DESC";
        $stmt = Database::query($sql, [$clienteId]);
        return $stmt->fetchAll();
    }

    public function getVehiculo(): ?Vehiculo
    {
        return Vehiculo::find($this->vehiculo_id);
    }

    public function getCliente(): ?Cliente
    {
        return Cliente::find($this->cliente_id);
    }
}
