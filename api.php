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
use App\Http\Controllers\AccesorioController;

$authController = new AuthController();
$vehiculoController = new VehiculoController();
$reservaController = new ReservaController();
$ventaController = new VentaController();
$accesorioController = new AccesorioController();

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

        // CU-13.3: MODIFICAR DATOS DE CUENTA
        case 'modificar-cuenta':
            if ($method === 'POST' && AuthController::check()) {
                $userId = AuthController::id();
                $nombre = $_POST['nombre'] ?? null;
                $direccion = $_POST['direccion'] ?? null;

                if ($nombre || $direccion) {
                    // Obtener dni del cliente
                    $cliente = \App\Database::queryOne("SELECT dniCliente FROM clientes WHERE idUsuario = ?", [$userId]);
                    if ($cliente) {
                        $updates = [];
                        $params = [];
                        if ($nombre) {
                            $updates[] = "nombre = ?";
                            $params[] = $nombre;
                        }
                        if ($direccion) {
                            $updates[] = "direccion = ?";
                            $params[] = $direccion;
                        }
                        $params[] = $cliente['dniCliente'];
                        \App\Database::query("UPDATE clientes SET " . implode(", ", $updates) . " WHERE dniCliente = ?", $params);
                        $response = ['success' => true, 'message' => 'Datos actualizados'];
                    } else {
                        $response = ['success' => false, 'error' => 'Cliente no encontrado'];
                    }
                } else {
                    $response = ['success' => false, 'error' => 'No hay datos para actualizar'];
                }
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        // CU-15: GESTIÓN DE OFERTAS (Admin)
        case 'ofertas':
            if (AuthController::hasRole('ADMINISTRADOR')) {
                $response = ['success' => true, 'ofertas' => \App\Database::query("SELECT * FROM oferta WHERE eliminado = 0")->fetchAll()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        case 'crear-oferta':
            if ($method === 'POST' && AuthController::hasRole('ADMINISTRADOR')) {
                $descuento = $_POST['descuento'] ?? 0;
                $descripcion = $_POST['descripcion'] ?? '';
                \App\Database::query("INSERT INTO oferta (descuento, descripcion) VALUES (?, ?)", [$descuento, $descripcion]);
                $response = ['success' => true, 'oferta_id' => \App\Database::lastInsertId()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        case 'eliminar-oferta':
            if ($method === 'POST' && AuthController::hasRole('ADMINISTRADOR')) {
                $id = $_POST['id'] ?? 0;
                \App\Database::query("UPDATE oferta SET eliminado = 1 WHERE idOferta = ?", [$id]);
                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        // CU-16: GESTIÓN DE PRODUCTOS (Admin)
        case 'crear-vehiculo':
            if ($method === 'POST' && AuthController::hasRole('ADMINISTRADOR')) {
                $nroChasis = $_POST['nro_chasis'] ?? '';
                $precio = $_POST['precio'] ?? 0;
                $descripcion = $_POST['descripcion'] ?? '';
                $anio = $_POST['anio'] ?? date('Y');
                $idModelo = $_POST['id_modelo'] ?? 1;
                \App\Database::query(
                    "INSERT INTO vehiculos (nroChasis, precio, descripcion, anio, habilitado, estadoVehiculo, idModelo) VALUES (?, ?, ?, ?, 1, 'DISPONIBLE', ?)",
                    [$nroChasis, $precio, $descripcion, $anio, $idModelo]
                );
                $response = ['success' => true, 'vehiculo_id' => \App\Database::lastInsertId()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        case 'eliminar-vehiculo':
            if ($method === 'POST' && AuthController::hasRole('ADMINISTRADOR')) {
                $id = $_POST['id'] ?? 0;
                // Solo eliminar si está deshabilitado
                $vehiculo = \App\Database::queryOne("SELECT habilitado FROM vehiculos WHERE idVehiculo = ?", [$id]);
                if ($vehiculo && !$vehiculo['habilitado']) {
                    \App\Database::query("UPDATE vehiculos SET eliminado = 1 WHERE idVehiculo = ?", [$id]);
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'error' => 'El vehículo debe estar deshabilitado para eliminarlo'];
                }
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        case 'modificar-vehiculo':
            if ($method === 'POST' && AuthController::hasRole('ADMINISTRADOR')) {
                $id = $_POST['id'] ?? 0;
                $precio = $_POST['precio'] ?? null;
                $habilitado = isset($_POST['habilitado']) ? (int) $_POST['habilitado'] : null;

                $updates = [];
                $params = [];
                if ($precio !== null) {
                    $updates[] = "precio = ?";
                    $params[] = $precio;
                }
                if ($habilitado !== null) {
                    $updates[] = "habilitado = ?";
                    $params[] = $habilitado;
                }

                if (!empty($updates)) {
                    $params[] = $id;
                    \App\Database::query("UPDATE vehiculos SET " . implode(", ", $updates) . " WHERE idVehiculo = ?", $params);
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'error' => 'No hay datos para actualizar'];
                }
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        // CU-17: BUSCAR PRODUCTO (Admin)
        case 'buscar-producto':
            if (AuthController::hasRole('ADMINISTRADOR')) {
                $q = $_GET['q'] ?? '';
                $sql = "SELECT v.*, mo.nombre as modelo, ma.nombre as marca
                        FROM vehiculos v
                        JOIN modelos mo ON v.idModelo = mo.idModelo
                        JOIN marcas ma ON mo.idMarca = ma.idMarca
                        WHERE v.eliminado = 0 AND (
                            v.nroChasis LIKE ? OR 
                            mo.nombre LIKE ? OR 
                            ma.nombre LIKE ? OR
                            v.anio = ?
                        )
                        ORDER BY v.idVehiculo DESC";
                $response = ['success' => true, 'productos' => \App\Database::query($sql, ["%$q%", "%$q%", "%$q%", $q])->fetchAll()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
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

        case 'modelos':
            // Listar modelos con vehículos disponibles
            $response = [
                'success' => true,
                'modelos' => \App\Database::query(
                    "SELECT DISTINCT m.idModelo, m.nombre as modelo, ma.nombre as marca, 
                            COUNT(v.idVehiculo) as vehiculos_disponibles
                     FROM modelos m
                     JOIN marcas ma ON m.idMarca = ma.idMarca
                     LEFT JOIN vehiculos v ON v.idModelo = m.idModelo AND v.estadoVehiculo = 'DISPONIBLE'
                     GROUP BY m.idModelo, m.nombre, ma.nombre
                     HAVING vehiculos_disponibles > 0
                     ORDER BY ma.nombre, m.nombre"
                )->fetchAll()
            ];
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
        // ACCESORIOS ROUTES
        // ============================================
        case 'accesorios':
            $response = $accesorioController->index();
            break;

        case 'accesorios-modelo':
            $idModelo = $_GET['id_modelo'] ?? 0;
            $response = $accesorioController->porModelo((int) $idModelo);
            break;

        // ============================================
        // RESERVA ROUTES  
        // ============================================
        case 'reservar':
            if ($method === 'POST') {
                $vehiculoId = $_POST['vehiculo_id'] ?? 0;
                $accesorioIds = isset($_POST['accesorios']) ? (array) $_POST['accesorios'] : [];
                $response = $reservaController->store((int) $vehiculoId, array_map('intval', $accesorioIds));
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

        case 'reservas-pendientes':
            // Vendedor: ver reservas activas para confirmar
            if (\App\Http\Controllers\AuthController::hasRole('VENDEDOR')) {
                $response = [
                    'success' => true,
                    'reservas' => \App\Database::query(
                        "SELECT r.nroReserva, r.fechaHoraGenerada, r.importe, r.estadoReserva,
                                c.importeFinal, c.dniCliente,
                                CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
                                v.idVehiculo, mo.nombre as modelo, ma.nombre as marca, v.precio as precio_vehiculo
                         FROM reservas r
                         JOIN cotizaciones c ON r.idCotizacion = c.idCotizacion
                         JOIN clientes cl ON c.dniCliente = cl.dniCliente
                         JOIN cotizaciones_vehiculos cv ON c.idCotizacion = cv.idCotizacion
                         JOIN vehiculos v ON cv.idVehiculo = v.idVehiculo
                         JOIN modelos mo ON v.idModelo = mo.idModelo
                         JOIN marcas ma ON mo.idMarca = ma.idMarca
                         WHERE r.estadoReserva = 'ACTIVA'
                         ORDER BY r.fechaHoraGenerada DESC"
                    )->fetchAll()
                ];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
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

        // ============================================
        // CU-7: BUSCAR COTIZACIÓN
        // ============================================
        case 'buscar-cotizacion':
            if (AuthController::check()) {
                $busqueda = $_GET['q'] ?? '';
                $sql = "SELECT c.idCotizacion, c.fechaHoraGenerada, c.importeFinal, c.valida,
                               CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre, cl.dniCliente,
                               r.nroReserva, r.estadoReserva
                        FROM cotizaciones c
                        JOIN clientes cl ON c.dniCliente = cl.dniCliente
                        LEFT JOIN reservas r ON c.idCotizacion = r.idCotizacion
                        WHERE (c.idCotizacion = ? OR cl.dniCliente LIKE ?)
                        AND c.valida = 1
                        ORDER BY c.fechaHoraGenerada DESC";
                $cotizaciones = \App\Database::query($sql, [$busqueda, "%$busqueda%"])->fetchAll();
                $response = ['success' => true, 'cotizaciones' => $cotizaciones];
            } else {
                $response = ['success' => false, 'error' => 'Debe iniciar sesión'];
            }
            break;

        // ============================================
        // REPORTES ADMINISTRADOR (CU-8 a CU-12)
        // ============================================
        case 'reporte-vehiculos-cotizados':
            if (AuthController::hasRole('ADMINISTRADOR')) {
                $desde = $_GET['desde'] ?? date('Y-m-01');
                $hasta = $_GET['hasta'] ?? date('Y-m-d');
                $sql = "SELECT mo.nombre as modelo, ma.nombre as marca, COUNT(*) as cantidad
                        FROM cotizaciones_vehiculos cv
                        JOIN cotizaciones c ON cv.idCotizacion = c.idCotizacion
                        JOIN vehiculos v ON cv.idVehiculo = v.idVehiculo
                        JOIN modelos mo ON v.idModelo = mo.idModelo
                        JOIN marcas ma ON mo.idMarca = ma.idMarca
                        WHERE c.fechaHoraGenerada BETWEEN ? AND ?
                        GROUP BY mo.idModelo, mo.nombre, ma.nombre
                        ORDER BY cantidad DESC";
                $response = ['success' => true, 'reporte' => \App\Database::query($sql, [$desde, $hasta])->fetchAll()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        case 'reporte-accesorios-solicitados':
            if (AuthController::hasRole('ADMINISTRADOR')) {
                $desde = $_GET['desde'] ?? date('Y-m-01');
                $hasta = $_GET['hasta'] ?? date('Y-m-d');
                $sql = "SELECT a.nombre as accesorio, COUNT(*) as cantidad
                        FROM cotizaciones_vehiculos_accesorios cva
                        JOIN cotizaciones c ON cva.idCotizacion = c.idCotizacion
                        JOIN accesorios a ON cva.idAccesorio = a.idAccesorio
                        WHERE c.fechaHoraGenerada BETWEEN ? AND ?
                        GROUP BY a.idAccesorio, a.nombre
                        ORDER BY cantidad DESC";
                $response = ['success' => true, 'reporte' => \App\Database::query($sql, [$desde, $hasta])->fetchAll()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        case 'reporte-ventas-no-concretadas':
            if (AuthController::hasRole('ADMINISTRADOR')) {
                $desde = $_GET['desde'] ?? date('Y-m-01');
                $hasta = $_GET['hasta'] ?? date('Y-m-d');
                $sql = "SELECT v.idVenta, v.fechaHoraGenerada, c.importeFinal,
                               mo.nombre as modelo, ma.nombre as marca
                        FROM ventas v
                        JOIN cotizaciones c ON v.idCotizacion = c.idCotizacion
                        JOIN cotizaciones_vehiculos cv ON c.idCotizacion = cv.idCotizacion
                        JOIN vehiculos ve ON cv.idVehiculo = ve.idVehiculo
                        JOIN modelos mo ON ve.idModelo = mo.idModelo
                        JOIN marcas ma ON mo.idMarca = ma.idMarca
                        WHERE v.concretada = 0 AND v.fechaHoraGenerada BETWEEN ? AND ?
                        ORDER BY v.fechaHoraGenerada DESC";
                $response = ['success' => true, 'reporte' => \App\Database::query($sql, [$desde, $hasta])->fetchAll()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        case 'estadisticas-modelos-vendidos':
            if (AuthController::hasRole('ADMINISTRADOR')) {
                $desde = $_GET['desde'] ?? date('Y-m-01');
                $hasta = $_GET['hasta'] ?? date('Y-m-d');
                $sql = "SELECT mo.nombre as modelo, ma.nombre as marca, COUNT(*) as cantidad,
                               SUM(c.importeFinal) as total_ventas
                        FROM ventas v
                        JOIN cotizaciones c ON v.idCotizacion = c.idCotizacion
                        JOIN cotizaciones_vehiculos cv ON c.idCotizacion = cv.idCotizacion
                        JOIN vehiculos ve ON cv.idVehiculo = ve.idVehiculo
                        JOIN modelos mo ON ve.idModelo = mo.idModelo
                        JOIN marcas ma ON mo.idMarca = ma.idMarca
                        WHERE v.concretada = 1 AND v.fechaHoraGenerada BETWEEN ? AND ?
                        GROUP BY mo.idModelo, mo.nombre, ma.nombre
                        ORDER BY cantidad DESC";
                $response = ['success' => true, 'estadisticas' => \App\Database::query($sql, [$desde, $hasta])->fetchAll()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;

        case 'estadisticas-comisiones':
            if (AuthController::hasRole('ADMINISTRADOR')) {
                $desde = $_GET['desde'] ?? date('Y-m-01');
                $hasta = $_GET['hasta'] ?? date('Y-m-d');
                $sql = "SELECT CONCAT(ve.nombre, ' ', ve.apellido) as vendedor, 
                               SUM(v.comision) as total_comision,
                               COUNT(*) as cantidad_ventas
                        FROM ventas v
                        JOIN vendedores ve ON v.dniVendedor = ve.dniVendedor
                        WHERE v.concretada = 1 AND v.fechaHoraGenerada BETWEEN ? AND ?
                        GROUP BY ve.dniVendedor, ve.nombre, ve.apellido
                        ORDER BY total_comision DESC";
                $response = ['success' => true, 'estadisticas' => \App\Database::query($sql, [$desde, $hasta])->fetchAll()];
            } else {
                $response = ['success' => false, 'error' => 'No autorizado'];
            }
            break;
    }
} catch (\Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
