<?php
namespace App\Models;

/**
 * Modelo Usuario
 */
class Usuario extends Model
{
    protected static string $table = 'usuarios';

    protected array $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'activo'
    ];

    public function getNombreCompleto(): string
    {
        // G1 Schema: nombres están en clientes/vendedores, no en usuarios
        if (isset($this->nombre) && isset($this->apellido)) {
            return $this->nombre . ' ' . $this->apellido;
        }

        // Buscar en clientes
        $cliente = \App\Database::queryOne("SELECT nombre, apellido FROM clientes WHERE idUsuario = ?", [$this->id ?? $this->idUsuario]);
        if ($cliente) {
            return $cliente['nombre'] . ' ' . $cliente['apellido'];
        }

        // Buscar en vendedores
        $vendedor = \App\Database::queryOne("SELECT nombre, apellido FROM vendedores WHERE idUsuario = ?", [$this->id ?? $this->idUsuario]);
        if ($vendedor) {
            return $vendedor['nombre'] . ' ' . $vendedor['apellido'];
        }

        return $this->email ?? 'Usuario';
    }

    public function verificarPassword(string $password): bool
    {
        return password_verify($password, $this->contrasenia ?? $this->password);
    }

    public static function registrarCliente(array $datos): ?Cliente
    {
        try {
            \App\Database::beginTransaction();

            // Crear usuario
            $usuario = self::create([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'email' => $datos['email'],
                'password' => password_hash($datos['password'], PASSWORD_DEFAULT),
                'rol' => 'CLIENTE',
                'activo' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Crear cliente
            $sql = "INSERT INTO clientes (id, direccion, telefono, dni) VALUES (?, ?, ?, ?)";
            \App\Database::query($sql, [
                $usuario->id,
                $datos['direccion'] ?? '',
                $datos['telefono'] ?? '',
                $datos['dni']
            ]);

            \App\Database::commit();
            return Cliente::find($usuario->id);
        } catch (\Exception $e) {
            \App\Database::rollBack();
            throw $e;
        }
    }

    public static function buscarPorEmail(string $email): ?self
    {
        return self::whereFirst('email', $email);
    }

    public static function existeEmail(string $email): bool
    {
        return self::whereFirst('email', $email) !== null;
    }
}
