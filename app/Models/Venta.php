<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Venta
 */
class Venta extends Model
{
    protected static string $table = 'ventas';

    protected array $fillable = [
        'cliente_id',
        'vendedor_id',
        'vehiculo_id',
        'reserva_id',
        'precio_final',
        'metodo_pago',
        'comision_vendedor'
    ];

    const METODOS_PAGO = ['EFECTIVO', 'TARJETA_CREDITO', 'TARJETA_DEBITO', 'TRANSFERENCIA', 'FINANCIADO'];

    /**
     * Realizar una venta
     */
    public static function realizar(int $clienteId, int $vendedorId, int $vehiculoId, string $metodoPago, ?int $reservaId = null): array
    {
        try {
            Database::beginTransaction();

            $vehiculo = Vehiculo::find($vehiculoId);
            if (!$vehiculo) {
                throw new \Exception("Vehículo no encontrado.");
            }

            if (!in_array($vehiculo->estado, ['DISPONIBLE', 'RESERVADO'])) {
                throw new \Exception("El vehículo no está disponible para venta.");
            }

            $vendedor = Vendedor::find($vendedorId);
            if (!$vendedor) {
                throw new \Exception("Vendedor no válido.");
            }

            $precioFinal = (float) $vehiculo->precio;
            $comision = $vendedor->calcularComision($precioFinal);

            // Si hay reserva, completarla y descontar seña
            if ($reservaId) {
                $reserva = Reserva::find($reservaId);
                if ($reserva && $reserva->estaActiva()) {
                    $reserva->completar();
                    $precioFinal -= (float) $reserva->monto_sena;
                }
            }

            $venta = self::create([
                'cliente_id' => $clienteId,
                'vendedor_id' => $vendedorId,
                'vehiculo_id' => $vehiculoId,
                'reserva_id' => $reservaId,
                'precio_final' => $precioFinal,
                'metodo_pago' => $metodoPago,
                'comision_vendedor' => $comision,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $vehiculo->actualizarEstado('VENDIDO');

            Database::commit();

            return [
                'success' => true,
                'venta_id' => $venta->id,
                'precio_final' => $precioFinal,
                'comision_vendedor' => $comision
            ];
        } catch (\Exception $e) {
            Database::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener ventas con datos relacionados
     */
    public static function conDetalles(?int $vendedorId = null): array
    {
        $sql = "SELECT v.*, 
                       ve.marca, ve.modelo, ve.anio,
                       CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                       CONCAT(vd.nombre, ' ', vd.apellido) as vendedor_nombre
                FROM ventas v
                JOIN vehiculos ve ON v.vehiculo_id = ve.id
                JOIN usuarios c ON v.cliente_id = c.id
                JOIN usuarios vd ON v.vendedor_id = vd.id";

        $params = [];
        if ($vendedorId) {
            $sql .= " WHERE v.vendedor_id = ?";
            $params[] = $vendedorId;
        }

        $sql .= " ORDER BY v.created_at DESC";
        $stmt = Database::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Obtener compras de un cliente
     */
    public static function comprasCliente(int $clienteId): array
    {
        $sql = "SELECT v.*, ve.marca, ve.modelo, ve.anio
                FROM ventas v
                JOIN vehiculos ve ON v.vehiculo_id = ve.id
                WHERE v.cliente_id = ?
                ORDER BY v.created_at DESC";
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

    public function getVendedor(): ?Vendedor
    {
        return Vendedor::find($this->vendedor_id);
    }
}
