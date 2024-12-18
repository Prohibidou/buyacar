-- =====================================================
-- FLY Car - Sistema de Gestión de Concesionaria
-- Modelo de Datos G1 - 16 Tablas
-- =====================================================

CREATE DATABASE IF NOT EXISTS flycar
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE flycar;

-- =====================================================
-- TABLA: Oferta (E7.0.0)
-- =====================================================
CREATE TABLE Oferta (
    idOferta INT AUTO_INCREMENT PRIMARY KEY,
    descuento DECIMAL(5,2) NOT NULL,
    fechaInicio DATE NOT NULL,
    fechaFin DATE NOT NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: TipoUsuarios (E10.0.0)
-- =====================================================
CREATE TABLE TipoUsuarios (
    idTipoUsuario INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Usuarios (E11.0.0)
-- =====================================================
CREATE TABLE Usuarios (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    contrasenia VARCHAR(255) NOT NULL,
    idTipoUsuario INT NOT NULL,
    FOREIGN KEY (idTipoUsuario) REFERENCES TipoUsuarios(idTipoUsuario)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Clientes (E2.0.0)
-- =====================================================
CREATE TABLE Clientes (
    dniCliente VARCHAR(15) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    fechaNacimiento DATE NULL,
    direccion VARCHAR(255) NULL,
    email VARCHAR(150) NULL,
    idUsuario INT NOT NULL,
    FOREIGN KEY (idUsuario) REFERENCES Usuarios(idUsuario)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Vendedores (E12.0.0)
-- =====================================================
CREATE TABLE Vendedores (
    dniVendedor VARCHAR(15) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    idUsuario INT NOT NULL,
    FOREIGN KEY (idUsuario) REFERENCES Usuarios(idUsuario)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Marcas (E6.0.0)
-- =====================================================
CREATE TABLE Marcas (
    idMarca INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Modelos (E15.0.0)
-- =====================================================
CREATE TABLE Modelos (
    idModelo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    idMarca INT NOT NULL,
    FOREIGN KEY (idMarca) REFERENCES Marcas(idMarca)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Accesorios (E1.0.0)
-- =====================================================
CREATE TABLE Accesorios (
    idAccesorio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    stock INT DEFAULT 0,
    descripcion TEXT NULL,
    habilitado BOOLEAN DEFAULT TRUE,
    eliminado BOOLEAN DEFAULT FALSE,
    idOferta INT NULL,
    FOREIGN KEY (idOferta) REFERENCES Oferta(idOferta)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Modelos_Accesorios (E16.0.0)
-- =====================================================
CREATE TABLE Modelos_Accesorios (
    idModelo INT NOT NULL,
    idAccesorio INT NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (idModelo, idAccesorio),
    FOREIGN KEY (idModelo) REFERENCES Modelos(idModelo),
    FOREIGN KEY (idAccesorio) REFERENCES Accesorios(idAccesorio)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Vehiculos (E14.0.0)
-- =====================================================
CREATE TABLE Vehiculos (
    idVehiculo INT AUTO_INCREMENT PRIMARY KEY,
    nroChasis VARCHAR(50) NOT NULL UNIQUE,
    precio DECIMAL(12,2) NOT NULL,
    descripcion TEXT NULL,
    anio INT NOT NULL,
    imagen VARCHAR(255) NULL,
    habilitado BOOLEAN DEFAULT TRUE,
    estadoVehiculo VARCHAR(50) DEFAULT 'DISPONIBLE',
    eliminado BOOLEAN DEFAULT FALSE,
    idModelo INT NOT NULL,
    idOferta INT NULL,
    FOREIGN KEY (idModelo) REFERENCES Modelos(idModelo),
    FOREIGN KEY (idOferta) REFERENCES Oferta(idOferta)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Cotizaciones (E3.0.0)
-- =====================================================
CREATE TABLE Cotizaciones (
    idCotizacion INT AUTO_INCREMENT PRIMARY KEY,
    fechaHoraGenerada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    importeFinal DECIMAL(12,2) NOT NULL,
    valida BOOLEAN DEFAULT TRUE,
    fechaHoraVencimiento DATETIME NOT NULL,
    dniCliente VARCHAR(15) NOT NULL,
    FOREIGN KEY (dniCliente) REFERENCES Clientes(dniCliente)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Cotizaciones_Vehiculos (E4.0.0)
-- =====================================================
CREATE TABLE Cotizaciones_Vehiculos (
    idCotizacion INT NOT NULL,
    idVehiculo INT NOT NULL,
    PRIMARY KEY (idCotizacion, idVehiculo),
    FOREIGN KEY (idCotizacion) REFERENCES Cotizaciones(idCotizacion),
    FOREIGN KEY (idVehiculo) REFERENCES Vehiculos(idVehiculo)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Cotizaciones_Vehiculos_Accesorios (E5.0.0)
-- =====================================================
CREATE TABLE Cotizaciones_Vehiculos_Accesorios (
    idCotizacion INT NOT NULL,
    idVehiculo INT NOT NULL,
    idAccesorio INT NOT NULL,
    PRIMARY KEY (idCotizacion, idVehiculo, idAccesorio),
    FOREIGN KEY (idCotizacion, idVehiculo) REFERENCES Cotizaciones_Vehiculos(idCotizacion, idVehiculo),
    FOREIGN KEY (idAccesorio) REFERENCES Accesorios(idAccesorio)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Pagos (E8.0.0)
-- =====================================================
CREATE TABLE Pagos (
    nroPago INT AUTO_INCREMENT PRIMARY KEY,
    fechaHoraGenerado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    importe DECIMAL(12,2) NOT NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Reservas (E9.0.0)
-- =====================================================
CREATE TABLE Reservas (
    nroReserva INT AUTO_INCREMENT PRIMARY KEY,
    fechaHoraGenerada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estadoReserva VARCHAR(50) DEFAULT 'ACTIVA',
    importe DECIMAL(12,2) NOT NULL,
    fechaHoraVencimiento DATETIME NOT NULL,
    idCotizacion INT NOT NULL,
    nroPago INT NULL,
    FOREIGN KEY (idCotizacion) REFERENCES Cotizaciones(idCotizacion),
    FOREIGN KEY (nroPago) REFERENCES Pagos(nroPago)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: Ventas (E13.0.0)
-- =====================================================
CREATE TABLE Ventas (
    idVenta INT AUTO_INCREMENT PRIMARY KEY,
    fechaHoraGenerada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    concretada BOOLEAN DEFAULT FALSE,
    comision DECIMAL(12,2) NOT NULL,
    nroPago INT NULL,
    idCotizacion INT NOT NULL,
    dniVendedor VARCHAR(15) NOT NULL,
    FOREIGN KEY (nroPago) REFERENCES Pagos(nroPago),
    FOREIGN KEY (idCotizacion) REFERENCES Cotizaciones(idCotizacion),
    FOREIGN KEY (dniVendedor) REFERENCES Vendedores(dniVendedor)
) ENGINE=InnoDB;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Tipos de Usuario
INSERT INTO TipoUsuarios (descripcion) VALUES 
('CLIENTE'), ('VENDEDOR'), ('ADMINISTRADOR');

-- Usuario Admin (password: admin123)
INSERT INTO Usuarios (email, contrasenia, idTipoUsuario) VALUES
('admin@flycar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);

-- Usuario Vendedor (password: vendedor123)
INSERT INTO Usuarios (email, contrasenia, idTipoUsuario) VALUES
('vendedor@flycar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);
INSERT INTO Vendedores (dniVendedor, nombre, apellido, idUsuario) VALUES 
('20123456', 'Juan', 'Pérez', 2);

-- Usuario Cliente (password: cliente123)
INSERT INTO Usuarios (email, contrasenia, idTipoUsuario) VALUES
('cliente@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
INSERT INTO Clientes (dniCliente, nombre, apellido, fechaNacimiento, direccion, email, idUsuario) VALUES 
('30987654', 'María', 'García', '1990-05-15', 'Av. Siempre Viva 123', 'cliente@test.com', 3);

-- Marcas
INSERT INTO Marcas (nombre) VALUES 
('Toyota'), ('Honda'), ('Ford'), ('Volkswagen'), ('Chevrolet'), ('Nissan'), ('Jeep');

-- Modelos
INSERT INTO Modelos (nombre, idMarca) VALUES
('Corolla', 1), ('CR-V', 2), ('Ranger', 3), ('Golf', 4), ('Camaro', 5), ('Sentra', 6), ('Wrangler', 7);

-- Vehículos
INSERT INTO Vehiculos (nroChasis, precio, descripcion, anio, habilitado, estadoVehiculo, idModelo) VALUES
('ABC123456789', 25000.00, 'Sedan compacto, motor 1.8L, automático', 2023, TRUE, 'DISPONIBLE', 1),
('DEF987654321', 35000.00, 'SUV familiar, motor 2.0L turbo, 4x4', 2023, TRUE, 'DISPONIBLE', 2),
('GHI456789123', 40000.00, 'Pickup doble cabina, diesel, automática', 2022, TRUE, 'DISPONIBLE', 3),
('JKL789123456', 28000.00, 'Hatchback deportivo, motor 1.4T', 2023, TRUE, 'DISPONIBLE', 4),
('MNO321654987', 55000.00, 'Deportivo V8 6.2L, 455HP', 2023, TRUE, 'DISPONIBLE', 5),
('PQR654987321', 22000.00, 'Sedan mediano, excelente consumo', 2023, TRUE, 'DISPONIBLE', 6),
('STU987321654', 48000.00, 'Todo terreno 4x4, techo removible', 2023, TRUE, 'DISPONIBLE', 7);

-- Accesorios
INSERT INTO Accesorios (nombre, stock, descripcion, habilitado) VALUES
('Alfombras de goma', 50, 'Juego de alfombras de goma para todo clima', TRUE),
('Cámara de retroceso', 30, 'Cámara HD con visión nocturna', TRUE),
('Sensor de estacionamiento', 40, 'Sensores delanteros y traseros', TRUE),
('Techo panorámico', 10, 'Techo corredizo panorámico', TRUE);
