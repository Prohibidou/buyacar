<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Venta - Compatible con G1 Schema
 * 
 * G1 Schema: Ventas = idVenta(PK), fechaHoraGenerada, concretada, comision, nroPago(FK), idCotizacion(FK), dniVendedor(FK)
 */
class Venta extends Model
{
    protected static string $table = 'ventas';

    protected array $fillable = [
        'idCotizacion',
        'dniVendedor',
        'comision',
        'concretada',
        'nroPago'
    ];

    /**
     * Realizar una venta desde una reserva (flujo G1)
     * La reserva está vinculada a una cotización, y la venta se crea desde esa cotización
     */
    public static function realizarDesdeReserva(int $reservaId, int $vendedorId): array
    {
        try {
            Database::beginTransaction();

            // Buscar la reserva y su cotización
            $reserva = Database::queryOne(
                "SELECT r.*, c.idCotizacion, c.importeFinal, c.dniCliente 
                 FROM reservas r 
                 JOIN cotizaciones c ON r.idCotizacion = c.idCotizacion 
                 WHERE r.nroReserva = ? AND r.estadoReserva = 'ACTIVA'",
                [$reservaId]
            );

            if (!$reserva) {
                throw new \Exception("Reserva no encontrada o no está activa.");
            }

            // Obtener el vendedor
            $vendedor = Vendedor::find($vendedorId);
            if (!$vendedor) {
                throw new \Exception("Vendedor no válido.");
            }

            // Calcular comisión (10% del importe según PDF CU-4)
            $comision = $reserva['importeFinal'] * 0.10;

            // Crear la venta
            $sql = "INSERT INTO ventas (fechaHoraGenerada, concretada, comision, idCotizacion, dniVendedor) 
                    VALUES (NOW(), TRUE, ?, ?, ?)";
            Database::query($sql, [$comision, $reserva['idCotizacion'], $vendedor->getDni()]);
            $ventaId = Database::lastInsertId();

            // Actualizar estado de la reserva a COMPLETADA
            Database::query(
                "UPDATE reservas SET estadoReserva = 'COMPLETADA' WHERE nroReserva = ?",
                [$reservaId]
            );

            // Actualizar estado del vehículo a VENDIDO
            $vehiculo = Database::queryOne(
                "SELECT cv.idVehiculo FROM cotizaciones_vehiculos cv WHERE cv.idCotizacion = ?",
                [$reserva['idCotizacion']]
            );
            if ($vehiculo) {
                Database::query(
                    "UPDATE vehiculos SET estadoVehiculo = 'VENDIDO' WHERE idVehiculo = ?",
                    [$vehiculo['idVehiculo']]
                );
            }

            Database::commit();

            return [
                'success' => true,
                'venta_id' => $ventaId,
                'comision' => $comision,
                'importe_final' => $reserva['importeFinal']
            ];
        } catch (\Exception $e) {
            Database::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener ventas con datos relacionados (para vendedor)
     */
    public static function conDetalles(?string $dniVendedor = null): array
    {
        $sql = "SELECT v.idVenta as id, 
                       v.fechaHoraGenerada as created_at,
                       v.comision as comision_vendedor,
                       c.importeFinal as precio_final, 
                       c.dniCliente,
                       CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
                       mo.nombre as modelo, 
                       ma.nombre as marca,
                       ve.anio
                FROM ventas v
                JOIN cotizaciones c ON v.idCotizacion = c.idCotizacion
                JOIN clientes cl ON c.dniCliente = cl.dniCliente
                JOIN cotizaciones_vehiculos cv ON c.idCotizacion = cv.idCotizacion
                JOIN vehiculos ve ON cv.idVehiculo = ve.idVehiculo
                JOIN modelos mo ON ve.idModelo = mo.idModelo
                JOIN marcas ma ON mo.idMarca = ma.idMarca";

        $params = [];
        if ($dniVendedor) {
            $sql .= " WHERE v.dniVendedor = ?";
            $params[] = $dniVendedor;
        }

        $sql .= " ORDER BY v.fechaHoraGenerada DESC";
        $stmt = Database::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Obtener compras de un cliente
     */
    public static function comprasCliente(string $dniCliente): array
    {
        $sql = "SELECT v.idVenta as id, 
                       v.fechaHoraGenerada as created_at,
                       c.importeFinal as precio_final,
                       mo.nombre as modelo, 
                       ma.nombre as marca,
                       ve.anio,
                       'Tarjeta' as metodo_pago
                FROM ventas v
                JOIN cotizaciones c ON v.idCotizacion = c.idCotizacion
                JOIN cotizaciones_vehiculos cv ON c.idCotizacion = cv.idCotizacion
                JOIN vehiculos ve ON cv.idVehiculo = ve.idVehiculo
                JOIN modelos mo ON ve.idModelo = mo.idModelo
                JOIN marcas ma ON mo.idMarca = ma.idMarca
                WHERE c.dniCliente = ? AND v.concretada = 1
                ORDER BY v.fechaHoraGenerada DESC";
        $stmt = Database::query($sql, [$dniCliente]);
        return $stmt->fetchAll();
    }

    public static function find($id): ?self
    {
        $row = Database::queryOne("SELECT * FROM ventas WHERE idVenta = ?", [$id]);
        return $row ? new self($row) : null;
    }
}
