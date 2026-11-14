/**
 * Mapa Interactivo UMC: zoom, panning, LOD de pines, filtros y horarios en vivo.
 */
(function (global) {
    'use strict';

    const BASE_URL = global.BASE_URL || '/';
    const CANVAS_WIDTH = 1080;
    const CANVAS_HEIGHT = 1920;

    let fitScale = 1.0;
    let userZoom = 1.0;
    const minUserZoom = 1.0;
    const maxUserZoom = 4.2;
    const zoomStep = 0.25;

    let panX = 0;
    let panY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startPanX = 0;
    let startPanY = 0;
    let hasDragged = false;
    let modoPines = 'auto';

    const contenedor = document.getElementById('contenedor-mapa');
    const viewport = document.getElementById('mapa-viewport');
    const canvas = document.getElementById('mapa-canvas');
    const zoomText = document.getElementById('zoom-porcentaje');
    const hintBar = document.getElementById('mapa-hint');
    const iconoTogglePines = document.getElementById('icono-toggle-pines');
    const iconoFullscreen = document.getElementById('icono-fullscreen');

    function recalcularFitScale() {
        if (!viewport) return;
        const vW = viewport.clientWidth;
        const vH = viewport.clientHeight;
        if (vW <= 0 || vH <= 0) return;
        fitScale = Math.min(vW / CANVAS_WIDTH, vH / CANVAS_HEIGHT);
    }

    function limitarPan() {
        if (!viewport) return;
        const vW = viewport.clientWidth;
        const vH = viewport.clientHeight;
        const currentScale = fitScale * userZoom;
        const renderedW = CANVAS_WIDTH * currentScale;
        const renderedH = CANVAS_HEIGHT * currentScale;

        panX = renderedW <= vW ? (vW - renderedW) / 2 : Math.min(0, Math.max(vW - renderedW, panX));
        panY = renderedH <= vH ? (vH - renderedH) / 2 : Math.min(0, Math.max(vH - renderedH, panY));
    }

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
            if (iconoTogglePines) iconoTogglePines.className = 'bx bx-low-vision';
            if (userZoom < 1.35) {
                canvas.classList.add('modo-vista-general');
            }
        }
    }

    function aplicarTransformacion(smooth = false) {
        if (!canvas) return;
        const currentScale = fitScale * userZoom;

        if (smooth) {
            canvas.classList.add('animar-zoom');
            setTimeout(() => canvas.classList.remove('animar-zoom'), 280);
        }

        canvas.style.transform = `translate(${panX.toFixed(2)}px, ${panY.toFixed(2)}px) scale(${currentScale.toFixed(4)})`;
        const pinScale = Math.max(0.45, Math.min(2.8, 1.0 / currentScale));
        canvas.style.setProperty('--pin-scale', pinScale.toFixed(4));

        if (zoomText) zoomText.innerText = Math.round(userZoom * 100) + '%';
        if (viewport) viewport.style.cursor = userZoom > 1.05 ? (isDragging ? 'grabbing' : 'grab') : 'default';

        actualizarModoVisibilidadPines();
    }

    function ocultarHint() {
        if (hintBar && hintBar.style.display !== 'none') {
            hintBar.style.opacity = '0';
            setTimeout(() => { if (hintBar) hintBar.style.display = 'none'; }, 500);
        }
    }

    function zoomHaciaPunto(nuevoUserZoom, clientX, clientY, smooth = false) {
        if (!viewport) return;
        const rect = viewport.getBoundingClientRect();
        const mouseX = clientX - rect.left;
        const mouseY = clientY - rect.top;

        const targetUserZoom = Math.min(maxUserZoom, Math.max(minUserZoom, nuevoUserZoom));
        if (Math.abs(targetUserZoom - userZoom) < 0.005) return;

        const oldScale = fitScale * userZoom;
        const newScale = fitScale * targetUserZoom;

        panX = mouseX - ((mouseX - panX) / oldScale) * newScale;
        panY = mouseY - ((mouseY - panY) / oldScale) * newScale;
        userZoom = targetUserZoom;

        limitarPan();
        aplicarTransformacion(smooth);
        ocultarHint();
    }

    function zoomHaciaCentro(nuevoUserZoom, smooth = true) {
        if (!viewport) return;
        const rect = viewport.getBoundingClientRect();
        zoomHaciaPunto(nuevoUserZoom, rect.left + rect.width / 2, rect.top + rect.height / 2, smooth);
    }

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

    document.getElementById('btn-zoom-in')?.addEventListener('click', (e) => {
        e.stopPropagation();
        zoomHaciaCentro(userZoom + zoomStep, true);
    });

    document.getElementById('btn-zoom-out')?.addEventListener('click', (e) => {
        e.stopPropagation();
        zoomHaciaCentro(userZoom - zoomStep, true);
    });

    document.getElementById('btn-zoom-reset')?.addEventListener('click', (e) => {
        e.stopPropagation();
        verTodoElCampus(true);
    });

    document.getElementById('btn-toggle-pines')?.addEventListener('click', (e) => {
        e.stopPropagation();
        modoPines = (modoPines === 'auto') ? 'todos' : (modoPines === 'todos' ? 'ocultos' : 'auto');
        actualizarModoVisibilidadPines();
    });

    document.getElementById('btn-fullscreen-mapa')?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!contenedor) return;
        contenedor.classList.toggle('is-fullscreen');
        const esFull = contenedor.classList.contains('is-fullscreen');
        if (iconoFullscreen) iconoFullscreen.className = esFull ? 'bx bx-exit-fullscreen' : 'bx bx-fullscreen';
        setTimeout(() => {
            recalcularFitScale();
            limitarPan();
            aplicarTransformacion(true);
        }, 150);
    });

    viewport?.addEventListener('wheel', (e) => {
        e.preventDefault();
        zoomHaciaPunto(userZoom * (e.deltaY < 0 ? 1.15 : 0.87), e.clientX, e.clientY, false);
    }, { passive: false });

    viewport?.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        isDragging = true;
        hasDragged = false;
        startX = e.clientX;
        startY = e.clientY;
        startPanX = panX;
        startPanY = panY;
        viewport.classList.add('is-dragging');
    });

    global.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        if (Math.abs(dx) > 4 || Math.abs(dy) > 4) hasDragged = true;

        const currentScale = fitScale * userZoom;
        if (CANVAS_WIDTH * currentScale > viewport.clientWidth || CANVAS_HEIGHT * currentScale > viewport.clientHeight) {
            panX = startPanX + dx;
            panY = startPanY + dy;
            limitarPan();
            aplicarTransformacion(false);
            ocultarHint();
        }
    });

    global.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            if (viewport) viewport.classList.remove('is-dragging');
            aplicarTransformacion(false);
        }
    });

    let lastTouchDist = 0;
    viewport?.addEventListener('touchstart', (e) => {
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

    viewport?.addEventListener('touchmove', (e) => {
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
                const midX = (e.touches[0].clientX + e.touches[1].clientX) / 2;
                const midY = (e.touches[0].clientY + e.touches[1].clientY) / 2;
                zoomHaciaPunto(userZoom * (dist / lastTouchDist), midX, midY, false);
            }
            lastTouchDist = dist;
        }
    }, { passive: true });

    viewport?.addEventListener('touchend', () => {
        isDragging = false;
        lastTouchDist = 0;
    });

    global.addEventListener('resize', () => {
        recalcularFitScale();
        limitarPan();
        aplicarTransformacion(false);
    });

    canvas?.addEventListener('click', (e) => {
        if (hasDragged) return;
        const pin = e.target.closest('.pin-contenedor');
        if (pin && pin.dataset.aula) {
            abrirModalAula(pin.dataset.aula);
        }
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
            const match = (filtro === 'libres' && estado === 'verde')
                || (filtro === 'ocupadas' && (estado === 'roja' || estado === 'amarilla'))
                || (carrera === filtro);
            pin.classList.toggle('pin-resaltado', match);
        });
    }

    document.querySelectorAll('.btn-filtro-carrera').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-filtro-carrera').forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');
            aplicarFiltroAulas(this.dataset.filtro);
        });
    });

    function abrirModalAula(nombreAula) {
        const esc = window.Lay8UI.escapeHtml;
        const safeBx = (cls, fallback) => /^[a-z0-9-]+$/i.test(String(cls || '')) ? cls : fallback;

        document.getElementById('tituloAula').innerHTML = '<i class="bx bx-calendar"></i> Aula: ' + esc(nombreAula);
        document.getElementById('cuerpoHorario').innerHTML = '<p style="color:#64748b; padding:15px 0; text-align:center;"><i class="bx bx-loader-alt bx-spin" style="font-size:1.5rem;"></i><br>Cargando programación...</p>';
        document.getElementById('modalAula').style.display = 'flex';

        fetch(BASE_URL + 'mapa/info_aula/' + encodeURIComponent(nombreAula))
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                let html = '';

                if (data.hay_clase_activa && data.clase_activa) {
                    const c = data.clase_activa;
                    const iconoCarrera = safeBx(c.carrera_icono, 'bx-book-open');
                    html += `<div class="banner-clase-activa" style="border-left: 4px solid #ef4444;">
                                <div class="banner-estado-titulo" style="color: #ef4444;">
                                    <i class="bx bx-radio-circle-marked"></i> CLASE EN CURSO AHORA
                                    ${c.carrera_nombre ? `<span style="margin-left:auto; font-size:0.75rem; background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:10px;"><i class="bx ${iconoCarrera}"></i> ${esc(c.carrera_nombre)}</span>` : ''}
                                </div>
                                <div class="banner-materia-nombre">${esc(c.materia)} <span class="seccion-tag">[Secc. ${esc(c.seccion)}]</span></div>
                                <div class="banner-tiempo-detalle">Intervalo: <b>${esc(c.intervalo)}</b> (${esc(c.duracion)}) &bull; Quedan ~${esc(c.minutos_restantes)} min</div>
                             </div>`;
                } else if (data.proxima_clase_hoy) {
                    const p = data.proxima_clase_hoy;
                    const iconoCarrera = safeBx(p.carrera_icono, 'bx-time-five');
                    html += `<div class="banner-clase-proxima" style="border-left: 4px solid #f59e0b;">
                                <div class="banner-estado-titulo" style="color: #b45309;">
                                    <i class="bx bx-time"></i> Próxima clase hoy:
                                    ${p.carrera_nombre ? `<span style="margin-left:auto; font-size:0.75rem; background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:10px;"><i class="bx ${iconoCarrera}"></i> ${esc(p.carrera_nombre)}</span>` : ''}
                                </div>
                                <div class="banner-materia-nombre">${esc(p.materia)} <span class="seccion-tag">[Secc. ${esc(p.seccion)}]</span></div>
                                <div class="banner-tiempo-detalle">Intervalo: <b>${esc(p.intervalo)}</b> &bull; Inicia en ~${esc(p.inicia_en_minutos)} min</div>
                             </div>`;
                }

                const dias = data.horarios_por_dia || {};
                let tieneHorarios = false;
                for (const [dia, sesiones] of Object.entries(dias)) {
                    if (sesiones && sesiones.length > 0) {
                        tieneHorarios = true;
                        html += `<div class="dia-horario-bloque"><h4>${esc(dia)}</h4><ul class="lista-sesiones">`;
                        sesiones.forEach(s => {
                            const badge = s.es_activa_ahora ? ' <span class="badge-en-curso">EN CURSO</span>' : '';
                            const tagCarrera = s.carrera ? `<span style="font-size:0.72rem; font-weight:600; color:#2563eb; background:#eff6ff; padding:1px 6px; border-radius:6px; margin-right:4px;">${esc(s.carrera)}</span>` : '';
                            html += `<li><span>${tagCarrera}<b>${esc(s.intervalo)}</b> (${esc(s.duracion_texto)}) &bull; ${esc(s.materia)} <span class="sub-seccion">[${esc(s.seccion)}]</span></span>${badge}</li>`;
                        });
                        html += '</ul></div>';
                    }
                }

                if (!tieneHorarios) {
                    html += '<div class="sin-horarios-msg"><i class="bx bx-calendar-x" style="font-size:1.8rem; display:block; margin-bottom:6px;"></i>No hay clases programadas para esta aula.</div>';
                }

                document.getElementById('cuerpoHorario').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('cuerpoHorario').innerHTML = '<p style="color:#ef4444; padding:15px 0; text-align:center;">Error al consultar el horario del aula.</p>';
            });
    }

    function cerrarModalAula() {
        document.getElementById('modalAula').style.display = 'none';
    }

    function actualizarLucesEnVivo() {
        if (document.hidden) return;
        fetch(BASE_URL + 'mapa/estado_en_vivo')
            .then(response => response.json())
            .then(estadosAulas => {
                for (const [alias, info] of Object.entries(estadosAulas)) {
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

                        pin.dataset.estado = color;
                        pin.dataset.carrera = info.carrera || '';
                        pin.classList.remove('pin-estado-libre', 'pin-estado-ocupada', 'pin-estado-proxima', 'pin-estado-mantenimiento');

                        if (color === 'verde') pin.classList.add('pin-estado-libre');
                        else if (color === 'roja') pin.classList.add('pin-estado-ocupada');
                        else if (color === 'amarilla') pin.classList.add('pin-estado-proxima');
                        else if (color === 'azul') pin.classList.add('pin-estado-mantenimiento');

                        badge.className = `pin-badge pin-${color}`;
                        if (icono) icono.className = `bx ${iconoClase}`;

                        if (tagTooltip) {
                            const safeColor = ['verde', 'roja', 'amarilla', 'azul'].includes(color) ? color : 'verde';
                            const safeIcono = /^[a-z0-9-]+$/i.test(String(iconoClase || '')) ? iconoClase : 'bx-door-open';
                            tagTooltip.className = `pin-tooltip-badge tag-${safeColor}`;
                            tagTooltip.innerHTML = `<i class="bx ${safeIcono}"></i> ${window.Lay8UI.escapeHtml(info.texto_estado || 'Disponible')}`;
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

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(() => verTodoElCampus(false), 60);
        actualizarLucesEnVivo();
        setInterval(actualizarLucesEnVivo, 60000);

        const urlParams = new URLSearchParams(global.location.search);
        const aulaParam = urlParams.get('aula');
        if (aulaParam) {
            setTimeout(() => abrirModalAula(aulaParam), 400);
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) actualizarLucesEnVivo();
    });

    global.abrirModalAula = abrirModalAula;
    global.cerrarModalAula = cerrarModalAula;
})(window);
