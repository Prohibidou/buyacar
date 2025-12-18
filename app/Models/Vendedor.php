<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Vendedor - Compatible con G1 Schema
 * 
 * G1 Schema: Vendedores = dniVendedor(PK), nombre, apellido, idUsuario(FK)
 */
class Vendedor extends Usuario
{
    protected static string $table = 'usuarios';

    private ?string $dniVendedor = null;
    private float $comision = 5.0; // Porcentaje de comisión por defecto

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->cargarDatosVendedor();
    }

    private function cargarDatosVendedor(): void
    {
        $userId = $this->idUsuario ?? $this->id;
        if ($userId) {
            // G1 Schema: vendedores tiene idUsuario como FK
            $datos = Database::queryOne("SELECT dniVendedor, nombre, apellido FROM vendedores WHERE idUsuario = ?", [$userId]);
            if ($datos) {
                $this->dniVendedor = $datos['dniVendedor'];
                $this->attributes['nombre'] = $datos['nombre'];
                $this->attributes['apellido'] = $datos['apellido'];
            }
        }
    }

    public function getDni(): ?string
    {
        return $this->dniVendedor;
    }

    public function getComision(): float
    {
        return $this->comision;
    }

    public function calcularComision(float $montoVenta): float
    {
        return $montoVenta * ($this->comision / 100);
    }

    public static function find($id): ?self
    {
        // G1 Schema: buscar por idUsuario
        $row = Database::queryOne(
            "SELECT u.*, v.dniVendedor, v.nombre, v.apellido
             FROM usuarios u 
             JOIN vendedores v ON u.idUsuario = v.idUsuario 
             WHERE u.idUsuario = ?",
            [$id]
        );

        if ($row) {
            $vendedor = new self($row);
            $vendedor->dniVendedor = $row['dniVendedor'];
            return $vendedor;
        }
        return null;
    }

    public static function findByDni(string $dniVendedor): ?self
    {
        $row = Database::queryOne(
            "SELECT u.*, v.dniVendedor, v.nombre, v.apellido
             FROM usuarios u 
             JOIN vendedores v ON u.idUsuario = v.idUsuario 
             WHERE v.dniVendedor = ?",
            [$dniVendedor]
        );

        if ($row) {
            $vendedor = new self($row);
            $vendedor->dniVendedor = $row['dniVendedor'];
            return $vendedor;
        }
        return null;
    }

    public function getVentas(): array
    {
        if (!$this->dniVendedor)
            return [];
        $stmt = Database::query("SELECT * FROM ventas WHERE dniVendedor = ?", [$this->dniVendedor]);
        return $stmt->fetchAll();
    }
}
