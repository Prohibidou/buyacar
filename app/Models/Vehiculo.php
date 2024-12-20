<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Vehículo
 */
class Vehiculo extends Model
{
    protected static string $table = 'vehiculos';

    protected array $fillable = [
        'marca',
        'modelo',
        'anio',
        'precio',
        'estado',
        'tipo',
        'kilometraje',
        'color',
        'descripcion',
        'imagen_url'
    ];

    const ESTADOS = ['DISPONIBLE', 'RESERVADO', 'VENDIDO', 'NO_DISPONIBLE'];
    const TIPOS = ['SEDAN', 'SUV', 'PICKUP', 'HATCHBACK', 'COUPE', 'DEPORTIVO'];

    public function getNombreCompleto(): string
    {
        return "{$this->marca} {$this->modelo} {$this->anio}";
    }

    public function estaDisponible(): bool
    {
        return $this->estado === 'DISPONIBLE';
    }

    public function esNuevo(): bool
    {
        return (int) $this->kilometraje === 0;
    }

    public static function disponibles(): array
    {
        return self::where('estado', 'DISPONIBLE');
    }

    public static function filtrar(?string $marca, ?string $tipo, ?float $precioMin, ?float $precioMax): array
    {
        $sql = "SELECT * FROM vehiculos WHERE estado = 'DISPONIBLE'";
        $params = [];

        if ($marca) {
            $sql .= " AND marca LIKE ?";
            $params[] = "%{$marca}%";
        }
        if ($tipo) {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }
        if ($precioMin) {
            $sql .= " AND precio >= ?";
            $params[] = $precioMin;
        }
        if ($precioMax) {
            $sql .= " AND precio <= ?";
            $params[] = $precioMax;
        }

        $sql .= " ORDER BY marca, modelo";
        $stmt = Database::query($sql, $params);
        return array_map(fn($row) => new self($row), $stmt->fetchAll());
    }

    public function actualizarEstado(string $estado): bool
    {
        $sql = "UPDATE vehiculos SET estado = ?, updated_at = NOW() WHERE id = ?";
        Database::query($sql, [$estado, $this->id]);
        $this->attributes['estado'] = $estado;
        return true;
    }
}
