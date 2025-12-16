/**
 * FLY Car - Laravel JavaScript
 * Aplicación principal con jQuery
 */

$(document).ready(function () {

    const API_URL = 'api.php';
    let vehiculoSeleccionado = null;

    // Bootstrap modals
    const modalAuth = new bootstrap.Modal('#modalAuth');
    const modalVehiculo = new bootstrap.Modal('#modalVehiculo');
    const modalReserva = new bootstrap.Modal('#modalReserva');

    // =========================================
    // INICIALIZACIÓN
    // =========================================
    verificarSesion();
    cargarVehiculos();

    // =========================================
    // API HELPER
    // =========================================
    function api(action, data = {}, method = 'GET') {
        return $.ajax({
            url: API_URL,
            type: method,
            data: { action, ...data },
            dataType: 'json'
        });
    }

    // =========================================
    // AUTENTICACIÓN
    // =========================================
    function verificarSesion() {
        api('session').done(function (resp) {
            if (resp.logueado) {
                mostrarUsuarioLogueado(resp.usuario);
            } else {
                mostrarBotonLogin();
            }
        });
    }

    function mostrarUsuarioLogueado(usuario) {
        $('#user-menu').html(`
            <span class="text-light me-3">
                <i class="bi bi-person-circle"></i> ${usuario.nombre}
                <span class="badge bg-light text-dark ms-1">${usuario.rol}</span>
            </span>
            <button class="btn btn-outline-light btn-sm" id="btn-logout">Salir</button>
        `);

        $('.auth-required').addClass('visible');

        if (usuario.rol === 'VENDEDOR') {
            $('.vendedor-only').addClass('visible');
        }
    }

    function mostrarBotonLogin() {
        $('#user-menu').html(`
            <button class="btn btn-light btn-sm" id="btn-login">Iniciar Sesión</button>
        `);
        $('.auth-required, .vendedor-only').removeClass('visible');
    }

    // Abrir modal login
    $(document).on('click', '#btn-login', function () {
        modalAuth.show();
    });

    // Logout
    $(document).on('click', '#btn-logout', function () {
        api('logout').done(function () {
            mostrarBotonLogin();
            mostrarPagina('catalogo');
        });
    });

    // Submit Login
    $('#form-login').submit(function (e) {
        e.preventDefault();
        $('#login-error').text('');

        api('login', {
            email: $('#login-email').val(),
            password: $('#login-password').val()
        }, 'POST').done(function (resp) {
            if (resp.success) {
                modalAuth.hide();
                $('#form-login')[0].reset();
                mostrarUsuarioLogueado(resp.usuario);
                cargarVehiculos();
            } else {
                $('#login-error').text(resp.error);
            }
        });
    });

    // Submit Registro
    $('#form-registro').submit(function (e) {
        e.preventDefault();
        $('#registro-error').text('');

        api('registro', {
            nombre: $('#reg-nombre').val(),
            apellido: $('#reg-apellido').val(),
            dni: $('#reg-dni').val(),
            email: $('#reg-email').val(),
            password: $('#reg-password').val(),
            telefono: $('#reg-telefono').val(),
            direccion: $('#reg-direccion').val()
        }, 'POST').done(function (resp) {
            if (resp.success) {
                alert('¡Registro exitoso! Ahora puedes iniciar sesión.');
                $('#form-registro')[0].reset();
                $('[data-bs-target="#tab-login"]').click();
            } else {
                $('#registro-error').text(resp.error);
            }
        });
    });

    // =========================================
    // NAVEGACIÓN
    // =========================================
    $('[data-page]').click(function (e) {
        e.preventDefault();
        mostrarPagina($(this).data('page'));
    });

    function mostrarPagina(page) {
        $('[data-page]').removeClass('active');
        $(`[data-page="${page}"]`).addClass('active');
        $('.page').removeClass('active');
        $(`#page-${page}`).addClass('active');

        switch (page) {
            case 'catalogo': cargarVehiculos(); break;
            case 'mis-reservas': cargarMisReservas(); break;
            case 'mis-compras': cargarMisCompras(); break;
            case 'mis-ventas': cargarMisVentas(); break;
        }
    }

    // =========================================
    // VEHÍCULOS
    // =========================================
    function cargarVehiculos(filtros = {}) {
        $('#vehiculos-grid').html('<div class="col-12 loading">Cargando vehículos...</div>');

        api('vehiculos', filtros).done(function (resp) {
            if (resp.success && resp.vehiculos.length > 0) {
                let html = '';
                resp.vehiculos.forEach(function (v) {
                    html += `
                        <div class="col-md-4 col-lg-3">
                            <div class="card vehiculo-card">
                                <div class="card-img-top">🚗</div>
                                <div class="card-body">
                                    <h5 class="card-title">${v.marca} ${v.modelo}</h5>
                                    <p class="card-text text-muted small">
                                        📅 ${v.anio} &nbsp; 🎨 ${v.color} &nbsp; 📊 ${Number(v.kilometraje).toLocaleString()} km
                                    </p>
                                    <p class="precio mb-3">$${Number(v.precio).toLocaleString()}</p>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary btn-sm btn-ver-detalle" data-id="${v.id}">Ver Detalle</button>
                                        <button class="btn btn-success btn-sm btn-reservar" data-id="${v.id}">Reservar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#vehiculos-grid').html(html);
            } else {
                $('#vehiculos-grid').html('<div class="col-12 empty-state"><div class="icon">🚗</div><p>No hay vehículos disponibles</p></div>');
            }
        });
    }

    // Filtrar
    $('#btn-filtrar').click(function () {
        cargarVehiculos({
            tipo: $('#filtro-tipo').val(),
            marca: $('#filtro-marca').val()
        });
    });

    // Ver detalle
    $(document).on('click', '.btn-ver-detalle', function () {
        const id = $(this).data('id');

        api('vehiculo', { id }).done(function (resp) {
            if (resp.success) {
                const v = resp.vehiculo;
                vehiculoSeleccionado = v;

                $('#vehiculo-detalle').html(`
                    <div class="vehiculo-detalle-grid">
                        <div class="image-section">🚗</div>
                        <div class="info-section">
                            <h4>${v.marca} ${v.modelo} ${v.anio}</h4>
                            <span class="badge badge-${v.estado.toLowerCase()} mb-3">${v.estado}</span>
                            <div class="specs">
                                <p><span>Tipo</span> <strong>${v.tipo}</strong></p>
                                <p><span>Color</span> <strong>${v.color}</strong></p>
                                <p><span>Kilometraje</span> <strong>${Number(v.kilometraje).toLocaleString()} km</strong></p>
                            </div>
                            <p class="text-muted mt-3">${v.descripcion || 'Sin descripción'}</p>
                            <div class="precio">$${Number(v.precio).toLocaleString()}</div>
                            ${v.estado === 'DISPONIBLE' ?
                        `<button class="btn btn-success btn-lg w-100 btn-reservar-modal" data-id="${v.id}">Reservar Vehículo</button>` :
                        '<p class="text-muted">Este vehículo no está disponible</p>'
                    }
                        </div>
                    </div>
                `);
                modalVehiculo.show();
            }
        });
    });

    // =========================================
    // RESERVAS
    // =========================================
    $(document).on('click', '.btn-reservar, .btn-reservar-modal', function () {
        const id = $(this).data('id');

        api('session').done(function (resp) {
            if (!resp.logueado) {
                alert('Debes iniciar sesión para hacer una reserva');
                modalVehiculo.hide();
                modalAuth.show();
                return;
            }

            if (resp.usuario.rol !== 'CLIENTE') {
                alert('Solo los clientes pueden hacer reservas');
                return;
            }

            api('vehiculo', { id }).done(function (vResp) {
                if (vResp.success) {
                    const v = vResp.vehiculo;
                    const sena = Number(v.precio) * 0.05;

                    $('#reserva-info').html(`
                        <p><strong>Vehículo:</strong> ${v.marca} ${v.modelo} ${v.anio}</p>
                        <p><strong>Precio:</strong> $${Number(v.precio).toLocaleString()}</p>
                        <p><strong>Seña a pagar (5%):</strong> <span class="text-success fw-bold">$${sena.toLocaleString()}</span></p>
                        <p><strong>Validez de reserva:</strong> 7 días</p>
                        <hr>
                        <p class="text-muted small">La seña se descontará del precio final al concretar la compra.</p>
                    `);
                    $('#btn-confirmar-reserva').data('vehiculo-id', id);
                    modalVehiculo.hide();
                    modalReserva.show();
                }
            });
        });
    });

    // Confirmar reserva
    $('#btn-confirmar-reserva').click(function () {
        const vehiculoId = $(this).data('vehiculo-id');

        api('reservar', { vehiculo_id: vehiculoId }, 'POST').done(function (resp) {
            if (resp.success) {
                alert(`¡Reserva realizada!\n\nNúmero: ${resp.reserva_id}\nSeña: $${Number(resp.monto_sena).toLocaleString()}\nVálida hasta: ${resp.fecha_expiracion}`);
                modalReserva.hide();
                cargarVehiculos();
            } else {
                alert('Error: ' + resp.error);
            }
        });
    });

    // Cargar mis reservas
    function cargarMisReservas() {
        $('#reservas-list').html('<div class="loading">Cargando reservas...</div>');

        api('mis-reservas').done(function (resp) {
            if (resp.success && resp.reservas.length > 0) {
                let html = '';
                resp.reservas.forEach(function (r) {
                    html += `
                        <div class="list-item">
                            <div class="info">
                                <h5>${r.marca} ${r.modelo}</h5>
                                <p>Reserva #${r.id} - ${r.created_at}</p>
                                <p>Seña: $${Number(r.monto_sena).toLocaleString()} - Expira: ${r.fecha_expiracion}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge badge-${r.estado.toLowerCase()} mb-2">${r.estado}</span><br>
                                ${r.estado === 'ACTIVA' ?
                            `<button class="btn btn-danger btn-sm btn-cancelar-reserva" data-id="${r.id}">Cancelar</button>` :
                            ''
                        }
                            </div>
                        </div>
                    `;
                });
                $('#reservas-list').html(html);
            } else {
                $('#reservas-list').html('<div class="empty-state"><div class="icon">📋</div><p>No tienes reservas</p></div>');
            }
        });
    }

    // Cancelar reserva
    $(document).on('click', '.btn-cancelar-reserva', function () {
        if (confirm('¿Cancelar esta reserva?')) {
            const id = $(this).data('id');
            api('cancelar-reserva', { reserva_id: id }, 'POST').done(function (resp) {
                if (resp.success) {
                    alert(`Reserva cancelada. Monto devuelto: $${Number(resp.monto_devuelto).toLocaleString()}`);
                    cargarMisReservas();
                } else {
                    alert('Error: ' + resp.error);
                }
            });
        }
    });

    // =========================================
    // COMPRAS
    // =========================================
    function cargarMisCompras() {
        $('#compras-list').html('<div class="loading">Cargando compras...</div>');

        api('mis-compras').done(function (resp) {
            if (resp.success && resp.compras.length > 0) {
                let html = '';
                resp.compras.forEach(function (c) {
                    html += `
                        <div class="list-item">
                            <div class="info">
                                <h5>${c.marca} ${c.modelo} ${c.anio}</h5>
                                <p>Compra #${c.id} - ${c.created_at}</p>
                                <p>Método: ${c.metodo_pago}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge badge-completada mb-2">COMPLETADA</span><br>
                                <strong class="precio">$${Number(c.precio_final).toLocaleString()}</strong>
                            </div>
                        </div>
                    `;
                });
                $('#compras-list').html(html);
            } else {
                $('#compras-list').html('<div class="empty-state"><div class="icon">🛒</div><p>No tienes compras</p></div>');
            }
        });
    }

    // =========================================
    // VENTAS (Vendedor)
    // =========================================
    function cargarMisVentas() {
        $('#ventas-list').html('<div class="loading">Cargando ventas...</div>');

        api('mis-ventas').done(function (resp) {
            if (resp.success && resp.ventas.length > 0) {
                let html = '';
                resp.ventas.forEach(function (v) {
                    html += `
                        <div class="list-item">
                            <div class="info">
                                <h5>${v.marca} ${v.modelo} ${v.anio}</h5>
                                <p>Venta #${v.id} - ${v.created_at}</p>
                                <p>Cliente: ${v.cliente_nombre}</p>
                            </div>
                            <div class="text-end">
                                <p class="mb-1"><strong>Venta:</strong> $${Number(v.precio_final).toLocaleString()}</p>
                                <p class="text-success mb-0"><strong>Comisión:</strong> $${Number(v.comision_vendedor).toLocaleString()}</p>
                            </div>
                        </div>
                    `;
                });
                $('#ventas-list').html(html);
            } else {
                $('#ventas-list').html('<div class="empty-state"><div class="icon">💼</div><p>No tienes ventas registradas</p></div>');
            }
        });
    }

});
