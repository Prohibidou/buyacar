<?php
/**
 * FLY Car - Tests Automatizados
 * Principio de Responsabilidad Única - Un test = Una verificación
 * 
 * Ejecutar: php tests/run_tests.php
 */

require_once __DIR__ . '/TestCase.php';

// ===========================================
// AUTH TESTS - Login
// ===========================================

class Test_Login_ClienteCredencialesValidas extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $this->assertTrue($r['success'] ?? false, 'Login cliente con credenciales válidas retorna success=true');
    }
}

class Test_Login_ClienteRetornaRolCliente extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $this->assertEquals('CLIENTE', $r['usuario']['rol'] ?? '', 'Login cliente retorna rol=CLIENTE');
    }
}

class Test_Login_VendedorCredencialesValidas extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'vendedor@flycar.com', 'password' => 'password'], 'POST');
        $this->assertTrue($r['success'] ?? false, 'Login vendedor con credenciales válidas retorna success=true');
    }
}

class Test_Login_VendedorRetornaRolVendedor extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'vendedor@flycar.com', 'password' => 'password'], 'POST');
        $this->assertEquals('VENDEDOR', $r['usuario']['rol'] ?? '', 'Login vendedor retorna rol=VENDEDOR');
    }
}

class Test_Login_AdminCredencialesValidas extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $this->assertTrue($r['success'] ?? false, 'Login admin con credenciales válidas retorna success=true');
    }
}

class Test_Login_AdminRetornaRolAdmin extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $this->assertEquals('ADMINISTRADOR', $r['usuario']['rol'] ?? '', 'Login admin retorna rol=ADMINISTRADOR');
    }
}

class Test_Login_EmailInvalidoRetornaError extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'noexiste@test.com', 'password' => 'password'], 'POST');
        $this->assertFalse($r['success'] ?? true, 'Email inválido retorna success=false');
    }
}

class Test_Login_PasswordInvalidoRetornaError extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'cliente@test.com', 'password' => 'wrongpass'], 'POST');
        $this->assertFalse($r['success'] ?? true, 'Password inválido retorna success=false');
    }
}

class Test_Login_RetornaNombreUsuario extends TestCase
{
    public function test(): void
    {
        $r = $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $this->assertNotEmpty($r['usuario']['nombre'] ?? '', 'Login retorna nombre de usuario');
    }
}

// ===========================================
// AUTH TESTS - Session
// ===========================================

class Test_Session_SinLoginRetornaLogueadoFalse extends TestCase
{
    public function test(): void
    {
        // Clear cookies to ensure no session
        $this->api('logout');
        $r = $this->api('session');
        $this->assertFalse($r['logueado'] ?? true, 'Sin login, session retorna logueado=false');
    }
}

class Test_Session_ConLoginRetornaLogueadoTrue extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $r = $this->api('session');
        $this->assertTrue($r['logueado'] ?? false, 'Con login, session retorna logueado=true');
    }
}

class Test_Session_ConLoginRetornaDatosUsuario extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $r = $this->api('session');
        $this->assertNotEmpty($r['usuario'] ?? [], 'Con login, session retorna datos de usuario');
    }
}

// ===========================================
// AUTH TESTS - Logout
// ===========================================

class Test_Logout_RetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $r = $this->api('logout');
        $this->assertTrue($r['success'] ?? false, 'Logout retorna success=true');
    }
}

class Test_Logout_DestuyeSesion extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $this->api('logout');
        $r = $this->api('session');
        $this->assertFalse($r['logueado'] ?? true, 'Después de logout, logueado=false');
    }
}

// ===========================================
// CATALOGO TESTS
// ===========================================

class Test_Vehiculos_RetornaSuccess extends TestCase
{
    public function test(): void
    {
        $r = $this->api('vehiculos');
        $this->assertTrue($r['success'] ?? false, 'API vehiculos retorna success=true');
    }
}

class Test_Vehiculos_RetornaArrayVehiculos extends TestCase
{
    public function test(): void
    {
        $r = $this->api('vehiculos');
        $this->assertTrue(is_array($r['vehiculos'] ?? null), 'API vehiculos retorna array de vehiculos');
    }
}

class Test_Vehiculos_TienenMarca extends TestCase
{
    public function test(): void
    {
        $r = $this->api('vehiculos');
        $v = $r['vehiculos'][0] ?? [];
        $this->assertArrayHasKey('marca', $v, 'Vehículo tiene campo marca');
    }
}

class Test_Vehiculos_TienenModelo extends TestCase
{
    public function test(): void
    {
        $r = $this->api('vehiculos');
        $v = $r['vehiculos'][0] ?? [];
        $this->assertArrayHasKey('modelo', $v, 'Vehículo tiene campo modelo');
    }
}

class Test_Vehiculos_TienenPrecio extends TestCase
{
    public function test(): void
    {
        $r = $this->api('vehiculos');
        $v = $r['vehiculos'][0] ?? [];
        $this->assertArrayHasKey('precio', $v, 'Vehículo tiene campo precio');
    }
}

class Test_Vehiculos_TienenAnio extends TestCase
{
    public function test(): void
    {
        $r = $this->api('vehiculos');
        $v = $r['vehiculos'][0] ?? [];
        $this->assertArrayHasKey('anio', $v, 'Vehículo tiene campo anio');
    }
}

class Test_Vehiculos_TienenId extends TestCase
{
    public function test(): void
    {
        $r = $this->api('vehiculos');
        $v = $r['vehiculos'][0] ?? [];
        $this->assertArrayHasKey('idVehiculo', $v, 'Vehículo tiene campo idVehiculo');
    }
}

class Test_Vehiculos_FiltrarPorMarcaRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $r = $this->api('vehiculos', ['marca' => 'Toyota']);
        $this->assertTrue($r['success'] ?? false, 'Filtrar por marca retorna success=true');
    }
}

class Test_VehiculoDetalle_RetornaSuccess extends TestCase
{
    public function test(): void
    {
        $list = $this->api('vehiculos');
        $id = $list['vehiculos'][0]['idVehiculo'] ?? 1;
        $r = $this->api('vehiculo', ['id' => $id]);
        $this->assertTrue($r['success'] ?? false, 'Detalle de vehículo retorna success=true');
    }
}

class Test_VehiculoDetalle_RetornaVehiculo extends TestCase
{
    public function test(): void
    {
        $list = $this->api('vehiculos');
        $id = $list['vehiculos'][0]['idVehiculo'] ?? 1;
        $r = $this->api('vehiculo', ['id' => $id]);
        $this->assertNotEmpty($r['vehiculo'] ?? [], 'Detalle retorna objeto vehiculo');
    }
}

// ===========================================
// ACCESORIOS TESTS
// ===========================================

class Test_AccesoriosModelo_RetornaSuccess extends TestCase
{
    public function test(): void
    {
        $r = $this->api('accesorios-modelo', ['id_modelo' => 1]);
        $this->assertTrue($r['success'] ?? false, 'Accesorios por modelo retorna success=true');
    }
}

class Test_AccesoriosModelo_RetornaArray extends TestCase
{
    public function test(): void
    {
        $r = $this->api('accesorios-modelo', ['id_modelo' => 1]);
        $this->assertTrue(is_array($r['accesorios'] ?? null), 'Accesorios retorna array');
    }
}

class Test_AccesoriosModelo_TienenNombre extends TestCase
{
    public function test(): void
    {
        $r = $this->api('accesorios-modelo', ['id_modelo' => 1]);
        $a = $r['accesorios'][0] ?? [];
        $this->assertArrayHasKey('nombre', $a, 'Accesorio tiene campo nombre');
    }
}

class Test_AccesoriosModelo_TienenPrecio extends TestCase
{
    public function test(): void
    {
        $r = $this->api('accesorios-modelo', ['id_modelo' => 1]);
        $a = $r['accesorios'][0] ?? [];
        $this->assertArrayHasKey('precio', $a, 'Accesorio tiene campo precio');
    }
}

// ===========================================
// RESERVA TESTS
// ===========================================

class Test_Reservar_SinLoginRetornaError extends TestCase
{
    public function test(): void
    {
        $r = $this->api('reservar', ['vehiculo_id' => 1], 'POST');
        $this->assertFalse($r['success'] ?? true, 'Reservar sin login retorna error');
    }
}

class Test_Reservar_ComoVendedorRetornaError extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'vendedor@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('reservar', ['vehiculo_id' => 1], 'POST');
        $this->assertFalse($r['success'] ?? true, 'Reservar como vendedor retorna error');
    }
}

class Test_MisReservas_SinLoginRetornaError extends TestCase
{
    public function test(): void
    {
        $r = $this->api('mis-reservas');
        $this->assertFalse($r['success'] ?? true, 'Mis reservas sin login retorna error');
    }
}

class Test_MisReservas_ComoClienteRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $r = $this->api('mis-reservas');
        $this->assertTrue($r['success'] ?? false, 'Mis reservas como cliente retorna success');
    }
}

class Test_MisReservas_RetornaArrayReservas extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $r = $this->api('mis-reservas');
        $this->assertTrue(is_array($r['reservas'] ?? null), 'Mis reservas retorna array');
    }
}

// ===========================================
// VENTA TESTS (Vendedor)
// ===========================================

class Test_MisVentas_SinLoginRetornaError extends TestCase
{
    public function test(): void
    {
        $r = $this->api('mis-ventas');
        $this->assertFalse($r['success'] ?? true, 'Mis ventas sin login retorna error');
    }
}

class Test_MisVentas_ComoVendedorRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'vendedor@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('mis-ventas');
        $this->assertTrue($r['success'] ?? false, 'Mis ventas como vendedor retorna success');
    }
}

class Test_MisVentas_RetornaArrayVentas extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'vendedor@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('mis-ventas');
        $this->assertTrue(is_array($r['ventas'] ?? null), 'Mis ventas retorna array');
    }
}

class Test_ReservasPendientes_ComoVendedor extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'vendedor@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('reservas-pendientes');
        $this->assertTrue($r['success'] ?? false, 'Reservas pendientes como vendedor retorna success');
    }
}

// ===========================================
// COMPRAS TESTS (Cliente)
// ===========================================

class Test_MisCompras_SinLoginRetornaError extends TestCase
{
    public function test(): void
    {
        $r = $this->api('mis-compras');
        $this->assertFalse($r['success'] ?? true, 'Mis compras sin login retorna error');
    }
}

class Test_MisCompras_ComoClienteRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'cliente@test.com', 'password' => 'password'], 'POST');
        $r = $this->api('mis-compras');
        $this->assertTrue($r['success'] ?? false, 'Mis compras como cliente retorna success');
    }
}

// ===========================================
// ADMIN TESTS - Reportes
// ===========================================

class Test_ReporteVehiculosCotizados_SinAdminRetornaError extends TestCase
{
    public function test(): void
    {
        $r = $this->api('reporte-vehiculos-cotizados');
        $this->assertFalse($r['success'] ?? true, 'Reporte sin admin retorna error');
    }
}

class Test_ReporteVehiculosCotizados_ComoAdminRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('reporte-vehiculos-cotizados');
        $this->assertTrue($r['success'] ?? false, 'Reporte vehiculos como admin retorna success');
    }
}

class Test_ReporteAccesorios_ComoAdminRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('reporte-accesorios-solicitados');
        $this->assertTrue($r['success'] ?? false, 'Reporte accesorios como admin retorna success');
    }
}

class Test_ReporteVentasNoConcretadas_ComoAdminRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('reporte-ventas-no-concretadas');
        $this->assertTrue($r['success'] ?? false, 'Reporte ventas no concretadas retorna success');
    }
}

class Test_EstadisticasModelos_ComoAdminRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('estadisticas-modelos-vendidos');
        $this->assertTrue($r['success'] ?? false, 'Estadísticas modelos retorna success');
    }
}

class Test_EstadisticasComisiones_ComoAdminRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('estadisticas-comisiones');
        $this->assertTrue($r['success'] ?? false, 'Estadísticas comisiones retorna success');
    }
}

// ===========================================
// ADMIN TESTS - Gestión Productos
// ===========================================

class Test_BuscarProducto_SinAdminRetornaError extends TestCase
{
    public function test(): void
    {
        $this->api('logout');  // Ensure no session
        $r = $this->api('buscar-producto', ['q' => 'Toyota']);
        $this->assertFalse($r['success'] ?? true, 'Buscar producto sin admin retorna error');
    }
}

class Test_BuscarProducto_ComoAdminRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('buscar-producto', ['q' => 'Toyota']);
        $this->assertTrue($r['success'] ?? false, 'Buscar producto como admin retorna success');
    }
}

class Test_Ofertas_ComoAdminRetornaSuccess extends TestCase
{
    public function test(): void
    {
        $this->api('login', ['email' => 'admin@flycar.com', 'password' => 'password'], 'POST');
        $r = $this->api('ofertas');
        $this->assertTrue($r['success'] ?? false, 'Ofertas como admin retorna success');
    }
}
