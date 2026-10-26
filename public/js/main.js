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
});

// Cerrar modales al presionar Escape
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalAjustes();
        cerrarModalPerfil();
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
    const temaGuardado = localStorage.getItem('lay8_theme') || 'light';
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
