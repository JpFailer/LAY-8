document.addEventListener('DOMContentLoaded', () => {

    const modalLogin = document.getElementById('modal-login-overlay');
    const btnAbrirLogin = document.getElementById('btn-login');
    const btnCerrarLogin = document.getElementById('btn-close-login');
    
    
    //funcion que muestra el modal 
    function abrirModal() {
        modalLogin.classList.remove('oculto');
        document.body.style.overflow = 'hidden';
    }

    //funcion esconder modal
    function cerrarModal () {
        modalLogin.classList.add('oculto');
        document.body.style.overflow = '';
    }

    if (btnAbrirLogin && modalLogin && btnCerrarLogin){
        btnAbrirLogin.addEventListener('click', abrirModal);
        btnCerrarLogin.addEventListener('click', cerrarModal);
    }

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
                imgOjo.style.opacity = '1'
            } else {
                imgOjo.style.opacity = '0.5'
            }
        });
    }
});