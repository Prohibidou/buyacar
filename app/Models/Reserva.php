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
     * @param int $clienteId ID del usuario cliente
     * @param int $vehiculoId ID del vehículo
     * @param array $accesorioIds IDs de accesorios seleccionados (opcional)
     */
    public static function crear(int $clienteId, int $vehiculoId, array $accesorioIds = []): array
    {
        try {
            Database::beginTransaction();

            $vehiculo = Vehiculo::find($vehiculoId);
            if (!$vehiculo || !$vehiculo->estaDisponible()) {
                throw new \Exception("El vehículo no está disponible para reserva.");
            }

            // Calcular precio base
            $precioVehiculo = (float) $vehiculo->precio;
            $idModelo = $vehiculo->idModelo;

            // Calcular precio de accesorios
            $precioAccesorios = 0.0;
            if (!empty($accesorioIds)) {
                $precioAccesorios = Accesorio::calcularPrecioAccesorios($idModelo, $accesorioIds);
            }

            // Calcular subtotal e IVA (21%)
            $subtotal = $precioVehiculo + $precioAccesorios;
            $iva = $subtotal * 0.21;
            $importeFinal = $subtotal + $iva;

            $montoSena = $importeFinal * (self::PORCENTAJE_SENA / 100);
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+' . self::DIAS_VALIDEZ . ' days'));

            // G1 Schema: Primero crear cotización
            $sqlCot = "INSERT INTO cotizaciones (fechaHoraGenerada, importeFinal, valida, fechaHoraVencimiento, dniCliente) 
                       SELECT NOW(), ?, 1, ?, dniCliente FROM clientes WHERE idUsuario = ?";
            Database::query($sqlCot, [$importeFinal, $fechaExpiracion, $clienteId]);
            $cotizacionId = Database::lastInsertId();

            // Agregar vehículo a la cotización
            $vehiculoIdDb = $vehiculo->idVehiculo ?? $vehiculoId;
            Database::query(
                "INSERT INTO cotizaciones_vehiculos (idCotizacion, idVehiculo) VALUES (?, ?)",
                [$cotizacionId, $vehiculoIdDb]
            );

            // Agregar accesorios a la cotización (tabla cotizaciones_vehiculos_accesorios)
            if (!empty($accesorioIds)) {
                foreach ($accesorioIds as $accId) {
                    Database::query(
                        "INSERT INTO cotizaciones_vehiculos_accesorios (idCotizacion, idVehiculo, idAccesorio) VALUES (?, ?, ?)",
                        [$cotizacionId, $vehiculoIdDb, $accId]
                    );
                }
            }

            // Crear reserva en G1 schema
            Database::query(
                "INSERT INTO reservas (fechaHoraGenerada, estadoReserva, importe, fechaHoraVencimiento, idCotizacion) 
                 VALUES (NOW(), 'ACTIVA', ?, ?, ?)",
                [$montoSena, $fechaExpiracion, $cotizacionId]
            );
            $reservaId = Database::lastInsertId();

            $vehiculo->actualizarEstado('RESERVADO');

            Database::commit();

            return [
                'success' => true,
                'reserva_id' => $reservaId,
                'cotizacion_id' => $cotizacionId,
                'monto_sena' => round($montoSena, 2),
                'fecha_expiracion' => $fechaExpiracion,
                'desglose' => [
                    'precio_vehiculo' => $precioVehiculo,
                    'precio_accesorios' => $precioAccesorios,
                    'subtotal' => $subtotal,
                    'iva_21' => round($iva, 2),
                    'total' => round($importeFinal, 2)
                ]
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
        // Alias column names to match JavaScript frontend expectations
        $sql = "SELECT r.nroReserva as id, r.estadoReserva as estado, 
                       r.fechaHoraGenerada as created_at, r.importe as monto_sena,
                       r.fechaHoraVencimiento as fecha_expiracion, r.idCotizacion,
                       v.idVehiculo, v.precio as precio_vehiculo, v.anio, v.descripcion,
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
