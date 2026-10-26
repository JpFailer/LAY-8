//FORMULARIO DE REGISTRO

//muestra/oculta el input del carnet si es docente

function mostrarCarnet(esDocente) {
    const divCarnet= document.getElementById('div_carnet');
    const inputCarnet= divCarnet ? divCarnet.querySelector('input[type="file"]') : null;
    const divInscripcion = document.getElementById('div_inscripcion_estudiante');
    const inputInscripcion = document.getElementById('input_planilla_inscripcion');

    if (esDocente) {
        if (divCarnet) divCarnet.style.display= 'block';
        if (inputCarnet) inputCarnet.setAttribute('required','required');
        if (divInscripcion) divInscripcion.style.display = 'none';
        if (inputInscripcion) {
            inputInscripcion.removeAttribute('required');
            inputInscripcion.value = '';
        }
    } else {
        if (divCarnet) divCarnet.style.display= 'none';
        if (inputCarnet) {
            inputCarnet.removeAttribute('required');
            inputCarnet.value = '';
        }
        if (divInscripcion) divInscripcion.style.display = 'block';
        if (inputInscripcion) inputInscripcion.setAttribute('required', 'required');
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

// ==========================================================================
// VENTANA MODAL SELECCIONADOR DE CARRERAS UMC
// ==========================================================================

let carreraTemporalCodigo = document.getElementById('input_carrera') ? document.getElementById('input_carrera').value : 'ING-INF';
let carreraTemporalNombre = document.getElementById('display_carrera_nombre') ? document.getElementById('display_carrera_nombre').textContent.trim() : 'Ingeniería en Informática';

function abrirModalCarreras() {
    const modal = document.getElementById('modal-carreras-registro');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalCarreras() {
    const modal = document.getElementById('modal-carreras-registro');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function seleccionarOpcionCarrera(codigo, nombre) {
    carreraTemporalCodigo = codigo;
    carreraTemporalNombre = nombre;

    const cards = document.querySelectorAll('#modal-carreras-registro .card-carrera-item');
    cards.forEach(card => {
        const checkIcon = card.querySelector('.carrera-check i');
        if (card.dataset.codigo === codigo) {
            card.classList.add('activa');
            if (checkIcon) {
                checkIcon.className = 'bx bxs-check-circle';
            }
        } else {
            card.classList.remove('activa');
            if (checkIcon) {
                checkIcon.className = 'bx bx-circle';
            }
        }
    });
}

function confirmarCarreraSeleccionada() {
    const input = document.getElementById('input_carrera');
    const codeSpan = document.getElementById('display_carrera_code');
    const nombreSpan = document.getElementById('display_carrera_nombre');

    if (input) input.value = carreraTemporalCodigo;
    if (codeSpan) codeSpan.textContent = carreraTemporalCodigo;
    if (nombreSpan) nombreSpan.textContent = carreraTemporalNombre;

    cerrarModalCarreras();
}

// Cerrar modal de carreras al presionar Escape
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalCarreras();
    }
});
