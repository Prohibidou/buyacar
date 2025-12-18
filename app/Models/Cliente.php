<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Cliente - Compatible con G1 Schema
 * 
 * G1 Schema: Clientes = dniCliente(PK), nombre, apellido, fechaNacimiento, direccion, email, idUsuario(FK)
 */
class Cliente extends Usuario
{
    protected static string $table = 'usuarios';

    private ?string $direccion = null;
    private ?string $dniCliente = null;
    private ?string $fechaNacimiento = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->cargarDatosCliente();
    }

    private function cargarDatosCliente(): void
    {
        $userId = $this->idUsuario ?? $this->id;
        if ($userId) {
            // G1 Schema: clientes tiene idUsuario como FK
            $sql = "SELECT dniCliente, nombre, apellido, direccion, fechaNacimiento, email FROM clientes WHERE idUsuario = ?";
            $datos = Database::queryOne($sql, [$userId]);
            if ($datos) {
                $this->direccion = $datos['direccion'];
                $this->dniCliente = $datos['dniCliente'];
                $this->fechaNacimiento = $datos['fechaNacimiento'];
                // Actualizar nombre y apellido desde clientes
                $this->attributes['nombre'] = $datos['nombre'];
                $this->attributes['apellido'] = $datos['apellido'];
            }
        }
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function getDni(): ?string
    {
        return $this->dniCliente;
    }

    public static function find($id): ?self
    {
        // G1 Schema: buscar por idUsuario
        $sql = "SELECT u.*, c.dniCliente, c.nombre, c.apellido, c.direccion, c.fechaNacimiento, c.email as cliente_email
                FROM usuarios u 
                JOIN clientes c ON u.idUsuario = c.idUsuario 
                WHERE u.idUsuario = ?";
        $row = Database::queryOne($sql, [$id]);

        if ($row) {
            $cliente = new self($row);
            $cliente->direccion = $row['direccion'];
            $cliente->dniCliente = $row['dniCliente'];
            $cliente->fechaNacimiento = $row['fechaNacimiento'];
            return $cliente;
        }
        return null;
    }

    public static function existeDni(string $dni): bool
    {
        // G1 Schema: dniCliente es el campo
        $sql = "SELECT COUNT(*) FROM clientes WHERE dniCliente = ?";
        $stmt = Database::query($sql, [$dni]);
        return $stmt->fetchColumn() > 0;
    }

    public function getReservas(): array
    {
        // G1 Schema: Reservas están vinculadas via Cotizaciones
        return Reserva::conVehiculo($this->idUsuario ?? $this->id);
    }
}
