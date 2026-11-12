document.addEventListener('DOMContentLoaded', () => {

    const modalLogin = document.getElementById('modal-login-overlay');
    const btnAbrirLogin = document.getElementById('btn-login');
    const btnCerrarLogin = document.getElementById('btn-close-login');
    
    //funcion que muestra el modal 
    function abrirModal() {
        if (modalLogin) {
            modalLogin.classList.remove('oculto');
            document.body.style.overflow = 'hidden';
        }
    }

    //funcion esconder modal
    function cerrarModal () {
        if (modalLogin) {
            modalLogin.classList.add('oculto');
            document.body.style.overflow = '';
        }
    }

    if (btnAbrirLogin && modalLogin && btnCerrarLogin){
        btnAbrirLogin.addEventListener('click', abrirModal);
        btnCerrarLogin.addEventListener('click', cerrarModal);
    }

    // Modal Recuperación de Contraseña
    const modalRecup = document.getElementById('modal-recuperacion-pass');

    window.abrirModalRecuperacion = function() {
        if (modalLogin) modalLogin.classList.add('oculto');
        if (modalRecup) {
            modalRecup.classList.remove('oculto');
            document.body.style.overflow = 'hidden';
        }
    };

    window.cerrarModalRecuperacion = function() {
        if (modalRecup) {
            modalRecup.classList.add('oculto');
            document.body.style.overflow = '';
        }
    };

    //funcion que cambia las tarjetas
    const botonesServicio = document.querySelectorAll('.btn-service');

    botonesServicio.forEach(boton => {
        //mouse entra al boton
        boton.addEventListener('mouseenter', () => {
            const target = boton.getAttribute('data-target');
            const tarjetaDestino = document.getElementById('tarjeta-'+target);

            if (tarjetaDestino) {
                document.querySelectorAll('.tarjeta-info').forEach(t => t.classList.remove('activa'));
                document.querySelectorAll('.btn-service').forEach(b => b.classList.remove('activo'));

                tarjetaDestino.classList.add('activa');
                boton.classList.add('activo');
                tarjetaDestino.classList.add('tarjeta-hover');
            }
        });

        //mouse sale del boton
        boton.addEventListener('mouseleave', () => {
            const target = boton.getAttribute('data-target');
            const tarjeta = document.getElementById('tarjeta-'+target);

            if (tarjeta) {
                tarjeta.classList.remove('tarjeta-hover');
            }
        });
    });

    //revisa si el backend activo el error en el html
    if (modalLogin && modalLogin.dataset.error === 'true') {
        abrirModal();
    }
    
    const btnTogglePass = document.getElementById('btn-toggle-pass');
    const inputContrasena = document.getElementById('contrasena');
    const imgOjo = document.getElementById('eye-icon');

    //funcion mostrar pass
    if (btnTogglePass && inputContrasena) {
        btnTogglePass.addEventListener('click', () => {
            const tipoActual = inputContrasena.getAttribute('type');
            const nuevoTipo = tipoActual === 'password' ? 'text' : 'password';

            inputContrasena.setAttribute('type', nuevoTipo);

            if (nuevoTipo === 'text') {
                imgOjo.style.opacity = '1';
            } else {
                imgOjo.style.opacity = '0.5';
            }
        });
    }

    // Tecla Escape para cerrar modales
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cerrarModal();
            cerrarModalRecuperacion();
        }
    });
});

// ==========================================================================
// FUNCIONES DE RECUPERACIÓN DE CONTRASEÑA (MIGRADAS DESDE LANDING.PHP)
// ==========================================================================

function abrirModalRecuperacion() {
    const modalLogin = document.getElementById('modal-login-overlay');
    const modalRecup = document.getElementById('modal-recuperacion-pass');
    if (modalLogin) modalLogin.classList.add('oculto');
    if (modalRecup) {
        modalRecup.classList.remove('oculto');
        document.body.style.overflow = 'hidden';
        // Resetear al paso 1
        const p1 = document.getElementById('recup-paso-1');
        const p2 = document.getElementById('recup-paso-2');
        const p3 = document.getElementById('recup-paso-3');
        if (p1) p1.style.display = 'block';
        if (p2) p2.style.display = 'none';
        if (p3) p3.style.display = 'none';
        const errBox = document.getElementById('recup-alerta-error');
        if (errBox) errBox.style.display = 'none';
    }
}

function cerrarModalRecuperacion() {
    const modalRecup = document.getElementById('modal-recuperacion-pass');
    if (modalRecup) {
        modalRecup.classList.add('oculto');
        document.body.style.overflow = '';
    }
}

function volverAPaso1Recup() {
    const p1 = document.getElementById('recup-paso-1');
    const p2 = document.getElementById('recup-paso-2');
    if (p2) p2.style.display = 'none';
    if (p1) p1.style.display = 'block';
    const input = document.getElementById('recup_identificador');
    if (input) {
        input.focus();
        input.select();
    }
}

function ejecutarVerificarCuenta() {
    const idenInput = document.getElementById('recup_identificador');
    const errBox = document.getElementById('recup-alerta-error');
    const btn = document.getElementById('btn-verificar-recup');
    const iden = idenInput ? idenInput.value.trim() : '';

    if (!iden) {
        if (errBox) {
            errBox.textContent = 'Por favor ingresa tu usuario o correo.';
            errBox.style.display = 'block';
        }
        return;
    }

    if (errBox) errBox.style.display = 'none';
    if (btn) {
        btn.textContent = 'Verificando...';
        btn.disabled = true;
    }

    const formData = new FormData();
    formData.append('identificador', iden);
    // sistema de cabeceras csrf antidayanas
    const csrfInput = document.querySelector('input[name="' + (window.CSRF_TOKEN || 'csrf_test_name') + '"]') || document.querySelector('input[type="hidden"][name*="csrf"]');
    if (csrfInput) {
        formData.append(csrfInput.name, csrfInput.value);
    }

    const baseUrl = (window.BASE_URL || '/').replace(/\/?$/, '/');

    fetch(baseUrl + 'login/verificar-recuperacion', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.textContent = 'Verificar Cuenta';
            btn.disabled = false;
        }
        if (data.exito) {
            const nombreEl = document.getElementById('recup-usuario-nombre');
            if (nombreEl) nombreEl.textContent = data.nombre || data.usuario;
            const correoEl = document.getElementById('recup-correo-destino');
            if (correoEl) correoEl.textContent = data.correo_ofuscado;

            const devCard = document.getElementById('recup-dev-card');
            const devLink = document.getElementById('recup-dev-link');
            if (data.enlace_directo && devCard && devLink) {
                devLink.href = data.enlace_directo;
                devCard.style.display = 'block';
            } else if (devCard) {
                devCard.style.display = 'none';
            }

            document.getElementById('recup-paso-1').style.display = 'none';
            document.getElementById('recup-paso-2').style.display = 'block';
        } else {
            if (errBox) {
                errBox.textContent = data.mensaje || 'Cuenta no encontrada.';
                errBox.style.display = 'block';
            }
        }
    })
    .catch(err => {
        if (btn) {
            btn.textContent = 'Verificar Cuenta';
            btn.disabled = false;
        }
        if (errBox) {
            errBox.textContent = 'Error al verificar la cuenta. Intenta de nuevo.';
            errBox.style.display = 'block';
        }
    });
}

function volverALoginDesdeRecuperacion() {
    cerrarModalRecuperacion();
    const modalLogin = document.getElementById('modal-login-overlay');
    if (modalLogin) {
        modalLogin.classList.remove('oculto');
        document.body.style.overflow = 'hidden';
    }
}
