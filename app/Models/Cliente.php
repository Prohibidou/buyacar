<?php
namespace App\Models;

use App\Database;

/**
 * Modelo Cliente - Extiende Usuario
 */
class Cliente extends Usuario
{
    protected static string $table = 'usuarios';

    private ?string $direccion = null;
    private ?string $telefono = null;
    private ?string $dni = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->cargarDatosCliente();
    }

    private function cargarDatosCliente(): void
    {
        if ($this->id) {
            $sql = "SELECT direccion, telefono, dni FROM clientes WHERE id = ?";
            $stmt = Database::query($sql, [$this->id]);
            $datos = $stmt->fetch();
            if ($datos) {
                $this->direccion = $datos['direccion'];
                $this->telefono = $datos['telefono'];
                $this->dni = $datos['dni'];
            }
        }
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }
    public function getTelefono(): ?string
    {
        return $this->telefono;
    }
    public function getDni(): ?string
    {
        return $this->dni;
    }

    public static function find(int $id): ?self
    {
        $sql = "SELECT u.*, c.direccion, c.telefono, c.dni 
                FROM usuarios u 
                JOIN clientes c ON u.id = c.id 
                WHERE u.id = ? AND u.rol = 'CLIENTE'";
        $stmt = Database::query($sql, [$id]);
        $row = $stmt->fetch();

        if ($row) {
            $cliente = new self($row);
            $cliente->direccion = $row['direccion'];
            $cliente->telefono = $row['telefono'];
            $cliente->dni = $row['dni'];
            return $cliente;
        }
        return null;
    }

    public static function existeDni(string $dni): bool
    {
        $sql = "SELECT COUNT(*) FROM clientes WHERE dni = ?";
        $stmt = Database::query($sql, [$dni]);
        return $stmt->fetchColumn() > 0;
    }

    public function getReservas(): array
    {
        return Reserva::where('cliente_id', $this->id);
    }
}
