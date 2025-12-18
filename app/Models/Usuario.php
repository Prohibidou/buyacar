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

            // G1 Schema: Usuarios tiene email, contrasenia, idTipoUsuario
            // TipoUsuarios: 1=CLIENTE, 2=VENDEDOR, 3=ADMINISTRADOR
            $sql = "INSERT INTO usuarios (email, contrasenia, idTipoUsuario) VALUES (?, ?, 1)";
            \App\Database::query($sql, [
                $datos['email'],
                password_hash($datos['password'], PASSWORD_DEFAULT)
            ]);
            $usuarioId = \App\Database::lastInsertId();

            // G1 Schema: Clientes tiene dniCliente(PK), nombre, apellido, fechaNacimiento, direccion, email, idUsuario(FK)
            $sql = "INSERT INTO clientes (dniCliente, nombre, apellido, fechaNacimiento, direccion, email, idUsuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            \App\Database::query($sql, [
                $datos['dni'],
                $datos['nombre'],
                $datos['apellido'],
                $datos['fechaNacimiento'] ?? null,
                $datos['direccion'] ?? '',
                $datos['email'],
                $usuarioId
            ]);

            \App\Database::commit();
            return Cliente::find($usuarioId);
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
