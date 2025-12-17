<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Reserva - Compatible con G1 Schema
 * 
 * G1 Schema: Reservas tiene nroReserva, fechaHoraGenerada, estadoReserva, importe, 
 * fechaHoraVencimiento, idCotizacion(FK), nroPago(FK)
 */
class Reserva extends Model
{
    protected static string $table = 'reservas';

    protected array $fillable = [
        'fechaHoraGenerada',
        'estadoReserva',
        'importe',
        'fechaHoraVencimiento',
        'idCotizacion',
        'nroPago'
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
        $estado = $this->estadoReserva ?? $this->estado ?? 'ACTIVA';
        return $estado === 'ACTIVA';
    }

    public function estaVencida(): bool
    {
        $fecha = $this->fechaHoraVencimiento ?? $this->fecha_expiracion;
        return strtotime($fecha) < time();
    }

    /**
     * Crear una nueva reserva (G1: a partir de una cotización)
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

            // G1 Schema: Primero crear cotización, luego reserva
            // Por simplicidad, creamos una cotización automática
            $sqlCot = "INSERT INTO cotizaciones (fechaHoraGenerada, importeFinal, valida, fechaHoraVencimiento, dniCliente) 
                       SELECT NOW(), ?, 1, ?, dniCliente FROM clientes WHERE idUsuario = ?";
            Database::query($sqlCot, [$vehiculo->precio, $fechaExpiracion, $clienteId]);
            $cotizacionId = Database::lastInsertId();

            // Agregar vehículo a la cotización
            $sqlCotVeh = "INSERT INTO cotizaciones_vehiculos (idCotizacion, idVehiculo) VALUES (?, ?)";
            $vehiculoIdDb = $vehiculo->idVehiculo ?? $vehiculoId;
            Database::query($sqlCotVeh, [$cotizacionId, $vehiculoIdDb]);

            // Crear reserva en G1 schema
            $sql = "INSERT INTO reservas (fechaHoraGenerada, estadoReserva, importe, fechaHoraVencimiento, idCotizacion) 
                    VALUES (NOW(), 'ACTIVA', ?, ?, ?)";
            Database::query($sql, [$montoSena, $fechaExpiracion, $cotizacionId]);
            $reservaId = Database::lastInsertId();

            $vehiculo->actualizarEstado('RESERVADO');

            Database::commit();

            return [
                'success' => true,
                'reserva_id' => $reservaId,
                'cotizacion_id' => $cotizacionId,
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

            $id = $this->nroReserva ?? $this->id;
            $sql = "UPDATE reservas SET estadoReserva = 'CANCELADA' WHERE nroReserva = ?";
            Database::query($sql, [$id]);

            // Liberar vehículos de la cotización
            $cotId = $this->idCotizacion ?? $this->cotizacion_id;
            $vehiculosResult = Database::query("SELECT idVehiculo FROM cotizaciones_vehiculos WHERE idCotizacion = ?", [$cotId]);
            foreach ($vehiculosResult->fetchAll() as $v) {
                $vehiculo = Vehiculo::find($v['idVehiculo']);
                if ($vehiculo) {
                    $vehiculo->actualizarEstado('DISPONIBLE');
                }
            }

            Database::commit();

            $importe = $this->importe ?? $this->monto_sena ?? 0;
            return ['success' => true, 'monto_devuelto' => $importe];
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
        $id = $this->nroReserva ?? $this->id;
        $sql = "UPDATE reservas SET estadoReserva = 'COMPLETADA' WHERE nroReserva = ?";
        Database::query($sql, [$id]);
        return true;
    }

    /**
     * Obtener reservas con datos de vehículo (G1 Schema)
     */
    public static function conVehiculo(int $clienteId): array
    {
        // G1: Reservas → Cotizaciones → Cotizaciones_Vehiculos → Vehiculos
        $sql = "SELECT r.*, v.idVehiculo, v.precio as precio_vehiculo, v.anio, v.descripcion,
                       mo.nombre as modelo, ma.nombre as marca
                FROM reservas r
                JOIN cotizaciones c ON r.idCotizacion = c.idCotizacion
                JOIN clientes cl ON c.dniCliente = cl.dniCliente
                LEFT JOIN cotizaciones_vehiculos cv ON c.idCotizacion = cv.idCotizacion
                LEFT JOIN vehiculos v ON cv.idVehiculo = v.idVehiculo
                LEFT JOIN modelos mo ON v.idModelo = mo.idModelo
                LEFT JOIN marcas ma ON mo.idMarca = ma.idMarca
                WHERE cl.idUsuario = ?
                ORDER BY r.fechaHoraGenerada DESC";
        $stmt = Database::query($sql, [$clienteId]);
        return $stmt->fetchAll();
    }

    public static function find($id): ?self
    {
        $sql = "SELECT * FROM reservas WHERE nroReserva = ?";
        $row = Database::queryOne($sql, [$id]);
        return $row ? new self($row) : null;
    }
}
