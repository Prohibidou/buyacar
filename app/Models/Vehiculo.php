<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Vehículo - Compatible con G1 Schema
 */
class Vehiculo extends Model
{
    protected static string $table = 'vehiculos';

    protected array $fillable = [
        'nroChasis',
        'precio',
        'descripcion',
        'anio',
        'imagen',
        'habilitado',
        'estadoVehiculo',
        'eliminado',
        'idModelo',
        'idOferta'
    ];

    const ESTADOS = ['DISPONIBLE', 'RESERVADO', 'VENDIDO', 'NO_DISPONIBLE'];

    public function getNombreCompleto(): string
    {
        // G1 Schema: obtener marca y modelo desde tablas relacionadas
        $modelo = Database::queryOne("SELECT m.nombre as modelo, ma.nombre as marca FROM modelos m JOIN marcas ma ON m.idMarca = ma.idMarca WHERE m.idModelo = ?", [$this->idModelo]);
        if ($modelo) {
            return "{$modelo['marca']} {$modelo['modelo']} {$this->anio}";
        }
        return "Vehículo {$this->anio}";
    }

    public function estaDisponible(): bool
    {
        $estado = $this->estadoVehiculo ?? $this->estado ?? 'DISPONIBLE';
        return $estado === 'DISPONIBLE';
    }

    public static function disponibles(): array
    {
        // G1 Schema: usar estadoVehiculo y habilitado
        $sql = "SELECT v.*, m.nombre as modelo, ma.nombre as marca 
                FROM vehiculos v 
                LEFT JOIN modelos m ON v.idModelo = m.idModelo 
                LEFT JOIN marcas ma ON m.idMarca = ma.idMarca 
                WHERE v.estadoVehiculo = 'DISPONIBLE' AND v.habilitado = 1 AND v.eliminado = 0";
        $stmt = Database::query($sql);
        return array_map(fn($row) => new self($row), $stmt->fetchAll());
    }

    public static function filtrar(?string $marca, ?string $tipo, ?float $precioMin, ?float $precioMax): array
    {
        // G1 Schema: vehiculos con join a modelos y marcas
        $sql = "SELECT v.*, m.nombre as modelo, ma.nombre as marca 
                FROM vehiculos v 
                LEFT JOIN modelos m ON v.idModelo = m.idModelo 
                LEFT JOIN marcas ma ON m.idMarca = ma.idMarca 
                WHERE v.estadoVehiculo = 'DISPONIBLE' AND v.habilitado = 1 AND v.eliminado = 0";
        $params = [];

        if ($marca) {
            $sql .= " AND ma.nombre LIKE ?";
            $params[] = "%{$marca}%";
        }
        if ($precioMin) {
            $sql .= " AND v.precio >= ?";
            $params[] = $precioMin;
        }
        if ($precioMax) {
            $sql .= " AND v.precio <= ?";
            $params[] = $precioMax;
        }

        $sql .= " ORDER BY ma.nombre, m.nombre";
        $stmt = Database::query($sql, $params);
        return array_map(fn($row) => new self($row), $stmt->fetchAll());
    }

    public function actualizarEstado(string $estado): bool
    {
        // G1 Schema: usar estadoVehiculo e idVehiculo
        $id = $this->idVehiculo ?? $this->id;
        $sql = "UPDATE vehiculos SET estadoVehiculo = ? WHERE idVehiculo = ?";
        Database::query($sql, [$estado, $id]);
        $this->attributes['estadoVehiculo'] = $estado;
        return true;
    }

    public static function find($id): ?self
    {
        // G1 Schema: usar idVehiculo y join con modelos/marcas
        $sql = "SELECT v.*, m.nombre as modelo, ma.nombre as marca 
                FROM vehiculos v 
                LEFT JOIN modelos m ON v.idModelo = m.idModelo 
                LEFT JOIN marcas ma ON m.idMarca = ma.idMarca 
                WHERE v.idVehiculo = ?";
        $row = Database::queryOne($sql, [$id]);
        return $row ? new self($row) : null;
    }
}
