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
            imgOjo.style.opacity = '1';
        } else {
            imgOjo.style.opacity = '0.5';
        }
    });
}

// MENU TOGGLE (Perfil Dropdown)
function toggleMenu() {
    const dropdown = document.getElementById('dropdown-perfil');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// PANTALLA COMPLETA TOGGLE
function toggleFocus() {
    const layout = document.querySelector('.layout-grid');
    if (layout) {
        layout.classList.toggle('modo-focus');
    }
    const icono = document.getElementById('focus-icon');

    if (icono) {
        if (icono.classList.contains('bx-expand')) {
            icono.classList.replace('bx-expand', 'bx-exit-fullscreen');
        } else {
            icono.classList.replace('bx-exit-fullscreen', 'bx-expand');
        }
    } 
}

// ==========================================================================
// MODAL DE PERFIL (VENTANA FLOTANTE)
// ==========================================================================

function abrirModalPerfil(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Cierra el menú desplegable del perfil si está abierto
    const menu = document.getElementById('dropdown-perfil');
    if (menu && menu.classList.contains('show')) {
        menu.classList.remove('show');
    }

    const modal = document.getElementById('modal-perfil');
    if (modal) {
        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalPerfil() {
    const modal = document.getElementById('modal-perfil');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

// ==========================================================================
// MODAL DE EDITAR PERFIL Y CARRERA
// ==========================================================================

function abrirModalEditarPerfil(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    cerrarModalPerfil();
    const modal = document.getElementById('modal-editar-perfil');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalEditarPerfil() {
    const modal = document.getElementById('modal-editar-perfil');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function seleccionarCarreraEditar(codigo) {
    const input = document.getElementById('input-editar-carrera');
    if (input) {
        input.value = codigo;
    }
    const opciones = document.querySelectorAll('#modal-editar-perfil .carrera-card-opcion');
    opciones.forEach(card => {
        const check = card.querySelector('.check-radio i');
        if (card.dataset.codigo === codigo) {
            card.classList.add('seleccionada');
            if (check) {
                check.className = 'bx bxs-check-circle';
            }
        } else {
            card.classList.remove('seleccionada');
            if (check) {
                check.className = 'bx bx-circle';
            }
        }
    });

    // Activar validación requerida de contraseña y documento solo si se cambia de carrera
    const carreraInicial = document.getElementById('carrera-inicial-perfil');
    const inputPass = document.getElementById('input-pass-perfil');
    const inputFile = document.querySelector('#modal-editar-perfil input[name="planilla_inscripcion"]');
    if (carreraInicial && inputPass) {
        if (codigo !== carreraInicial.value) {
            inputPass.setAttribute('required', 'required');
            if (inputFile) inputFile.setAttribute('required', 'required');
        } else {
            inputPass.removeAttribute('required');
            if (inputFile) inputFile.removeAttribute('required');
        }
    }
}

function previsualizarFotoPerfil(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('preview-foto-modal');
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ==========================================================================
// MODAL DE AJUSTES & MODO OSCURO
// ==========================================================================

function abrirModalAjustes(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Cierra el menú desplegable del perfil si está abierto
    const menu = document.getElementById('dropdown-perfil');
    if (menu && menu.classList.contains('show')) {
        menu.classList.remove('show');
    }

    const modal = document.getElementById('modal-ajustes');
    if (modal) {
        modal.classList.add('activo');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalAjustes() {
    const modal = document.getElementById('modal-ajustes');
    if (modal) {
        modal.classList.remove('activo');
        document.body.style.overflow = '';
    }
}

// Clics fuera de los menús y modales
window.addEventListener('click', function(event) {
    if (!event.target.closest('.user-menu-container')) {
        const menu = document.getElementById('dropdown-perfil');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
        }
    }

    const modalAjustes = document.getElementById('modal-ajustes');
    if (modalAjustes && event.target === modalAjustes) {
        cerrarModalAjustes();
    }

    const modalPerfil = document.getElementById('modal-perfil');
    if (modalPerfil && event.target === modalPerfil) {
        cerrarModalPerfil();
    }

    const modalEditar = document.getElementById('modal-editar-perfil');
    if (modalEditar && event.target === modalEditar) {
        cerrarModalEditarPerfil();
    }
});

// Cerrar modales al presionar Escape
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalAjustes();
        cerrarModalPerfil();
        cerrarModalEditarPerfil();
    }
});

// Inicializador de Modo Oscuro
function aplicarTema(tema) {
    const esOscuro = (tema === 'dark');
    if (esOscuro) {
        document.documentElement.classList.add('dark-mode');
        document.body.classList.add('dark-mode');
        localStorage.setItem('lay8_theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark-mode');
        document.body.classList.remove('dark-mode');
        localStorage.setItem('lay8_theme', 'light');
    }

    // Actualizar todos los switches en la pantalla
    const switches = document.querySelectorAll('.toggle-dark-mode-input');
    switches.forEach(sw => {
        sw.checked = esOscuro;
    });

    // Actualizar icono visual si existe
    const iconoTema = document.getElementById('icono-tema-visual');
    if (iconoTema) {
        if (esOscuro) {
            iconoTema.className = 'bx bx-moon';
        } else {
            iconoTema.className = 'bx bx-sun';
        }
    }
}

function inicializarModoOscuro() {
    const temaForzadoClaro = document.body && document.body.dataset.temaDefault === 'light';
    let temaGuardado = localStorage.getItem('lay8_theme') || 'light';
    if (temaForzadoClaro && localStorage.getItem('lay8_auth_override') !== 'true') {
        temaGuardado = 'light';
    }
    aplicarTema(temaGuardado);

    const switches = document.querySelectorAll('.toggle-dark-mode-input');
    switches.forEach(sw => {
        sw.addEventListener('change', function() {
            aplicarTema(this.checked ? 'dark' : 'light');
        });
    });
}

// Ejecutar al cargar la página
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarModoOscuro);
} else {
    inicializarModoOscuro();
}
