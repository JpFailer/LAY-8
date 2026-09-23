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