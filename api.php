<?php
/**
 * Router de API - Laravel Style
 * Punto de entrada para todas las peticiones
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// Autoloader simple
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\VentaController;

$authController = new AuthController();
$vehiculoController = new VehiculoController();
$reservaController = new ReservaController();
$ventaController = new VentaController();

// Obtener acción y método
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$response = ['success' => false, 'error' => 'Acción no válida'];

try {
    switch ($action) {
        // ============================================
        // AUTH ROUTES
        // ============================================
        case 'login':
            if ($method === 'POST') {
                $response = $authController->login(
                    $_POST['email'] ?? '',
                    $_POST['password'] ?? ''
                );
            }
            break;

        case 'registro':
            if ($method === 'POST') {
                $response = $authController->registro($_POST);
            }
            break;

        case 'logout':
            $response = $authController->logout();
            break;

        case 'session':
            $response = [
                'success' => true,
                'logueado' => AuthController::check(),
                'usuario' => AuthController::user()
            ];
            break;

        // ============================================
        // VEHICULO ROUTES
        // ============================================
        case 'vehiculos':
            if (!empty($_GET['marca']) || !empty($_GET['tipo'])) {
                $response = $vehiculoController->filtrar(
                    $_GET['marca'] ?? null,
                    $_GET['tipo'] ?? null,
                    $_GET['precio_min'] ?? null,
                    $_GET['precio_max'] ?? null
                );
            } else {
                $response = $vehiculoController->index();
            }
            break;

        case 'vehiculo':
            $id = $_GET['id'] ?? 0;
            $response = $vehiculoController->show((int) $id);
            break;

        case 'vehiculo-crear':
            if ($method === 'POST') {
                $response = $vehiculoController->store($_POST);
            }
            break;

        // ============================================
        // RESERVA ROUTES  
        // ============================================
        case 'reservar':
            if ($method === 'POST') {
                $vehiculoId = $_POST['vehiculo_id'] ?? 0;
                $response = $reservaController->store((int) $vehiculoId);
            }
            break;

        case 'mis-reservas':
            $response = $reservaController->misReservas();
            break;

        case 'cancelar-reserva':
            if ($method === 'POST') {
                $reservaId = $_POST['reserva_id'] ?? 0;
                $response = $reservaController->cancelar((int) $reservaId);
            }
            break;

        case 'reserva':
            $id = $_GET['id'] ?? 0;
            $response = $reservaController->show((int) $id);
            break;

        // ============================================
        // VENTA ROUTES
        // ============================================
        case 'venta':
            if ($method === 'POST') {
                $response = $ventaController->store($_POST);
            }
            break;

        case 'mis-ventas':
            $response = $ventaController->misVentas();
            break;

        case 'mis-compras':
            $response = $ventaController->misCompras();
            break;

        case 'ventas':
            $response = $ventaController->index();
            break;
    }
} catch (\Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
