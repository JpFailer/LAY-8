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

        // Inicializar estudio de avatar si aún no se ha cargado imagen
        if (!avatarImg) {
            const avatarNav = document.querySelector('.usuario-badge img, .perfil-card-avatar, .foto-usuario');
            const defaultAvatar = (avatarNav && avatarNav.src && !avatarNav.src.includes('data:'))
                ? avatarNav.src
                : (window.BASE_URL ? window.BASE_URL + 'img/usuario.png' : '/img/usuario.png');
            initAvatarCropper(defaultAvatar);
        }
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

// ==========================================================================
// ESTUDIO Y RECORTADOR 1:1 DE AVATAR (REVISIÓN HARYERIT)
// ==========================================================================
let avatarImg = null;
let avatarScale = 1.0;
let avatarMinScale = 1.0;
let avatarPanX = 0;
let avatarPanY = 0;
let avatarIsDragging = false;
let avatarStartX = 0;
let avatarStartY = 0;
let avatarListenersAttached = false;

function initAvatarCropper(imageSrc) {
    const canvas = document.getElementById('canvas-avatar-cropper');
    if (!canvas) return;

    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function() {
        avatarImg = img;
        // Escala base para que la imagen cubra siempre el círculo 1:1 de 200x200
        avatarMinScale = Math.max(200 / img.width, 200 / img.height);
        avatarScale = avatarMinScale;

        // Centrar imagen
        avatarPanX = (200 - img.width * avatarScale) / 2;
        avatarPanY = (200 - img.height * avatarScale) / 2;

        const slider = document.getElementById('slider-zoom-avatar');
        if (slider) slider.value = '1';

        const label = document.getElementById('label-zoom-porcentaje');
        if (label) label.textContent = '100%';

        redibujarAvatarCanvas();
        adjuntarListenersCropper();
    };
    img.src = imageSrc;
}

function redibujarAvatarCanvas() {
    const canvas = document.getElementById('canvas-avatar-cropper');
    if (!canvas || !avatarImg) return;
    const ctx = canvas.getContext('2d');

    const curW = avatarImg.width * avatarScale;
    const curH = avatarImg.height * avatarScale;

    // Límites para que la imagen no se salga del encuadre
    avatarPanX = Math.min(0, Math.max(200 - curW, avatarPanX));
    avatarPanY = Math.min(0, Math.max(200 - curH, avatarPanY));

    ctx.clearRect(0, 0, 200, 200);
    ctx.save();
    ctx.beginPath();
    ctx.arc(100, 100, 100, 0, Math.PI * 2);
    ctx.clip();
    ctx.drawImage(avatarImg, avatarPanX, avatarPanY, curW, curH);
    ctx.restore();
}

function adjuntarListenersCropper() {
    if (avatarListenersAttached) return;
    const canvas = document.getElementById('canvas-avatar-cropper');
    if (!canvas) return;
    avatarListenersAttached = true;

    const guia = document.getElementById('avatar-guia-drag');

    function startDrag(e) {
        avatarIsDragging = true;
        const pt = e.touches ? e.touches[0] : e;
        avatarStartX = pt.clientX;
        avatarStartY = pt.clientY;
        canvas.style.cursor = 'grabbing';
        if (guia) guia.style.opacity = '1';
    }

    function doDrag(e) {
        if (!avatarIsDragging || !avatarImg) return;
        const pt = e.touches ? e.touches[0] : e;
        const dx = pt.clientX - avatarStartX;
        const dy = pt.clientY - avatarStartY;
        avatarStartX = pt.clientX;
        avatarStartY = pt.clientY;
        avatarPanX += dx;
        avatarPanY += dy;
        redibujarAvatarCanvas();
        if (e.cancelable && e.touches) e.preventDefault();
    }

    function stopDrag() {
        avatarIsDragging = false;
        canvas.style.cursor = 'grab';
        if (guia) guia.style.opacity = '0';
    }

    canvas.addEventListener('mousedown', startDrag);
    window.addEventListener('mousemove', doDrag);
    window.addEventListener('mouseup', stopDrag);

    canvas.addEventListener('touchstart', startDrag, { passive: false });
    window.addEventListener('touchmove', doDrag, { passive: false });
    window.addEventListener('touchend', stopDrag);

    // Zoom interactivo con rueda del ratón
    canvas.addEventListener('wheel', function(e) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.06 : 0.06;
        ajustarZoomAvatar(delta);
    }, { passive: false });
}

function cambiarZoomSlider(multiplier) {
    if (!avatarImg) return;
    const newMult = Math.max(1, Math.min(3, parseFloat(multiplier)));
    const newScale = avatarMinScale * newMult;

    // Zoom centrado en (100, 100)
    const factor = newScale / avatarScale;
    avatarPanX = 100 - (100 - avatarPanX) * factor;
    avatarPanY = 100 - (100 - avatarPanY) * factor;
    avatarScale = newScale;

    const label = document.getElementById('label-zoom-porcentaje');
    if (label) label.textContent = Math.round(newMult * 100) + '%';

    redibujarAvatarCanvas();
}

function ajustarZoomAvatar(delta) {
    const slider = document.getElementById('slider-zoom-avatar');
    if (!slider) return;
    let val = parseFloat(slider.value) + delta;
    val = Math.max(1, Math.min(3, val));
    slider.value = val.toFixed(2);
    cambiarZoomSlider(val);
}

function cargarImagenParaRecorte(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            initAvatarCropper(e.target.result);
        };
        reader.readAsDataURL(file);
    }
}

function prepararEnvioAvatar(e) {
    if (!avatarImg) return;
    try {
        // Generar un canvas de alta resolución 400x400 exactamente 1:1
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = 400;
        exportCanvas.height = 400;
        const expCtx = exportCanvas.getContext('2d');

        const factor = 400 / 200;
        expCtx.drawImage(
            avatarImg,
            avatarPanX * factor,
            avatarPanY * factor,
            avatarImg.width * avatarScale * factor,
            avatarImg.height * avatarScale * factor
        );

        const dataUrl = exportCanvas.toDataURL('image/jpeg', 0.92);
        const hiddenInput = document.getElementById('input-foto-recortada');
        if (hiddenInput) {
            hiddenInput.value = dataUrl;
        }
    } catch(err) {
        console.warn('Error al exportar recorte de avatar:', err);
    }
}

function previsualizarFotoPerfil(input) {
    cargarImagenParaRecorte(input);
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
        document.cookie = "lay8_theme=dark; path=/; max-age=31536000; SameSite=Lax";
    } else {
        document.documentElement.classList.remove('dark-mode');
        document.body.classList.remove('dark-mode');
        localStorage.setItem('lay8_theme', 'light');
        document.cookie = "lay8_theme=light; path=/; max-age=31536000; SameSite=Lax";
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
    let temaGuardado = localStorage.getItem('lay8_theme');
    if (!temaGuardado) {
        temaGuardado = document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light';
    }
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
