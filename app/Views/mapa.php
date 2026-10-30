<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="mapa-dashboard-contenedor">
    <div class="mapa-header-box">
        <div>
            <h2><i class="bx bx-map-pin"></i> Mapa Interactivo del Campus UMC</h2>
            <p>Monitoreo en tiempo real de ocupación académica, carreras asignadas y disponibilidad de aulas.</p>
        </div>
    </div>

    <!-- Barra de Filtros Rápidos por Carrera y Disponibilidad -->
    <div class="mapa-filtros-carrera" id="mapa-filtros-carrera">
        <button type="button" class="btn-filtro-carrera activo" data-filtro="todos">
            <i class="bx bx-layer"></i> Todas las Aulas
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="libres">
            <i class="bx bx-check-circle" style="color: #10b981;"></i> Solo Libres
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ocupadas">
            <i class="bx bx-radio-circle-marked" style="color: #ef4444;"></i> Solo Ocupadas
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ING-INF">
            <i class="bx bx-code-alt" style="color: #2563eb;"></i> Informática
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ING-MAR">
            <i class="bx bx-anchor" style="color: #0284c7;"></i> Marítima
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ING-AMB">
            <i class="bx bx-leaf" style="color: #10b981;"></i> Ambiental
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ADM">
            <i class="bx bx-briefcase-alt-2" style="color: #f59e0b;"></i> Administración
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="TUR">
            <i class="bx bx-compass" style="color: #ec4899;"></i> Turismo
        </button>
    </div>

    <!-- Leyenda Moderna de Estados y Carreras -->
    <div class="leyenda-mapa-moderna">
        <div class="leyenda-fila">
            <span class="leyenda-titulo-seccion"><i class="bx bx-info-circle"></i> Estados del Aula:</span>
            <div class="item-leyenda-badge" title="Aula libre sin clases en este momento">
                <span class="mini-badge mini-verde"><i class="bx bx-check"></i></span>
                <span>Aula Libre (Verde)</span>
            </div>
            <div class="item-leyenda-badge" title="Hay una clase académica en curso">
                <span class="mini-badge mini-roja"><i class="bx bx-radio-circle-marked"></i></span>
                <span>En clase / Ocupada (Rojo)</span>
            </div>
            <div class="item-leyenda-badge" title="Una clase comenzará en los próximos 15 minutos">
                <span class="mini-badge mini-amarilla"><i class="bx bx-time-five"></i></span>
                <span>Próxima clase (15 min)</span>
            </div>
            <div class="item-leyenda-badge" title="Aula temporalmente inhabilitada">
                <span class="mini-badge mini-azul"><i class="bx bx-wrench"></i></span>
                <span>Fuera de servicio</span>
            </div>
        </div>
        
        <div class="leyenda-fila" style="border-top: 1px dashed #e2e8f0; padding-top: 8px; margin-top: 4px;">
            <span class="leyenda-titulo-seccion"><i class="bx bx-bookmark-alt"></i> Logos en Aulas Ocupadas:</span>
            <div class="item-leyenda-badge" title="Ingeniería en Informática">
                <span class="mini-badge mini-roja"><i class="bx bx-code-alt"></i></span>
                <span>Informática</span>
            </div>
            <div class="item-leyenda-badge" title="Ingeniería Marítima">
                <span class="mini-badge mini-roja"><i class="bx bx-anchor"></i></span>
                <span>Marítima</span>
            </div>
            <div class="item-leyenda-badge" title="Ingeniería Ambiental">
                <span class="mini-badge mini-roja"><i class="bx bx-leaf"></i></span>
                <span>Ambiental</span>
            </div>
            <div class="item-leyenda-badge" title="Licenciatura en Administración">
                <span class="mini-badge mini-roja"><i class="bx bx-briefcase-alt-2"></i></span>
                <span>Administración</span>
            </div>
            <div class="item-leyenda-badge" title="Licenciatura en Turismo">
                <span class="mini-badge mini-roja"><i class="bx bx-compass"></i></span>
                <span>Turismo</span>
            </div>
        </div>
    </div>

    <!-- Contenedor del Mapa Interactivo con Zoom y Pan -->
    <div class="contenedor-mapa" id="contenedor-mapa">
        <!-- Viewport interactivo con fondo institucional oscuro -->
        <div class="mapa-viewport" id="mapa-viewport">
            <!-- Lienzo transformable por Zoom / Pan -->
            <div class="mapa-canvas modo-vista-general" id="mapa-canvas">
                <img src="<?= base_url('img/mapa_umc.jpg') ?>" 
                     alt="Mapa Universidad Marítima del Caribe" 
                     class="imagen-mapa" 
                     draggable="false">

                <!-- Pines de Aulas con Logos de Carrera y Estado -->
                <?php if (!empty($aulas)): ?>
                    <?php foreach ($aulas as $aula): ?>
                        <?php 
                            $idSeguro = preg_replace('/[^\w\-]/', '_', $aula['alias_pdf']);
                            $colorPin = $aula['color'] ?? ($aula['esta_ocupado'] ? 'roja' : 'verde');
                            $iconoPin = $aula['icono'] ?? ($colorPin === 'verde' ? 'bx-check' : 'bx-code-alt');
                            $carreraCod = $aula['carrera'] ?? '';
                            $tituloPin = 'Aula ' . $aula['alias_pdf'] . ' (' . ($aula['texto_estado'] ?? 'Disponible') . ')';

                            $claseEstado = 'pin-estado-libre';
                            if ($colorPin === 'roja') {
                                $claseEstado = 'pin-estado-ocupada';
                            } elseif ($colorPin === 'amarilla') {
                                $claseEstado = 'pin-estado-proxima';
                            } elseif ($colorPin === 'azul') {
                                $claseEstado = 'pin-estado-mantenimiento';
                            }
                        ?>
                        <div class="pin-contenedor <?= $claseEstado ?>" 
                             id="pin_aula_<?= $idSeguro ?>"
                             style="left: <?= $aula['coord_x'] ?>%; top: <?= $aula['coord_y'] ?>%;"
                             data-aula="<?= esc($aula['alias_pdf']) ?>"
                             data-estado="<?= esc($colorPin) ?>"
                             data-carrera="<?= esc($carreraCod) ?>"
                             title="<?= esc($tituloPin) ?>">
                             
                             <div class="pin-badge pin-<?= $colorPin ?>" id="badge_aula_<?= $idSeguro ?>">
                                 <i id="icono_aula_<?= $idSeguro ?>" class="bx <?= esc($iconoPin) ?>"></i>
                             </div>

                             <!-- Tooltip flotante al pasar el cursor -->
                             <div class="pin-tooltip" id="tooltip_aula_<?= $idSeguro ?>">
                                 <span class="pin-tooltip-title">
                                     <i class="bx bx-map-pin"></i> <?= esc($aula['alias_pdf']) ?>
                                 </span>
                                 <span class="pin-tooltip-badge tag-<?= $colorPin ?>" id="tooltip_tag_<?= $idSeguro ?>">
                                     <i class="bx <?= esc($iconoPin) ?>"></i> <?= esc($aula['texto_estado'] ?? 'Disponible') ?>
                                 </span>
                                 <?php if (!empty($aula['materia'])): ?>
                                     <span class="pin-tooltip-detail" id="tooltip_mat_<?= $idSeguro ?>">
                                         <?= esc($aula['materia']) ?> <?= !empty($aula['intervalo']) ? '(' . esc($aula['intervalo']) . ')' : '' ?>
                                     </span>
                                 <?php else: ?>
                                     <span class="pin-tooltip-detail" id="tooltip_mat_<?= $idSeguro ?>" style="display:none;"></span>
                                 <?php endif; ?>
                             </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Controles Flotantes de Zoom y Visualización -->
        <div class="mapa-zoom-controls">
            <button type="button" class="btn-mapa-zoom" id="btn-zoom-in" title="Acercar mapa (+)">
                <i class="bx bx-plus"></i>
            </button>
            <button type="button" class="btn-mapa-zoom" id="btn-zoom-reset" title="Ver todo el campus (Vista completa)">
                <span class="zoom-indicator-text" id="zoom-porcentaje">100%</span>
            </button>
            <button type="button" class="btn-mapa-zoom" id="btn-zoom-out" title="Alejar mapa (-)">
                <i class="bx bx-minus"></i>
            </button>
            <button type="button" class="btn-mapa-zoom" id="btn-toggle-pines" title="Modo de pines (Auto / Ver todos / Ocultar)">
                <i class="bx bx-show" id="icono-toggle-pines"></i>
            </button>
            <button type="button" class="btn-mapa-zoom" id="btn-fullscreen-mapa" title="Pantalla completa">
                <i class="bx bx-fullscreen" id="icono-fullscreen"></i>
            </button>
        </div>

        <!-- Indicador sutil de navegación -->
        <div class="mapa-hint-bar" id="mapa-hint">
            <i class="bx bx-mouse"></i> Usa la rueda o botones para hacer zoom • Arrastra para explorar el campus
        </div>
    </div>

    <!-- Modal de Información y Horario Detallado del Aula -->
    <div id="modalAula" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalAula()">
        <div class="modal-contenido-aula">
            <div class="modal-aula-header">
                <h3 id="tituloAula"><i class="bx bx-calendar"></i> Horario del Aula</h3>
                <span onclick="cerrarModalAula()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
            </div>
            <div id="cuerpoHorario" class="modal-aula-body">
                <p>Cargando información...</p>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?= rtrim(base_url(), "/") ?>/';

    // =========================================================================
    // 1. MOTOR DE VISTA COMPLETA, ZOOM CENTRADO Y PANNING MATEMÁTICO
    // =========================================================================
    // Dimensiones canónicas del mapa UMC (relación de aspecto 9:16)
    const CANVAS_WIDTH = 1080;
    const CANVAS_HEIGHT = 1920;

    // Estado del zoom y posición
    let fitScale = 1.0;         // Escala para que todo el campus quepa al 100% en el viewport
    let userZoom = 1.0;         // Factor multiplicador del usuario (1.0 = vista completa del campus)
    let minUserZoom = 1.0;      // Límite mínimo: nunca se puede alejar más allá de la vista completa
    let maxUserZoom = 4.2;      // Acercamiento máximo detallado para enfocar aulas individuales
    const zoomStep = 0.25;

    let panX = 0;
    let panY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startPanX = 0;
    let startPanY = 0;
    let hasDragged = false;

    // Modo de visibilidad de pines: 'auto' (LOD dinámico), 'todos' (siempre visibles), 'ocultos' (solo mapa)
    let modoPines = 'auto';

    // Elementos DOM
    const contenedor = document.getElementById('contenedor-mapa');
    const viewport = document.getElementById('mapa-viewport');
    const canvas = document.getElementById('mapa-canvas');
    const zoomText = document.getElementById('zoom-porcentaje');
    const hintBar = document.getElementById('mapa-hint');
    const iconoTogglePines = document.getElementById('icono-toggle-pines');
    const iconoFullscreen = document.getElementById('icono-fullscreen');

    /**
     * Calcula la escala de ajuste perfecto ('fitScale') para que todo el mapa institucional
     * sea visible de borde a borde dentro del contenedor, sin desbordes.
     */
    function recalcularFitScale() {
        if (!viewport) return;
        const vW = viewport.clientWidth;
        const vH = viewport.clientHeight;
        if (vW <= 0 || vH <= 0) return;

        fitScale = Math.min(vW / CANVAS_WIDTH, vH / CANVAS_HEIGHT);
    }

    /**
     * Clampa las coordenadas de pan (desplazamiento) garantizando que:
     * 1. Si el mapa es más pequeño que el viewport, se mantiene perfectamente CENTRADO.
     * 2. Si el mapa es más grande, sus bordes nunca se despeguen dejando huecos blancos.
     */
    function limitarPan() {
        if (!viewport) return;
        const vW = viewport.clientWidth;
        const vH = viewport.clientHeight;
        const currentScale = fitScale * userZoom;

        const renderedW = CANVAS_WIDTH * currentScale;
        const renderedH = CANVAS_HEIGHT * currentScale;

        // Eje X
        if (renderedW <= vW) {
            panX = (vW - renderedW) / 2;
        } else {
            const minPanX = vW - renderedW;
            const maxPanX = 0;
            panX = Math.min(maxPanX, Math.max(minPanX, panX));
        }

        // Eje Y
        if (renderedH <= vH) {
            panY = (vH - renderedH) / 2;
        } else {
            const minPanY = vH - renderedH;
            const maxPanY = 0;
            panY = Math.min(maxPanY, Math.max(minPanY, panY));
        }
    }

    /**
     * Aplica la transformación CSS en el canvas, contra-escala los pines
     * para mantenerlos en tamaño físico constante y gestiona el Level of Detail (LOD).
     */
    function aplicarTransformacion(smooth = false) {
        if (!canvas) return;
        const currentScale = fitScale * userZoom;

        if (smooth) {
            canvas.classList.add('animar-zoom');
            setTimeout(() => canvas.classList.remove('animar-zoom'), 280);
        }

        canvas.style.transform = `translate(${panX.toFixed(2)}px, ${panY.toFixed(2)}px) scale(${currentScale.toFixed(4)})`;

        // Contra-escalado dinámico: los pines miden ~28px en pantalla sin importar el zoom
        const targetScreenSize = 28;
        const baseBadgeSize = 28;
        const pinScale = Math.max(0.45, Math.min(2.8, targetScreenSize / (baseBadgeSize * currentScale)));
        canvas.style.setProperty('--pin-scale', pinScale.toFixed(4));

        // Actualizar indicador de zoom en la botonera
        if (zoomText) {
            zoomText.innerText = Math.round(userZoom * 100) + '%';
        }

        // Cursor del viewport
        if (viewport) {
            viewport.style.cursor = userZoom > 1.05 ? (isDragging ? 'grabbing' : 'grab') : 'default';
        }

        // Gestión del Level of Detail (LOD): Ocultar pines libres cuando está alejado
        actualizarModoVisibilidadPines();
    }

    /**
     * Gestiona las clases de visibilidad de los pines según el modo y el nivel de zoom
     */
    function actualizarModoVisibilidadPines() {
        if (!canvas) return;

        canvas.classList.remove('modo-vista-general', 'forzar-ver-pines', 'forzar-ocultar-pines');

        if (modoPines === 'todos') {
            canvas.classList.add('forzar-ver-pines');
            if (iconoTogglePines) iconoTogglePines.className = 'bx bx-show';
        } else if (modoPines === 'ocultos') {
            canvas.classList.add('forzar-ocultar-pines');
            if (iconoTogglePines) iconoTogglePines.className = 'bx bx-hide';
        } else {
            // Modo 'auto' (LOD dinámico según petición del usuario)
            if (iconoTogglePines) iconoTogglePines.className = 'bx bx-low-vision';
            // Si el zoom es menor a 1.35x, ocultar pines libres para no saturar la vista general
            if (userZoom < 1.35) {
                canvas.classList.add('modo-vista-general');
            }
        }
    }

    /**
     * Realiza un zoom dirigido hacia las coordenadas del cursor del ratón o el centro
     */
    function zoomHaciaPunto(nuevoUserZoom, clientX, clientY, smooth = false) {
        if (!viewport) return;
        const rect = viewport.getBoundingClientRect();
        const mouseX = clientX - rect.left;
        const mouseY = clientY - rect.top;

        const targetUserZoom = Math.min(maxUserZoom, Math.max(minUserZoom, nuevoUserZoom));
        if (Math.abs(targetUserZoom - userZoom) < 0.005) return;

        const oldScale = fitScale * userZoom;
        const newScale = fitScale * targetUserZoom;

        // Mantener el punto focal bajo el cursor
        panX = mouseX - ((mouseX - panX) / oldScale) * newScale;
        panY = mouseY - ((mouseY - panY) / oldScale) * newScale;
        userZoom = targetUserZoom;

        limitarPan();
        aplicarTransformacion(smooth);
        ocultarHint();
    }

    /**
     * Realiza un zoom centrado en el viewport
     */
    function zoomHaciaCentro(nuevoUserZoom, smooth = true) {
        if (!viewport) return;
        const rect = viewport.getBoundingClientRect();
        zoomHaciaPunto(nuevoUserZoom, rect.left + rect.width / 2, rect.top + rect.height / 2, smooth);
    }

    /**
     * Restablece la vista para ver el 100% del mapa del campus perfectamente centrado
     */
    function verTodoElCampus(smooth = true) {
        recalcularFitScale();
        userZoom = 1.0;
        const currentScale = fitScale * userZoom;
        const vW = viewport ? viewport.clientWidth : 800;
        const vH = viewport ? viewport.clientHeight : 600;

        panX = (vW - (CANVAS_WIDTH * currentScale)) / 2;
        panY = (vH - (CANVAS_HEIGHT * currentScale)) / 2;

        limitarPan();
        aplicarTransformacion(smooth);
    }

    function ocultarHint() {
        if (hintBar && hintBar.style.display !== 'none') {
            hintBar.style.opacity = '0';
            setTimeout(() => { if (hintBar) hintBar.style.display = 'none'; }, 500);
        }
    }

    // =========================================================================
    // 2. EVENTOS DE ENTRADA (BOTONES, RUEDA DEL RATÓN Y ARRASTRE)
    // =========================================================================

    // Botón Acercar (+)
    document.getElementById('btn-zoom-in')?.addEventListener('click', function(e) {
        e.stopPropagation();
        zoomHaciaCentro(userZoom + zoomStep, true);
    });

    // Botón Alejar (-)
    document.getElementById('btn-zoom-out')?.addEventListener('click', function(e) {
        e.stopPropagation();
        zoomHaciaCentro(userZoom - zoomStep, true);
    });

    // Botón 100% (Ver Todo el Campus)
    document.getElementById('btn-zoom-reset')?.addEventListener('click', function(e) {
        e.stopPropagation();
        verTodoElCampus(true);
    });

    // Botón Alternar Visibilidad de Pines
    document.getElementById('btn-toggle-pines')?.addEventListener('click', function(e) {
        e.stopPropagation();
        if (modoPines === 'auto') {
            modoPines = 'todos';
        } else if (modoPines === 'todos') {
            modoPines = 'ocultos';
        } else {
            modoPines = 'auto';
        }
        actualizarModoVisibilidadPines();
    });

    // Botón Pantalla Completa
    document.getElementById('btn-fullscreen-mapa')?.addEventListener('click', function(e) {
        e.stopPropagation();
        if (!contenedor) return;
        contenedor.classList.toggle('is-fullscreen');
        const esFull = contenedor.classList.contains('is-fullscreen');
        if (iconoFullscreen) {
            iconoFullscreen.className = esFull ? 'bx bx-exit-fullscreen' : 'bx bx-fullscreen';
        }
        setTimeout(() => {
            recalcularFitScale();
            limitarPan();
            aplicarTransformacion(true);
        }, 150);
    });

    // Zoom con la Rueda del Ratón
    viewport?.addEventListener('wheel', function(e) {
        e.preventDefault();
        const factor = e.deltaY < 0 ? 1.15 : 0.87;
        zoomHaciaPunto(userZoom * factor, e.clientX, e.clientY, false);
    }, { passive: false });

    // Arrastre con el Ratón (Panning)
    viewport?.addEventListener('mousedown', function(e) {
        if (e.button !== 0) return;
        isDragging = true;
        hasDragged = false;
        startX = e.clientX;
        startY = e.clientY;
        startPanX = panX;
        startPanY = panY;
        if (viewport) viewport.classList.add('is-dragging');
    });

    window.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
            hasDragged = true;
        }

        const currentScale = fitScale * userZoom;
        const renderedW = CANVAS_WIDTH * currentScale;
        const renderedH = CANVAS_HEIGHT * currentScale;
        const vW = viewport.clientWidth;
        const vH = viewport.clientHeight;

        // Solo permitir arrastre si el lienzo sobrepasa el viewport
        if (renderedW > vW || renderedH > vH) {
            panX = startPanX + dx;
            panY = startPanY + dy;
            limitarPan();
            aplicarTransformacion(false);
            ocultarHint();
        }
    });

    window.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            if (viewport) viewport.classList.remove('is-dragging');
            aplicarTransformacion(false);
        }
    });

    // Soporte para gestos táctiles (Móviles / Tablets)
    let lastTouchDist = 0;
    viewport?.addEventListener('touchstart', function(e) {
        if (e.touches.length === 1) {
            isDragging = true;
            hasDragged = false;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startPanX = panX;
            startPanY = panY;
        } else if (e.touches.length === 2) {
            isDragging = false;
            lastTouchDist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
        }
    }, { passive: true });

    viewport?.addEventListener('touchmove', function(e) {
        if (e.touches.length === 1 && isDragging) {
            const dx = e.touches[0].clientX - startX;
            const dy = e.touches[0].clientY - startY;
            if (Math.abs(dx) > 4 || Math.abs(dy) > 4) hasDragged = true;
            panX = startPanX + dx;
            panY = startPanY + dy;
            limitarPan();
            aplicarTransformacion(false);
        } else if (e.touches.length === 2) {
            const dist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            if (lastTouchDist > 0) {
                const ratio = dist / lastTouchDist;
                const midX = (e.touches[0].clientX + e.touches[1].clientX) / 2;
                const midY = (e.touches[0].clientY + e.touches[1].clientY) / 2;
                zoomHaciaPunto(userZoom * ratio, midX, midY, false);
            }
            lastTouchDist = dist;
        }
    }, { passive: true });

    viewport?.addEventListener('touchend', function() {
        isDragging = false;
        lastTouchDist = 0;
    });

    // Recalcular en cambio de tamaño de ventana
    window.addEventListener('resize', function() {
        recalcularFitScale();
        limitarPan();
        aplicarTransformacion(false);
    });

    // Delegación de clic en pines para abrir modal (evita abrirlo si se estaba arrastrando)
    canvas?.addEventListener('click', function(e) {
        if (hasDragged) return;
        const pin = e.target.closest('.pin-contenedor');
        if (pin && pin.dataset.aula) {
            abrirModalAula(pin.dataset.aula);
        }
    });

    // =========================================================================
    // 3. BARRA DE FILTROS RÁPIDOS POR CARRERA Y ESTADO
    // =========================================================================
    document.querySelectorAll('.btn-filtro-carrera').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-filtro-carrera').forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');

            const filtro = this.dataset.filtro;
            aplicarFiltroAulas(filtro);
        });
    });

    function aplicarFiltroAulas(filtro) {
        const todosLosPines = document.querySelectorAll('.pin-contenedor');
        if (!canvas) return;

        if (filtro === 'todos') {
            canvas.classList.remove('filtro-activo');
            todosLosPines.forEach(p => p.classList.remove('pin-resaltado'));
            return;
        }

        canvas.classList.add('filtro-activo');
        todosLosPines.forEach(pin => {
            const estado = pin.dataset.estado;
            const carrera = pin.dataset.carrera;
            let match = false;

            if (filtro === 'libres' && estado === 'verde') match = true;
            else if (filtro === 'ocupadas' && (estado === 'roja' || estado === 'amarilla')) match = true;
            else if (carrera === filtro) match = true;

            if (match) {
                pin.classList.add('pin-resaltado');
            } else {
                pin.classList.remove('pin-resaltado');
            }
        });
    }

    // =========================================================================
    // 4. MODAL DE INFORMACIÓN Y HORARIO DETALLADO DEL AULA
    // =========================================================================
    function abrirModalAula(nombreAula) {
        document.getElementById('tituloAula').innerHTML = '<i class="bx bx-calendar"></i> Aula: ' + nombreAula;
        document.getElementById('cuerpoHorario').innerHTML = '<p style="color:#64748b; padding:15px 0; text-align:center;"><i class="bx bx-loader-alt bx-spin" style="font-size:1.5rem;"></i><br>Cargando programación...</p>';
        document.getElementById('modalAula').style.display = 'flex';

        fetch(BASE_URL + "mapa/info_aula/" + encodeURIComponent(nombreAula))
            .then(res => res.json())
            .then(data => {
                let html = "";

                // 1. Banner de clase activa en este momento
                if (data.hay_clase_activa && data.clase_activa) {
                    const c = data.clase_activa;
                    const iconoCarrera = c.carrera_icono || 'bx-book-open';
                    html += `<div class="banner-clase-activa" style="border-left: 4px solid #ef4444;">
                                <div class="banner-estado-titulo" style="color: #ef4444;">
                                    <i class="bx bx-radio-circle-marked"></i> CLASE EN CURSO AHORA
                                    ${c.carrera_nombre ? `<span style="margin-left:auto; font-size:0.75rem; background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:10px;"><i class="bx ${iconoCarrera}"></i> ${c.carrera_nombre}</span>` : ''}
                                </div>
                                <div class="banner-materia-nombre">${c.materia} <span class="seccion-tag">[Secc. ${c.seccion}]</span></div>
                                <div class="banner-tiempo-detalle">Intervalo: <b>${c.intervalo}</b> (${c.duracion}) &bull; Quedan ~${c.minutos_restantes} min</div>
                             </div>`;
                } else if (data.proxima_clase_hoy) {
                    const p = data.proxima_clase_hoy;
                    const iconoCarrera = p.carrera_icono || 'bx-time-five';
                    html += `<div class="banner-clase-proxima" style="border-left: 4px solid #f59e0b;">
                                <div class="banner-estado-titulo" style="color: #b45309;">
                                    <i class="bx bx-time"></i> Próxima clase hoy:
                                    ${p.carrera_nombre ? `<span style="margin-left:auto; font-size:0.75rem; background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:10px;"><i class="bx ${iconoCarrera}"></i> ${p.carrera_nombre}</span>` : ''}
                                </div>
                                <div class="banner-materia-nombre">${p.materia} <span class="seccion-tag">[Secc. ${p.seccion}]</span></div>
                                <div class="banner-tiempo-detalle">Intervalo: <b>${p.intervalo}</b> &bull; Inicia en ~${p.inicia_en_minutos} min</div>
                             </div>`;
                }

                // 2. Horarios semanales agrupados por día
                const dias = data.horarios_por_dia || {};
                let tieneHorarios = false;

                for (const [dia, sesiones] of Object.entries(dias)) {
                    if (sesiones && sesiones.length > 0) {
                        tieneHorarios = true;
                        html += `<div class="dia-horario-bloque">
                                    <h4>${dia}</h4>
                                    <ul class="lista-sesiones">`;
                        sesiones.forEach(s => {
                            const badge = s.es_activa_ahora ? ' <span class="badge-en-curso">EN CURSO</span>' : "";
                            const tagCarrera = s.carrera ? `<span style="font-size:0.72rem; font-weight:600; color:#2563eb; background:#eff6ff; padding:1px 6px; border-radius:6px; margin-right:4px;">${s.carrera}</span>` : '';
                            html += `<li>
                                        <span>${tagCarrera}<b>${s.intervalo}</b> (${s.duracion_texto}) &bull; ${s.materia} <span class="sub-seccion">[${s.seccion}]</span></span>
                                        ${badge}
                                     </li>`;
                        });
                        html +=    `</ul>
                                 </div>`;
                    }
                }

                if (!tieneHorarios) {
                    html += '<div class="sin-horarios-msg"><i class="bx bx-calendar-x" style="font-size:1.8rem; display:block; margin-bottom:6px;"></i>No hay clases programadas para esta aula.</div>';
                }

                document.getElementById("cuerpoHorario").innerHTML = html;
            })
            .catch(err => {
                document.getElementById('cuerpoHorario').innerHTML = '<p style="color:#ef4444; padding:15px 0; text-align:center;">Error al consultar el horario del aula.</p>';
            });
    }

    function cerrarModalAula() { 
        document.getElementById('modalAula').style.display = 'none'; 
    }

    // =========================================================================
    // 5. ACTUALIZACIÓN EN VIVO DE ESTADOS Y LOGOS DE CARRERAS (CADA MINUTO)
    // =========================================================================
    function actualizarLucesEnVivo() {
        fetch(BASE_URL + 'mapa/estado_en_vivo')
            .then(response => response.json())
            .then(estados_aulas => {
                for (const [alias, info] of Object.entries(estados_aulas)) {
                    const idSeguro = alias.replace(/[^\w\-]/g, '_');
                    const pin = document.getElementById(`pin_aula_${idSeguro}`);
                    const badge = document.getElementById(`badge_aula_${idSeguro}`);
                    const icono = document.getElementById(`icono_aula_${idSeguro}`);
                    const tagTooltip = document.getElementById(`tooltip_tag_${idSeguro}`);
                    const matTooltip = document.getElementById(`tooltip_mat_${idSeguro}`);

                    if (!pin || !badge) continue;

                    if (typeof info === 'object' && info !== null) {
                        const color = info.color || 'verde';
                        const iconoClase = info.icono || (color === 'verde' ? 'bx-check' : 'bx-code-alt');

                        // Actualizar datos y clases en el pin
                        pin.dataset.estado = color;
                        pin.dataset.carrera = info.carrera || '';

                        pin.classList.remove('pin-estado-libre', 'pin-estado-ocupada', 'pin-estado-proxima', 'pin-estado-mantenimiento');
                        if (color === 'verde') pin.classList.add('pin-estado-libre');
                        else if (color === 'roja') pin.classList.add('pin-estado-ocupada');
                        else if (color === 'amarilla') pin.classList.add('pin-estado-proxima');
                        else if (color === 'azul') pin.classList.add('pin-estado-mantenimiento');

                        // Actualizar badge
                        badge.className = `pin-badge pin-${color}`;
                        if (icono) icono.className = `bx ${iconoClase}`;

                        // Actualizar tooltip
                        if (tagTooltip) {
                            tagTooltip.className = `pin-tooltip-badge tag-${color}`;
                            tagTooltip.innerHTML = `<i class="bx ${iconoClase}"></i> ${info.texto_estado || 'Disponible'}`;
                        }
                        if (matTooltip) {
                            if (info.materia) {
                                matTooltip.style.display = 'block';
                                matTooltip.innerText = `${info.materia} ${info.intervalo ? '(' + info.intervalo + ')' : ''}`;
                            } else {
                                matTooltip.style.display = 'none';
                            }
                        }
                    }
                }
            })
            .catch(error => console.error('Error sincronizando el estado de las aulas:', error));
    }

    // =========================================================================
    // 6. INICIALIZACIÓN AL CARGAR LA PÁGINA
    // =========================================================================
    document.addEventListener("DOMContentLoaded", function() {
        // Vista general inicial completa del campus
        setTimeout(() => {
            verTodoElCampus(false);
        }, 60);

        // Actualización de estados en vivo
        actualizarLucesEnVivo();
        setInterval(actualizarLucesEnVivo, 60000);

        // Si se envió un parámetro ?aula= en la URL para enfocarla
        const urlParams = new URLSearchParams(window.location.search);
        const aulaParam = urlParams.get('aula');
        if (aulaParam) {
            setTimeout(() => abrirModalAula(aulaParam), 400);
        }
    });
</script>
<?= $this->endSection() ?>
