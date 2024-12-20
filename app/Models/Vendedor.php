<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Vendedor - Extiende Usuario
 */
class Vendedor extends Usuario
{
    protected static string $table = 'usuarios';

    private ?string $legajo = null;
    private float $comision = 5.0;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->cargarDatosVendedor();
    }

    private function cargarDatosVendedor(): void
    {
        if ($this->id) {
            $sql = "SELECT legajo, comision FROM vendedores WHERE id = ?";
            $stmt = Database::query($sql, [$this->id]);
            $datos = $stmt->fetch();
            if ($datos) {
                $this->legajo = $datos['legajo'];
                $this->comision = (float) $datos['comision'];
            }
        }
    }

    public function getLegajo(): ?string
    {
        return $this->legajo;
    }
    public function getComision(): float
    {
        return $this->comision;
    }

    public function calcularComision(float $montoVenta): float
    {
        return $montoVenta * ($this->comision / 100);
    }

    public static function find(int $id): ?self
    {
        $sql = "SELECT u.*, v.legajo, v.comision 
                FROM usuarios u 
                JOIN vendedores v ON u.id = v.id 
                WHERE u.id = ? AND u.rol = 'VENDEDOR'";
        $stmt = Database::query($sql, [$id]);
        $row = $stmt->fetch();

        if ($row) {
            $vendedor = new self($row);
            $vendedor->legajo = $row['legajo'];
            $vendedor->comision = (float) $row['comision'];
            return $vendedor;
        }
        return null;
    }

    public function getVentas(): array
    {
        return Venta::where('vendedor_id', $this->id);
    }
}
