//FORMULARIO DE REGISTRO

//muestra/oculta el input del carnet si es docente

function mostrarCarnet(esDocente) {
    const divCarnet= document.getElementById('div_carnet')
    const inputCarnet= divCarnet.querySelector('input[type="file"]');

    if (esDocente) {
        divCarnet.style.display= 'block';
        inputCarnet.setAttribute('required','required');
    } else {
        divCarnet.style.display= 'none';
        inputCarnet.removeAttribute('required');
        inputCarnet.value = ''; //limpia el archivo en caso q el user se arrepienta
    }
}

//nav del paso 1 al paso 2

function siguientePaso() {
    const paso1= document.getElementById('paso1');
    const inputsRequeridos = paso1.querySelectorAll('[required]');
    let todoValido = true;

    //validacion
    inputsRequeridos.forEach(input => {
        if (!input.checkValidity()){
            input.reportValidity();
            todoValido = false;
        }
    });

    if (todoValido) {
        document.getElementById('paso1').style.display= 'none';
        document.getElementById('paso2').style.display= 'block';

        document.getElementById('indicator-1').classList.remove('activo');
        document.getElementById('indicator-2').classList.add('activo');
    }
}

//nav del paso 2 al paso 1 (boton atras)

function pasoAnterior() {
        document.getElementById('paso2').style.display= 'none';
        document.getElementById('paso1').style.display= 'block';

        document.getElementById('indicator-2').classList.remove('activo');
        document.getElementById('indicator-1').classList.add('activo');
}

//password toggle
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