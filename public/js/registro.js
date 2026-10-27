//FORMULARIO DE REGISTRO

//muestra/oculta el input del carnet si es docente
function mostrarCarnet(esDocente) {
    const divCarnet = document.getElementById('div_carnet');
    const inputCarnet = divCarnet ? divCarnet.querySelector('input[type="file"]') : null;
    const divInscripcion = document.getElementById('div_inscripcion_estudiante');
    const inputInscripcion = document.getElementById('input_planilla_inscripcion');

    if (esDocente) {
        if (divCarnet) divCarnet.style.display = 'block';
        if (inputCarnet) inputCarnet.setAttribute('required', 'required');
        if (divInscripcion) divInscripcion.style.display = 'none';
        if (inputInscripcion) {
            inputInscripcion.removeAttribute('required');
            inputInscripcion.value = '';
            const feedback = document.getElementById('archivo-feedback-inscripcion');
            if (feedback) feedback.style.display = 'none';
        }
    } else {
        if (divCarnet) divCarnet.style.display = 'none';
        if (inputCarnet) {
            inputCarnet.removeAttribute('required');
            inputCarnet.value = '';
        }
        if (divInscripcion) divInscripcion.style.display = 'block';
        // Solo required si no tiene archivo previo guardado en sesión
        const tieneArchivoPrevio = document.getElementById('tiene_archivo_previo');
        if (inputInscripcion && (!tieneArchivoPrevio || tieneArchivoPrevio.value !== '1')) {
            inputInscripcion.setAttribute('required', 'required');
        }
    }
}

//nav del paso 1 al paso 2
function siguientePaso() {
    const paso1 = document.getElementById('paso1');
    const inputsRequeridos = paso1.querySelectorAll('[required]');
    let todoValido = true;

    //validacion nativa
    inputsRequeridos.forEach(input => {
        if (!input.checkValidity()){
            input.reportValidity();
            todoValido = false;
        }
    });

    if (todoValido) {
        document.getElementById('paso1').style.display = 'none';
        document.getElementById('paso2').style.display = 'block';

        document.getElementById('indicator-1').classList.remove('activo');
        document.getElementById('indicator-2').classList.add('activo');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

//nav del paso 2 al paso 1 (boton atras)
function pasoAnterior() {
    document.getElementById('paso2').style.display = 'none';
    document.getElementById('paso1').style.display = 'block';

    document.getElementById('indicator-2').classList.remove('activo');
    document.getElementById('indicator-1').classList.add('activo');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ==========================================================================
// FEEDBACK VISUAL DE CARGA DE DOCUMENTO (PDF / CARNET)
// ==========================================================================

function formatearTamanoArchivo(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function inicializarFeedbackArchivos() {
    const inputInscripcion = document.getElementById('input_planilla_inscripcion');
    const feedbackBox = document.getElementById('archivo-feedback-inscripcion');
    const nombreSpan = document.getElementById('nombre_archivo_inscripcion');
    const tamanoSpan = document.getElementById('tamano_archivo_inscripcion');

    if (inputInscripcion && feedbackBox) {
        inputInscripcion.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (nombreSpan) nombreSpan.textContent = file.name;
                if (tamanoSpan) tamanoSpan.textContent = formatearTamanoArchivo(file.size);
                feedbackBox.style.display = 'flex';
            }
        });
    }
}

function removerArchivoInscripcion() {
    const input = document.getElementById('input_planilla_inscripcion');
    const feedbackBox = document.getElementById('archivo-feedback-inscripcion');
    const tienePrevio = document.getElementById('tiene_archivo_previo');
    
    if (input) {
        input.value = '';
        input.setAttribute('required', 'required');
    }
    if (tienePrevio) {
        tienePrevio.value = '0';
    }
    if (feedbackBox) {
        feedbackBox.style.display = 'none';
    }
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

// ==========================================================================
// VENTANA MODAL DE TÉRMINOS Y CONDICIONES EN REGISTRO
// ==========================================================================

function abrirModalTerminos() {
    const modal = document.getElementById('modal-terminos-registro');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalTerminos() {
    const modal = document.getElementById('modal-terminos-registro');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function aceptarTerminosModal() {
    const checkbox = document.getElementById('terminos');
    if (checkbox) {
        checkbox.checked = true;
    }
    cerrarModalTerminos();
}

// Cerrar modales con Escape
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalCarreras();
        cerrarModalTerminos();
    }
});

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', function() {
    inicializarFeedbackArchivos();
});
