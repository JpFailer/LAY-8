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