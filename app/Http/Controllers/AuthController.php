<?php
namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\Vendedor;

/**
 * Controlador de Autenticación
 */
class AuthController
{

    /**
     * Procesar login
     */
    public function login(string $email, string $password): array
    {
        $usuario = Usuario::buscarPorEmail($email);

        if (!$usuario) {
            return ['success' => false, 'error' => 'Usuario no encontrado'];
        }

        if (!$usuario->verificarPassword($password)) {
            return ['success' => false, 'error' => 'Contraseña incorrecta'];
        }

        // G1 Schema: usar idUsuario en lugar de id
        $userId = $usuario->idUsuario ?? $usuario->id;

        // G1 Schema: obtener rol de TipoUsuarios
        $rol = $usuario->rol ?? null;
        if (!$rol && isset($usuario->idTipoUsuario)) {
            $tipoUsuario = \App\Database::queryOne("SELECT descripcion FROM tipousuarios WHERE idTipoUsuario = ?", [$usuario->idTipoUsuario]);
            $rol = $tipoUsuario ? $tipoUsuario['descripcion'] : 'CLIENTE';
        }

        // Crear sesión
        $_SESSION['usuario_id'] = $userId;
        $_SESSION['usuario_nombre'] = $usuario->getNombreCompleto();
        $_SESSION['usuario_rol'] = $rol;
        $_SESSION['usuario_email'] = $usuario->email;

        return [
            'success' => true,
            'usuario' => [
                'id' => $userId,
                'nombre' => $usuario->getNombreCompleto(),
                'rol' => $rol
            ]
        ];
    }

    /**
     * Procesar registro
     */
    public function registro(array $datos): array
    {
        // Validaciones
        if (
            empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['email']) ||
            empty($datos['password']) || empty($datos['dni'])
        ) {
            return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
        }

        if (Usuario::existeEmail($datos['email'])) {
            return ['success' => false, 'error' => 'El email ya está registrado'];
        }

        if (Cliente::existeDni($datos['dni'])) {
            return ['success' => false, 'error' => 'El DNI ya está registrado'];
        }

        try {
            $cliente = Usuario::registrarCliente($datos);
            return ['success' => true, 'usuario_id' => $cliente->id];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error al registrar: ' . $e->getMessage()];
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(): array
    {
        session_destroy();
        return ['success' => true];
    }

    /**
     * Verificar sesión activa
     */
    public static function check(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Obtener usuario actual
     */
    public static function user(): ?array
    {
        if (!self::check())
            return null;
        return [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'rol' => $_SESSION['usuario_rol'],
            'email' => $_SESSION['usuario_email']
        ];
    }

    /**
     * Verificar si tiene un rol específico
     */
    public static function hasRole(string $rol): bool
    {
        return self::check() && $_SESSION['usuario_rol'] === $rol;
    }

    /**
     * Obtener ID del usuario actual
     */
    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['usuario_id'] : null;
    }
}
