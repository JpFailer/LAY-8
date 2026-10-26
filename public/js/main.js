// MOSTRAR CONTRASENA 

 const btnTogglePass = document.getElementById('btn-toggle-pass');
    const inputContrasena = document.getElementById('contrasena');
    const imgOjo = document.getElementById('eye-icon');

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

// MENU TOGGLE

function toggleMenu() {
    document.getElementById('dropdown-perfil').classList.toggle('show');
}

window.addEventListener('click', function(event){
    if (!event.target.closest('.user-menu-container')) {
        const menu= document.getElementById('dropdown-perfil');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
        }
    }
});

// PANTALLA COMPLETA TOGGLE
function toggleFocus() {
    document.querySelector('.layout-grid').classList.toggle('modo-focus');
    const icono = document.getElementById('focus-icon');

    if (icono) {
        if(icono.classList.contains('bx-expand')) {
            icono.classList.replace('bx-expand', 'bx-exit-fullscreen');
        } else {
            icono.classList.replace('bx-exit-fullscreen', 'bx-expand');
        }
    } 
} 