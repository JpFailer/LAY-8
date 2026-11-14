// aulas y horario semanal: grilla, bloques y entregas

const BASE_URL = window.BASE_URL || '/';

function mostrarToastAulas(mensaje, tipo = 'exito') {
    if (window.Lay8UI) {
        window.Lay8UI.toast(mensaje, tipo);
    } else {
        console.log(mensaje);
    }
}

function cambiarTabAulas(tabId) {
    document.querySelectorAll('.tab-contenido-aulas').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.filtros-categorias .btn-filtro').forEach(b => b.classList.remove('activo'));

    const seccionTarget = document.getElementById('tab-seccion-' + tabId);
    const btnTarget     = document.getElementById('tabbtn-' + tabId);

    if (seccionTarget) seccionTarget.style.display = 'block';
    if (btnTarget) btnTarget.classList.add('activo');

    try {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url);
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    inicializarBuscadorMaterias();

    const urlParams = new URLSearchParams(window.location.search);
    const tabSolicitada = urlParams.get('tab');
    if (tabSolicitada && document.getElementById('tab-seccion-' + tabSolicitada)) {
        cambiarTabAulas(tabSolicitada);
    }
});

function recargarVistaHorario() {
    try {
        const target = new URL(BASE_URL + 'aulas?tab=horario', window.location.origin);
        target.searchParams.set('tab', 'horario');
        window.location.href = target.toString();
    } catch (e) {
        window.location.href = BASE_URL + 'aulas?tab=horario';
    }
}

/**
 * Imprime el Horario Académico Personal suprimiendo barras de depuración y metadatos sensibles.
 */
function imprimirHorarioAcademico() {
    const debugElements = document.querySelectorAll(
        '#toolbarContainer, .ci-debugbar, [id^="debug-bar"], [id^="ci-"], #debugbar, .debug-bar-wrapper, .debug-bar-btn'
    );
    debugElements.forEach(el => {
        el.style.setProperty('display', 'none', 'important');
    });

    window.print();

    setTimeout(() => {
        debugElements.forEach(el => {
            el.style.removeProperty('display');
        });
    }, 1500);
}

async function eliminarBloqueHorario(id, btnElem) {
    const ok = await window.Lay8UI.confirm('¿Deseas eliminar este bloque de clase de tu horario?', {
        title: 'Ocultar bloque de horario',
        confirmText: 'Eliminar bloque',
        danger: true
    });
    if (!ok) return;

    const ids = String(id).split(',').map(s => parseInt(s.trim(), 10)).filter(n => !isNaN(n));
    const card = btnElem ? btnElem.closest('.bloque-clase-card') : null;
    const parentTd = card ? card.closest('td') : null;

    if (card) {
        card.style.opacity = '0.35';
    }

    try {
        for (const subId of ids) {
            await window.Lay8UI.apiFetch(BASE_URL + 'aulas/eliminar-bloque/' + subId, { method: 'POST' });
        }

        if (card) {
            card.style.transition = 'all 0.25s ease';
            card.style.transform = 'scale(0.85)';
            card.style.opacity = '0';
        }

        setTimeout(() => {
            if (parentTd) {
                // Si la celda tenía rowspan, revertir a celda vacía o recargar vista limpia
                recargarVistaHorario();
            } else if (card) {
                card.remove();
            }

            const badge = document.querySelector('#tabbtn-horario .badge-conteo');
            if (badge) {
                const act = parseInt(badge.textContent || '0', 10);
                badge.textContent = Math.max(0, act - ids.length);
            }
            const avisoVacio = document.getElementById('aviso-horario-vacio');
            if (avisoVacio && document.querySelectorAll('.bloque-clase-card').length === 0) {
                avisoVacio.style.display = 'flex';
            }
        }, 250);
        mostrarToastAulas('Clase removida de tu horario correctamente.', 'exito');
    } catch (e) {
        if (card) card.style.opacity = '1';
        mostrarToastAulas(e.message || 'No se pudo eliminar el bloque.', 'error');
    }
}

async function confirmarLimpiarHorario(btnElem) {
    const ok = await window.Lay8UI.confirm(
        '¿Estás seguro de que deseas limpiar tu horario?\n\nSe vaciarán todos los bloques asignados en tu horario semanal (puedes restaurarlos luego con "Restaurar Horario Oficial").',
        { title: 'Limpiar horario', confirmText: 'Limpiar horario', danger: true }
    );
    if (!ok) return;

    const btn = btnElem || document.getElementById('btn-limpiar-horario');
    if (btn) btn.disabled = true;

    try {
        const data = await window.Lay8UI.apiFetch(BASE_URL + 'aulas/limpiar-horario', { method: 'POST' });
        document.querySelectorAll('.bloque-clase-card').forEach(card => card.remove());
        const badge = document.querySelector('#tabbtn-horario .badge-conteo');
        if (badge) badge.textContent = '0';
        const avisoVacio = document.getElementById('aviso-horario-vacio');
        if (avisoVacio) avisoVacio.style.display = 'flex';

        mostrarToastAulas(data.mensaje || 'Horario limpiado exitosamente.', 'exito');
        setTimeout(recargarVistaHorario, 600);
    } catch (e) {
        if (btn) btn.disabled = false;
        mostrarToastAulas(e.message || 'No se pudo limpiar el horario.', 'error');
    }
}

async function confirmarRestaurarHorario(btnElem) {
    const ok = await window.Lay8UI.confirm('¿Deseas restaurar todos los bloques oficiales asignados por la universidad?', {
        title: 'Restaurar horario oficial',
        confirmText: 'Restaurar'
    });
    if (!ok) return;

    const btn = btnElem || document.getElementById('btn-restaurar-horario');
    if (btn) btn.disabled = true;

    try {
        const res = await window.Lay8UI.apiFetch(BASE_URL + 'aulas/restaurar-horario', { method: 'POST' });
        mostrarToastAulas(res.mensaje || 'Horario oficial restaurado.', 'exito');
        setTimeout(recargarVistaHorario, 600);
    } catch (e) {
        if (btn) btn.disabled = false;
        mostrarToastAulas(e.message || 'No se pudo restaurar.', 'error');
    }
}

function filtrarActividades(filtro, btnElem) {
    document.querySelectorAll('.filtros-actividades-chips .chip-actividad').forEach(c => c.classList.remove('activo'));
    const targetBtn = btnElem || (window.event ? window.event.target : null);
    if (targetBtn) targetBtn.classList.add('activo');

    document.querySelectorAll('.item-actividad-card').forEach(item => {
        const estado = item.getAttribute('data-estado');
        if (filtro === 'todas' || (filtro === 'pendientes' && estado === 'pendiente') || (filtro === 'completadas' && estado === 'completada')) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

async function toggleActividadCheckbox(actividadId, checkboxElem) {
    try {
        await window.Lay8UI.apiFetch(BASE_URL + 'aulas/toggle-actividad/' + actividadId, { method: 'POST' });
        const card = checkboxElem.closest('.item-actividad-card');
        if (checkboxElem.checked) {
            card.classList.add('actividad-completada');
            card.setAttribute('data-estado', 'completada');
        } else {
            card.classList.remove('actividad-completada');
            card.setAttribute('data-estado', 'pendiente');
        }
    } catch (err) {
        checkboxElem.checked = !checkboxElem.checked;
        mostrarToastAulas(err.message || 'No se pudo actualizar el estado de la tarea.', 'error');
    }
}

function abrirModalNuevaActividad(seccionId = null) {
    const modal = document.getElementById('modalNuevaActividad');
    if (!modal) return;
    if (seccionId) {
        const selectSec = document.getElementById('act_seccion_id');
        if (selectSec) selectSec.value = seccionId;
    }
    modal.style.display = 'flex';
}

function cerrarModalNuevaActividad() {
    const modal = document.getElementById('modalNuevaActividad');
    if (modal) modal.style.display = 'none';
}

let actividadEntregaSeleccionada = null;
function abrirModalEntregarTarea(actividadId, titulo, materia) {
    actividadEntregaSeleccionada = actividadId;
    document.getElementById('entregar_modal_titulo').textContent = titulo;
    document.getElementById('entregar_modal_materia').textContent = materia;
    const form = document.getElementById('formEntregarTareaAlumno');
    if (form) {
        form.reset();
        form.action = BASE_URL + 'aulas/entregar-tarea/' + actividadId;
    }
    document.getElementById('modalEntregarTarea').style.display = 'flex';
}

function cerrarModalEntregarTarea() {
    document.getElementById('modalEntregarTarea').style.display = 'none';
}

const formEntrega = document.getElementById('formEntregarTareaAlumno');
if (formEntrega) {
    formEntrega.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitEntrega');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Subiendo entrega...';

        try {
            const res = await window.Lay8UI.apiFetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            });
            mostrarToastAulas(res.mensaje || 'Entrega guardada exitosamente.', 'exito');
            cerrarModalEntregarTarea();
            setTimeout(() => {
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', 'actividades');
                    window.location.href = url.toString();
                } catch (uErr) {
                    window.location.href = BASE_URL + 'aulas?tab=actividades';
                }
            }, 650);
        } catch (err) {
            mostrarToastAulas(err.message || 'Error al subir la entrega.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    });
}

function normalizarTextoAulas(txt) {
    return (txt || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

let tarjetasMateriasCache = [];
let totalMateriasOriginal = 0;

function inicializarBuscadorMaterias() {
    const cards = document.querySelectorAll('.tarjeta-aula-classroom');
    totalMateriasOriginal = cards.length;
    tarjetasMateriasCache = Array.from(cards).map(card => {
        const titulo = card.querySelector('.titulo-materia-aula')?.textContent || '';
        const codigo = card.querySelector('.badge-codigo-materia')?.textContent || '';
        const seccion = card.querySelector('.badge-seccion-materia')?.textContent || '';
        const semestre = card.querySelector('.badge-semestre')?.textContent || '';
        const profesor = card.querySelector('.profesor-materia')?.textContent || '';
        const aulas = Array.from(card.querySelectorAll('.btn-tag-mapa')).map(a => a.textContent).join(' ');
        return {
            element: card,
            searchCorpus: normalizarTextoAulas(`${titulo} ${codigo} ${seccion} secc ${semestre} ${profesor} ${aulas}`)
        };
    });
}

function filtrarGridMaterias(query) {
    const btnClear = document.getElementById('btn-limpiar-search-materias');
    const badgeNum = document.getElementById('conteo-materias-num');
    const sinResultados = document.getElementById('sin-resultados-busqueda-materias');
    const grid = document.getElementById('grid-materias-aulas');
    const txtTermino = document.getElementById('texto-sin-resultados');

    const rawQuery = (query || '').trim();
    if (btnClear) btnClear.style.display = rawQuery.length > 0 ? 'inline-flex' : 'none';

    const normQuery = normalizarTextoAulas(rawQuery);
    if (!normQuery) {
        tarjetasMateriasCache.forEach(item => item.element.style.display = '');
        if (badgeNum) badgeNum.textContent = totalMateriasOriginal;
        if (sinResultados) sinResultados.style.display = 'none';
        if (grid) grid.style.display = 'grid';
        return;
    }

    const tokens = normQuery.split(/\s+/).filter(Boolean);
    let visibleCount = 0;
    tarjetasMateriasCache.forEach(item => {
        const matchesAll = tokens.every(token => item.searchCorpus.includes(token));
        item.element.style.display = matchesAll ? '' : 'none';
        if (matchesAll) visibleCount++;
    });

    if (badgeNum) badgeNum.textContent = visibleCount;

    if (visibleCount === 0) {
        if (grid) grid.style.display = 'none';
        if (sinResultados) {
            sinResultados.style.display = 'block';
            if (txtTermino) {
                txtTermino.innerHTML = `No hay materias que coincidan con "<b>${window.Lay8UI.escapeHtml(rawQuery)}</b>". Prueba buscando sin código o con otra palabra clave.`;
            }
        }
    } else {
        if (grid) grid.style.display = 'grid';
        if (sinResultados) sinResultados.style.display = 'none';
    }
}

function limpiarBuscadorMaterias() {
    const input = document.getElementById('buscador-materias-aulas');
    if (input) {
        input.value = '';
        input.focus();
    }
    filtrarGridMaterias('');
}

let debounceExtemporaneaTimer = null;

function abrirModalExtemporanea() {
    const modal = document.getElementById('modalInscripcionExtemporanea');
    if (!modal) return;
    modal.style.display = 'flex';
    const input = document.getElementById('buscador-modal-extemporanea');
    if (input) {
        input.value = '';
        input.focus();
    }
    buscarMateriasExtemporaneas('');
}

function cerrarModalExtemporanea() {
    const modal = document.getElementById('modalInscripcionExtemporanea');
    if (modal) modal.style.display = 'none';
}

function buscarMateriasExtemporaneas(termino) {
    clearTimeout(debounceExtemporaneaTimer);
    debounceExtemporaneaTimer = setTimeout(() => {
        ejecutarBusquedaExtemporanea(termino);
    }, 220);
}

async function ejecutarBusquedaExtemporanea(termino) {
    const contenedor = document.getElementById('lista-resultados-extemporanea');
    const carreraSelect = document.getElementById('filtro-carrera-extemporanea');
    const carrera = carreraSelect ? carreraSelect.value : '';
    if (!contenedor) return;

    contenedor.innerHTML = `
        <div style="text-align: center; padding: 20px; color: #94a3b8; font-size: 0.86rem;">
            <i class="bx bx-loader-alt bx-spin" style="font-size: 1.5rem; display: block; margin-bottom: 6px;"></i>
            Consultando oferta académica...
        </div>
    `;

    try {
        const url = BASE_URL + 'api/aulas/buscar-extemporaneas?q=' + encodeURIComponent(termino) + '&carrera=' + encodeURIComponent(carrera);
        const data = await window.Lay8UI.apiFetch(url);
        if (!data.secciones || data.secciones.length === 0) {
            contenedor.innerHTML = `
                <div style="text-align: center; padding: 24px 10px; color: #94a3b8; font-size: 0.86rem;">
                    <i class="bx bx-info-circle" style="font-size: 1.8rem; display: block; margin-bottom: 6px;"></i>
                    No se encontraron asignaturas disponibles con los criterios seleccionados.
                </div>
            `;
            return;
        }

        const esc = window.Lay8UI.escapeHtml;
        let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
        data.secciones.forEach(sec => {
            const secId = parseInt(sec.id, 10);
            const prof = (sec.prof_nombre || sec.prof_apellido) ? `${sec.prof_nombre || ''} ${sec.prof_apellido || ''}`.trim() : 'Docente UMC por asignar';
            const yaInscrita = !!sec.ya_inscrita;

            html += `
                <div class="aulas-ext-item-card">
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px; flex-wrap: wrap;">
                            <span style="font-size: 0.72rem; padding: 2px 6px; background: #2563eb; color: #fff; border-radius: 4px; font-weight: 600;">${esc(sec.codigo_materia || '')}</span>
                            <span class="aulas-ext-item-secc">Secc. ${esc(sec.codigo_seccion || '')}</span>
                            <span class="aulas-ext-item-carrera">${esc(sec.carrera || '')}</span>
                            ${sec.semestre_trayecto ? `<span class="aulas-ext-item-meta">Semestre ${esc(sec.semestre_trayecto)}</span>` : ''}
                        </div>
                        <div class="aulas-ext-item-title">
                            ${esc(sec.materia || '')}
                        </div>
                        <div class="aulas-ext-item-meta" style="margin-top: 2px;">
                            <i class="bx bx-user"></i> ${esc(prof)}
                        </div>
                    </div>
                    <div>
                        ${yaInscrita ? `
                            <span style="font-size: 0.78rem; color: #10b981; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background: rgba(16, 185, 129, 0.1); border-radius: 6px;">
                                <i class="bx bx-check"></i> Inscrita
                            </span>
                        ` : (sec.solicitud_pendiente ? `
                            <span style="font-size: 0.78rem; color: #f59e0b; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background: rgba(245, 158, 11, 0.12); border-radius: 6px;">
                                <i class="bx bx-time-five"></i> Solicitud Pendiente
                            </span>
                        ` : `
                            <button type="button" class="btn-subir-cta btn-inscribir-ext" data-seccion-id="${secId}" data-materia="${esc(sec.materia || '')}" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="bx bx-send"></i> Solicitar Inscripción
                            </button>
                        `)}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        contenedor.innerHTML = html;
        contenedor.querySelectorAll('.btn-inscribir-ext').forEach(btn => {
            btn.addEventListener('click', () => {
                inscribirMateriaExtemporanea(parseInt(btn.dataset.seccionId, 10), btn.dataset.materia || '');
            });
        });
    } catch (e) {
        contenedor.innerHTML = `
            <div style="text-align: center; padding: 20px; color: #ef4444; font-size: 0.86rem;">
                <i class="bx bx-error" style="font-size: 1.5rem; display: block; margin-bottom: 6px;"></i>
                Error de comunicación al consultar el catálogo de asignaturas.
            </div>
        `;
    }
}

async function inscribirMateriaExtemporanea(seccionId, nombreMateria) {
    const ok = await window.Lay8UI.confirm(
        '¿Deseas enviar una solicitud formal de inscripción extemporánea al docente encargado de ' + nombreMateria + '?',
        { title: 'Solicitar Materia Extemporánea', confirmText: 'Enviar Solicitud' }
    );
    if (!ok) return;

    const formData = new FormData();
    formData.append('seccion_id', seccionId);

    try {
        const data = await window.Lay8UI.apiFetch(BASE_URL + 'api/aulas/inscribir-extemporanea', {
            method: 'POST',
            body: formData
        });
        mostrarToastAulas(data.mensaje || 'Solicitud enviada correctamente al docente.', 'exito');
        const buscador = document.getElementById('buscador-modal-extemporanea');
        ejecutarBusquedaExtemporanea(buscador ? buscador.value : '');
    } catch (err) {
        mostrarToastAulas(err.message || 'No se pudo enviar la solicitud de inscripción.', 'error');
    }
}
