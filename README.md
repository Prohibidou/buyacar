# 🚗 FLY Car - Car Dealership Management System

A PHP MVC application for managing a car dealership, including vehicle quotes, reservations, and sales.

## Features

- **User Management**: Clients, Sellers, and Administrators
- **Quote System**: Generate quotes for vehicles with accessories
- **Reservation System**: Reserve vehicles with a deposit
- **Sales Management**: Complete sales with commission tracking
- **Offer Management**: Apply discounts to vehicles and accessories

## Tech Stack

- **Backend**: PHP 8+ (Laravel-style MVC)
- **Database**: MySQL / MariaDB
- **Server**: WAMP (Windows, Apache, MySQL, PHP)
- **Frontend**: HTML5, CSS3, JavaScript, jQuery, Bootstrap 5

## Project Status

🚧 Under Development

---

## Installation

### Prerequisites
- [WAMP Server](https://www.wampserver.com/) (Windows) or XAMPP
- PHP 8.0+
- MySQL 5.7+ or MariaDB

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/Prohibidou/buyacar.git
   cd buyacar
   ```

2. **Move to WAMP www folder**
   ```bash
   # Copy project to: C:\wamp64\www\buyacar
   ```

3. **Start WAMP Server**
   - Ensure Apache and MySQL are running (icon should be green)

4. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `concesionaria_laravel`
   - Import file: `database/schema.sql`

5. **Access the Application**
   - Open: `http://localhost/buyacar/`

### Default Users

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@flycar.com | password |
| Seller | vendedor@flycar.com | password |
| Client | cliente@test.com | password |

---

## Class Diagram (G1 Domain Model)

```mermaid
classDiagram
    class Cliente {
        +string dni
        +string nombre
        +string apellido
        +date fechaNacimiento
        +string direccion
    }
    
    class Vendedor {
        +string dni
        +string nombre
        +string apellido
        +date fechaNacimiento
        +string direccion
    }
    
    class Administrador {
        +string dni
        +string nombre
        +string apellido
        +date fechaNacimiento
        +string direccion
    }
    
    class Cuenta {
        +int idUsuario
        +string email
        +string contrasenia
        +string tipoUsuario
        +string dniUsuario
    }
    
    class Cotizacion {
        +int idCotizacion
        +datetime fechaCotizacion
        +float importeFinal
        +string dniCliente
    }
    
    class Reserva {
        +int nroReserva
        +datetime fechaReserva
        +string estado
        +int nroPago
        +int idCotizacion
    }
    
    class Venta {
        +int nroVenta
        +datetime fechaDeVenta
        +string descripcionDeVenta
        +string dniVendedor
        +int nroPago
        +int idCotizacion
    }
    
    class Pago {
        +int nroPago
        +datetime fechaDePago
        +string descripcionDePago
    }
    
    class Vehiculo {
        +int idVehiculo
        +string marca
        +string modelo
        +int anio
        +string nroChasis
        +float precio
        +string descripcion
        +string estado
    }
    
    class Accesorio {
        +int idAccesorio
        +string nombreAccesorio
        +string descripcion
        +float precio
        +string estado
    }
    
    class Oferta {
        +int idOferta
        +string descripcionOferta
        +int idProducto
        +date fechaInicio
        +date fechaFin
        +float descuento
    }

    %% Cliente relationships
    Cliente "1" -- "1" Cuenta : posee
    Cliente "1" -- "0..*" Reserva : realiza
    Cliente "1" -- "0..*" Cotizacion : solicita

    %% Vendedor relationships
    Vendedor "1" -- "1" Cuenta : posee
    Vendedor "1" -- "0..*" Cotizacion : genera
    Vendedor "1" -- "0..*" Venta : realiza

    %% Administrador relationships
    Administrador "1" -- "1" Cuenta : posee
    Administrador "1" -- "0..*" Oferta : crea

    %% Cotizacion relationships
    Cotizacion "1" -- "1" Cliente : pertenece a
    Cotizacion "1" -- "1" Vendedor : atendida por
    Cotizacion "1" -- "0..*" Vehiculo : contiene
    Cotizacion "1" -- "0..*" Accesorio : contiene
    Cotizacion "1" -- "0..1" Reserva : genera
    Cotizacion "1" -- "0..1" Venta : concreta

    %% Vehiculo/Accesorio inverse
    Vehiculo "1" -- "1" Cotizacion : pertenece a
    Accesorio "1" -- "1" Cotizacion : pertenece a

    %% Reserva relationships
    Reserva "1" -- "1" Cotizacion : proviene de
    Reserva "1" -- "1" Cliente : pertenece a
    Reserva "1" -- "0..1" Pago : tiene

    %% Venta relationships
    Venta "1" -- "1" Cotizacion : proviene de
    Venta "1" -- "1" Vendedor : realizada por
    Venta "1" -- "0..1" Pago : tiene

    %% Pago relationships
    Pago "1" -- "1" Reserva : paga
    Pago "1" -- "1" Venta : paga

    %% Oferta relationships (many-to-many)
    Oferta "*" -- "*" Vehiculo : aplica a
    Oferta "*" -- "*" Accesorio : aplica a
    Oferta "1" -- "1" Administrador : creada por
```
---

## Database Diagram (G1 Data Model)

```mermaid
erDiagram
    Oferta {
        int idOferta PK
        float descuento
        date fechaInicio
        date fechaFin
    }
    
    Accesorios {
        int idAccesorio PK
        varchar nombre
        int stock
        varchar descripcion
        boolean habilitado
        boolean eliminado
        int idOferta FK
    }
    
    TipoUsuarios {
        int idTipoUsuario PK
        varchar descripcion
    }
    
    Usuarios {
        int idUsuario PK
        varchar email
        varchar contrasenia
        int idTipoUsuario FK
    }
    
    Clientes {
        varchar dniCliente PK
        varchar nombre
        varchar apellido
        date fechaNacimiento
        varchar direccion
        varchar email
        int idUsuario FK
    }
    
    Vendedores {
        varchar dniVendedor PK
        varchar nombre
        varchar apellido
        int idUsuario FK
    }
    
    Cotizaciones {
        int idCotizacion PK
        datetime fechaHoraGenerada
        float importeFinal
        boolean valida
        datetime fechaHoraVencimiento
        varchar dniCliente FK
    }
    
    Marcas {
        int idMarca PK
        varchar nombre
    }
    
    Modelos {
        int idModelo PK
        varchar nombre
        int idMarca FK
    }
    
    Modelos_Accesorios {
        int idModelo PK
        int idAccesorio PK
        float precio
    }
    
    Vehiculos {
        int idVehiculo PK
        varchar nroChasis
        float precio
        varchar descripcion
        int anio
        varchar imagen
        boolean habilitado
        varchar estadoVehiculo
        boolean eliminado
        int idModelo FK
        int idOferta FK
    }
    
    Cotizaciones_Vehiculos {
        int idCotizacion PK
        int idVehiculo PK
    }
    
    Cotizaciones_Vehiculos_Accesorios {
        int idCotizacion PK
        int idVehiculo PK
        int idAccesorio PK
    }
    
    Pagos {
        int nroPago PK
        datetime fechaHoraGenerado
        float importe
    }
    
    Reservas {
        int nroReserva PK
        datetime fechaHoraGenerada
        varchar estadoReserva
        float importe
        datetime fechaHoraVencimiento
        int idCotizacion FK
        int nroPago FK
    }
    
    Ventas {
        int idVenta PK
        datetime fechaHoraGenerada
        boolean concretada
        float comision
        int nroPago FK
        int idCotizacion FK
        varchar dniVendedor FK
    }

    %% FK Relationships
    Usuarios }o--|| TipoUsuarios : "idTipoUsuario"
    Clientes }o--|| Usuarios : "idUsuario"
    Vendedores }o--|| Usuarios : "idUsuario"
    Accesorios }o--|| Oferta : "idOferta"
    Modelos }o--|| Marcas : "idMarca"
    Vehiculos }o--|| Modelos : "idModelo"
    Vehiculos }o--|| Oferta : "idOferta"
    Modelos_Accesorios }o--|| Modelos : "idModelo"
    Modelos_Accesorios }o--|| Accesorios : "idAccesorio"
    Cotizaciones }o--|| Clientes : "dniCliente"
    Cotizaciones_Vehiculos }o--|| Cotizaciones : "idCotizacion"
    Cotizaciones_Vehiculos }o--|| Vehiculos : "idVehiculo"
    Cotizaciones_Vehiculos_Accesorios }o--|| Cotizaciones_Vehiculos : "idCotizacion+idVehiculo"
    Cotizaciones_Vehiculos_Accesorios }o--|| Accesorios : "idAccesorio"
    Reservas }o--|| Cotizaciones : "idCotizacion"
    Reservas }o--|| Pagos : "nroPago"
    Ventas }o--|| Cotizaciones : "idCotizacion"
    Ventas }o--|| Pagos : "nroPago"
    Ventas }o--|| Vendedores : "dniVendedor"
```

## License

MIT License
