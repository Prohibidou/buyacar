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
    const modalNotificacion = new bootstrap.Modal('#modalNotificacion');
    const modalConfirm = new bootstrap.Modal('#modalConfirm');

    // =========================================
    // FUNCIONES DE NOTIFICACIÓN (reemplazan alert/confirm)
    // =========================================

    /**
     * Muestra un modal de notificación estilizado (reemplaza alert)
     * @param {string} titulo - Título del modal
     * @param {string} contenido - Contenido HTML del mensaje
     * @param {string} tipo - 'success', 'error', 'info', 'warning'
     * @param {function} callback - Función a ejecutar cuando se cierre el modal
     */
    function mostrarNotificacion(titulo, contenido, tipo = 'info', callback = null) {
        const header = $('#notificacion-header');
        const iconos = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        const clases = {
            success: 'bg-success text-white',
            error: 'bg-danger text-white',
            warning: 'bg-warning text-dark',
            info: 'bg-primary text-white'
        };

        header.removeClass('bg-success bg-danger bg-warning bg-primary text-white text-dark')
            .addClass(clases[tipo] || clases.info);

        $('#notificacion-titulo').html(`${iconos[tipo] || iconos.info} ${titulo}`);
        $('#notificacion-contenido').html(contenido);

        // Configurar callback al cerrar
        if (callback) {
            $('#modalNotificacion').off('hidden.bs.modal').on('hidden.bs.modal', function () {
                callback();
                $(this).off('hidden.bs.modal');
            });
        }

        modalNotificacion.show();
    }

    /**
     * Muestra un modal de confirmación estilizado (reemplaza confirm)
     * @param {string} titulo - Título del modal
     * @param {string} contenido - Contenido HTML del mensaje
     * @param {function} onConfirm - Función a ejecutar si confirma
     * @param {function} onCancel - Función a ejecutar si cancela
     */
    function mostrarConfirm(titulo, contenido, onConfirm, onCancel = null) {
        $('#confirm-titulo').text(titulo);
        $('#confirm-contenido').html(contenido);

        // Limpiar handlers anteriores
        $('#btn-confirm-aceptar').off('click');
        $('#btn-confirm-cancelar').off('click');
        $('#modalConfirm').off('hidden.bs.modal');

        // Configurar handlers
        $('#btn-confirm-aceptar').on('click', function () {
            modalConfirm.hide();
            if (onConfirm) onConfirm();
        });

        $('#btn-confirm-cancelar').on('click', function () {
            modalConfirm.hide();
            if (onCancel) onCancel();
        });

        $('#modalConfirm').on('hidden.bs.modal', function () {
            // Si se cierra sin confirmar, ejecutar onCancel
        });

        modalConfirm.show();
    }

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
                mostrarNotificacion(
                    '¡Registro Exitoso!',
                    '<p>Tu cuenta ha sido creada correctamente.</p><p>Ahora puedes iniciar sesión con tu email y contraseña.</p>',
                    'success',
                    function () {
                        $('#form-registro')[0].reset();
                        $('[data-bs-target="#tab-login"]').click();
                    }
                );
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
                                        📅 ${v.anio} &nbsp; ${v.descripcion || ''}
                                    </p>
                                    <p class="precio mb-3">$${Number(v.precio).toLocaleString()}</p>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary btn-sm btn-ver-detalle" data-id="${v.idVehiculo}">Ver Detalle</button>
                                        <button class="btn btn-success btn-sm btn-reservar" data-id="${v.idVehiculo}">Reservar</button>
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
                            <span class="badge badge-${(v.estadoVehiculo || 'disponible').toLowerCase()} mb-3">${v.estadoVehiculo || 'DISPONIBLE'}</span>
                            <div class="specs">
                                <p><span>Año</span> <strong>${v.anio}</strong></p>
                                <p><span>Chasis</span> <strong>${v.nroChasis || 'N/A'}</strong></p>
                            </div>
                            <p class="text-muted mt-3">${v.descripcion || 'Sin descripción'}</p>
                            <div class="precio">$${Number(v.precio).toLocaleString()}</div>
                            ${(v.estadoVehiculo || 'DISPONIBLE') === 'DISPONIBLE' ?
                        `<button class="btn btn-success btn-lg w-100 btn-reservar-modal" data-id="${v.idVehiculo}">Reservar Vehículo</button>` :
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

    // Variables para manejar el estado de la reserva
    let vehiculoReserva = null;
    let accesoriosDisponibles = [];

    $(document).on('click', '.btn-reservar, .btn-reservar-modal', function () {
        const id = $(this).data('id');

        api('session').done(function (resp) {
            if (!resp.logueado) {
                mostrarNotificacion(
                    'Iniciar Sesión Requerido',
                    '<p>Debes iniciar sesión para hacer una reserva.</p><p>Por favor ingresa con tu cuenta o regístrate si aún no tienes una.</p>',
                    'warning',
                    function () {
                        modalVehiculo.hide();
                        modalAuth.show();
                    }
                );
                return;
            }

            if (resp.usuario.rol !== 'CLIENTE') {
                mostrarNotificacion(
                    'Acción No Permitida',
                    '<p>Solo los clientes pueden hacer reservas.</p><p>Los vendedores y administradores deben usar una cuenta de cliente para reservar vehículos.</p>',
                    'error'
                );
                return;
            }

            // Cargar vehículo y accesorios disponibles
            api('vehiculo', { id }).done(function (vResp) {
                if (vResp.success) {
                    vehiculoReserva = vResp.vehiculo;

                    // Cargar accesorios para el modelo del vehículo
                    api('accesorios-modelo', { id_modelo: vehiculoReserva.idModelo }).done(function (accResp) {
                        accesoriosDisponibles = accResp.success ? accResp.accesorios : [];
                        mostrarModalReservaConAccesorios();
                    }).fail(function () {
                        accesoriosDisponibles = [];
                        mostrarModalReservaConAccesorios();
                    });
                }
            });
        });
    });

    function mostrarModalReservaConAccesorios() {
        const v = vehiculoReserva;
        const precioVehiculo = Number(v.precio);

        // Generar HTML de accesorios
        let accesoriosHtml = '';
        if (accesoriosDisponibles.length > 0) {
            accesoriosHtml = `
                <div class="mb-3">
                    <h6 class="fw-bold">🔧 Accesorios Disponibles</h6>
                    <div class="accesorios-lista border rounded p-2">
            `;
            accesoriosDisponibles.forEach(function (acc) {
                accesoriosHtml += `
                    <div class="form-check">
                        <input class="form-check-input accesorio-check" type="checkbox" 
                               value="${acc.idAccesorio}" 
                               data-precio="${acc.precio}"
                               data-nombre="${acc.nombre}"
                               id="acc-${acc.idAccesorio}">
                        <label class="form-check-label d-flex justify-content-between w-100" for="acc-${acc.idAccesorio}">
                            <span>${acc.nombre}</span>
                            <span class="text-primary fw-bold">+$${Number(acc.precio).toLocaleString()}</span>
                        </label>
                    </div>
                `;
            });
            accesoriosHtml += `
                    </div>
                </div>
            `;
        }

        // Calcular precios iniciales (sin accesorios)
        const subtotalInicial = precioVehiculo;
        const ivaInicial = subtotalInicial * 0.21;
        const totalInicial = subtotalInicial + ivaInicial;
        const senaInicial = totalInicial * 0.05;

        $('#reserva-info').html(`
            <div class="reserva-detalle">
                <p><strong>🚗 Vehículo:</strong> ${v.marca} ${v.modelo} ${v.anio}</p>
                
                ${accesoriosHtml}
                
                <hr>
                <div class="desglose-precios">
                    <div class="d-flex justify-content-between">
                        <span>Precio vehículo:</span>
                        <span id="precio-vehiculo">$${precioVehiculo.toLocaleString()}</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted" id="linea-accesorios" style="display: none !important;">
                        <span>Accesorios:</span>
                        <span id="precio-accesorios">$0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span id="subtotal">$${subtotalInicial.toLocaleString()}</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted">
                        <span>IVA (21%):</span>
                        <span id="precio-iva">$${ivaInicial.toLocaleString()}</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 mt-2 pt-2 border-top">
                        <span>Total:</span>
                        <span id="precio-total" class="text-primary">$${totalInicial.toLocaleString()}</span>
                    </div>
                </div>
                <hr>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><strong>Seña a pagar (5%):</strong></span>
                        <span id="precio-sena" class="text-success fw-bold fs-5">$${senaInicial.toLocaleString()}</span>
                    </div>
                    <p class="text-muted small mb-0 mt-1">La seña se descontará del precio final.</p>
                </div>
                <p class="mt-2 mb-0"><strong>📅 Validez de reserva:</strong> 7 días</p>
            </div>
        `);

        $('#btn-confirmar-reserva').data('vehiculo-id', v.idVehiculo);
        modalVehiculo.hide();
        modalReserva.show();

        // Configurar eventos para recalcular precios
        $('.accesorio-check').off('change').on('change', recalcularPreciosReserva);
    }

    function recalcularPreciosReserva() {
        const precioVehiculo = Number(vehiculoReserva.precio);
        let precioAccesorios = 0;

        $('.accesorio-check:checked').each(function () {
            precioAccesorios += Number($(this).data('precio'));
        });

        const subtotal = precioVehiculo + precioAccesorios;
        const iva = subtotal * 0.21;
        const total = subtotal + iva;
        const sena = total * 0.05;

        // Actualizar UI
        $('#linea-accesorios').css('display', precioAccesorios > 0 ? 'flex' : 'none');
        $('#precio-accesorios').text('$' + precioAccesorios.toLocaleString());
        $('#subtotal').text('$' + subtotal.toLocaleString());
        $('#precio-iva').text('$' + iva.toLocaleString());
        $('#precio-total').text('$' + total.toLocaleString());
        $('#precio-sena').text('$' + sena.toLocaleString());
    }

    // Confirmar reserva
    $('#btn-confirmar-reserva').click(function () {
        const vehiculoId = $(this).data('vehiculo-id');

        // Recoger accesorios seleccionados
        const accesoriosSeleccionados = [];
        $('.accesorio-check:checked').each(function () {
            accesoriosSeleccionados.push($(this).val());
        });

        api('reservar', {
            vehiculo_id: vehiculoId,
            accesorios: accesoriosSeleccionados
        }, 'POST').done(function (resp) {
            modalReserva.hide();
            if (resp.success) {
                // Construir mensaje con desglose
                let desgloseHtml = '';
                if (resp.desglose) {
                    desgloseHtml = `
                        <hr>
                        <p class="mb-1"><strong>Desglose:</strong></p>
                        <p class="mb-0 small">Vehículo: $${Number(resp.desglose.precio_vehiculo).toLocaleString()}</p>
                        ${resp.desglose.precio_accesorios > 0 ? `<p class="mb-0 small">Accesorios: $${Number(resp.desglose.precio_accesorios).toLocaleString()}</p>` : ''}
                        <p class="mb-0 small">IVA (21%): $${Number(resp.desglose.iva_21).toLocaleString()}</p>
                        <p class="mb-0 small fw-bold">Total: $${Number(resp.desglose.total).toLocaleString()}</p>
                    `;
                }

                mostrarNotificacion(
                    '¡Reserva Realizada!',
                    `<p><strong>Número de Reserva:</strong> #${resp.reserva_id}</p>
                     <p><strong>Seña Pagada:</strong> $${Number(resp.monto_sena).toLocaleString()}</p>
                     <p><strong>Válida hasta:</strong> ${resp.fecha_expiracion}</p>
                     ${desgloseHtml}
                     <hr>
                     <p class="text-muted small">Puedes ver el estado de tu reserva en "Mis Reservas".</p>`,
                    'success',
                    function () {
                        cargarVehiculos();
                    }
                );
            } else {
                mostrarNotificacion(
                    'Error en la Reserva',
                    `<p>${resp.error}</p>`,
                    'error'
                );
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
        const id = $(this).data('id');

        mostrarConfirm(
            'Cancelar Reserva',
            `<p>¿Estás seguro de que deseas cancelar esta reserva?</p>
             <p class="text-muted small">Se te devolverá el monto de la seña.</p>`,
            function () {
                // onConfirm
                api('cancelar-reserva', { reserva_id: id }, 'POST').done(function (resp) {
                    if (resp.success) {
                        mostrarNotificacion(
                            'Reserva Cancelada',
                            `<p>Tu reserva ha sido cancelada exitosamente.</p>
                             <p><strong>Monto Devuelto:</strong> $${Number(resp.monto_devuelto).toLocaleString()}</p>`,
                            'success',
                            function () {
                                cargarMisReservas();
                            }
                        );
                    } else {
                        mostrarNotificacion(
                            'Error',
                            `<p>${resp.error}</p>`,
                            'error'
                        );
                    }
                });
            }
        );
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

            // Cargar reservas pendientes DESPUÉS de que termine la lista de ventas
            cargarReservasPendientes();
        });
    }

    function cargarReservasPendientes() {
        api('reservas-pendientes').done(function (resp) {
            if (resp.success && resp.reservas.length > 0) {
                let html = '<h5 class="mt-4 mb-3 text-warning">📋 Reservas Pendientes de Confirmar</h5>';
                resp.reservas.forEach(function (r) {
                    html += `
                        <div class="list-item border-warning">
                            <div class="info">
                                <h5>${r.marca} ${r.modelo}</h5>
                                <p>Reserva #${r.nroReserva} - ${r.fechaHoraGenerada}</p>
                                <p>Cliente: ${r.cliente_nombre}</p>
                                <p>Seña pagada: $${Number(r.importe).toLocaleString()}</p>
                            </div>
                            <div class="text-end">
                                <p class="mb-2"><strong>Total:</strong> $${Number(r.importeFinal).toLocaleString()}</p>
                                <button type="button" class="btn btn-success btn-sm btn-confirmar-venta" data-reserva-id="${r.nroReserva}">
                                    ✅ Confirmar Venta
                                </button>
                            </div>
                        </div>
                    `;
                });
                $('#ventas-list').append(html);
            }
        });
    }

    // Confirmar venta desde reserva pendiente
    $(document).on('click', '.btn-confirmar-venta', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(this);
        const reservaId = $btn.data('reserva-id');

        mostrarConfirm(
            'Confirmar Venta',
            `<p>¿Confirmar esta venta?</p>
             <p class="text-muted small">Esta acción completará la transacción y registrará la venta en el sistema.</p>`,
            function () {
                // onConfirm
                $btn.prop('disabled', true).text('Procesando...');

                api('venta', { reserva_id: reservaId }, 'POST').done(function (resp) {
                    if (resp.success) {
                        mostrarNotificacion(
                            '¡Venta Confirmada!',
                            `<p><strong>Venta #:</strong> ${resp.venta_id}</p>
                             <p><strong>Importe Total:</strong> $${Number(resp.importe_final).toLocaleString()}</p>
                             <p><strong>Tu Comisión:</strong> <span class="text-success">$${Number(resp.comision).toLocaleString()}</span></p>`,
                            'success',
                            function () {
                                $btn.closest('.list-item').fadeOut(300, function () {
                                    $(this).remove();
                                    cargarMisVentas();
                                });
                            }
                        );
                    } else {
                        mostrarNotificacion(
                            'Error en la Venta',
                            `<p>${resp.error}</p>`,
                            'error'
                        );
                        $btn.prop('disabled', false).text('✅ Confirmar Venta');
                    }
                }).fail(function () {
                    mostrarNotificacion(
                        'Error de Conexión',
                        '<p>No se pudo conectar con el servidor. Por favor intenta de nuevo.</p>',
                        'error'
                    );
                    $btn.prop('disabled', false).text('✅ Confirmar Venta');
                });
            },
            function () {
                // onCancel - no hacer nada
            }
        );
    });

});
