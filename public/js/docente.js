// panel docente: secciones, estados en vivo y tareas (no moverle mucho)

const BASE_URL = window.BASE_URL || '/';

    function cambiarTabDocente(tabId) {
        const seccionTarget = document.getElementById('tab-doc-' + tabId);
        if (!seccionTarget) return;

        document.querySelectorAll('.tab-contenido-docente').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.filtros-categorias .btn-filtro').forEach(b => b.classList.remove('activo'));

        const btnTarget = document.getElementById('tabbtn-doc-' + tabId);
        seccionTarget.style.display = 'block';
        if (btnTarget) btnTarget.classList.add('activo');
    }

    // cambiar estado de la clase (presencial, virtual, etc)
    function abrirModalEstadoSeccion(seccionId, materia, seccion) {
        document.getElementById('estado_seccion_id').value = seccionId;
        document.getElementById('estado_modal_materia').textContent = materia + ' (Sección ' + seccion + ')';
        document.getElementById('modalEstadoSeccion').style.display = 'flex';
    }

    function cerrarModalEstadoSeccion() {
        document.getElementById('modalEstadoSeccion').style.display = 'none';
    }

    function toggleEnlaceVirtual(esOnline) {
        document.getElementById('campo_enlace_virtual').style.display = esOnline ? 'block' : 'none';
    }

    function setEstadoPredefinido(texto) {
        document.getElementById('estado_rapido').value = texto;
    }

    async function enviarEstadoClaseDocente(e) {
        e.preventDefault();
        const form = document.getElementById('formEstadoSeccion');
        const btn = document.getElementById('btnGuardarEstado');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Transmitiendo estado...';

        try {
            const res = await window.Lay8UI.apiFetch(BASE_URL + 'docente/actualizar-estado', {
                method: 'POST',
                body: new FormData(form)
            });
            window.Lay8UI.toast(res.mensaje || 'Estado transmitido correctamente a los alumnos.', 'exito');
            setTimeout(() => window.location.reload(), 700);
        } catch (err) {
            window.Lay8UI.toast(err.message || 'Error al guardar estado.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    // publicar tarea o evaluacion nueva
    function abrirModalCrearTareaDocente() {
        document.getElementById('modalTareaDocente').style.display = 'flex';
    }

    function cerrarModalTareaDocente() {
        document.getElementById('modalTareaDocente').style.display = 'none';
    }

    function preseleccionarSeccionTarea(seccionId) {
        document.getElementById('tarea_seccion_id').value = seccionId;
        abrirModalCrearTareaDocente();
    }

    async function enviarTareaDocente(e) {
        e.preventDefault();
        const form = document.getElementById('formTareaDocente');
        const btn = document.getElementById('btnGuardarTareaDocente');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Publicando evaluación...';

        try {
            const res = await window.Lay8UI.apiFetch(BASE_URL + 'docente/crear-tarea', {
                method: 'POST',
                body: new FormData(form)
            });
            window.Lay8UI.toast(res.mensaje || 'Evaluación publicada con éxito.', 'exito');
            setTimeout(() => window.location.reload(), 700);
        } catch (err) {
            window.Lay8UI.toast(err.message || 'Error al crear la evaluación.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    // ver quien entrego y clavarle la nota
    async function abrirModalRevisarEntregas(actividadId, titulo) {
        document.getElementById('titulo_evaluacion_modal').textContent = titulo;
        document.getElementById('modalRevisarEntregas').style.display = 'flex';
        document.getElementById('cargando_entregas').style.display = 'block';
        document.getElementById('contenedor_lista_entregas').style.display = 'none';
        document.getElementById('sin_entregas_msg').style.display = 'none';

        try {
            const esc = window.Lay8UI.escapeHtml;
            const resp = await fetch(BASE_URL + 'docente/entregas/' + parseInt(actividadId, 10), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) {
                throw new Error('HTTP ' + resp.status);
            }
            const res = await resp.json();
            document.getElementById('cargando_entregas').style.display = 'none';

            if (!res.success || !res.entregas || res.entregas.length === 0) {
                document.getElementById('sin_entregas_msg').style.display = 'block';
                return;
            }

            document.getElementById('contenedor_lista_entregas').style.display = 'block';
            const contenedor = document.getElementById('lista_entregas_items');
            contenedor.innerHTML = '';

            res.entregas.forEach(ent => {
                const entId = parseInt(ent.id, 10);
                const nombreEst = esc((ent.primer_nombre + ' ' + (ent.segundo_nombre ? ent.segundo_nombre + ' ' : '') + ent.apellidos).trim());
                const calificada = ent.calificacion !== null && ent.calificacion !== undefined;
                const notaSegura = calificada && !Number.isNaN(Number(ent.calificacion)) ? Number(ent.calificacion) : '';

                const card = document.createElement('div');
                card.className = 'card-solicitud-docente-item docente-entrega-card';
                card.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.96rem; display: flex; align-items: center; gap: 6px;">
                                <i class="bx bx-user" style="color: #2563eb;"></i> ${nombreEst}
                                <span style="font-size: 0.8rem; color: #64748b; font-weight: normal;">(C.I: ${esc(ent.cedula || 'N/A')})</span>
                            </div>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                <i class="bx bx-calendar"></i> Entregado: ${esc(ent.created_at || 'Reciente')}
                            </div>
                            ${ent.comentario_alumno ? `<div class="docente-entrega-comentario">"${esc(ent.comentario_alumno)}"</div>` : ''}
                        </div>
                        <div>
                            ${ent.archivo_ruta ? `
                                <a href="${BASE_URL}docente/descargar-entrega/${entId}" class="btn-subir-cta" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="bx bx-download"></i> Descargar Trabajo (${esc(ent.archivo_nombre || 'archivo')})
                                </a>
                            ` : `<span style="font-size: 0.8rem; color: #94a3b8;">Sin archivo adjunto</span>`}
                        </div>
                    </div>

                    <div class="docente-entrega-calif-row">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <label style="font-size: 0.82rem; font-weight: 700;">Calificación:</label>
                            <input type="number" step="0.5" min="0" max="100" id="nota_entrega_${entId}" value="${esc(notaSegura)}" placeholder="0-20" class="input-lay8" style="width: 80px; padding: 6px 8px; font-weight: 700;">
                        </div>
                        <div style="flex: 1; min-width: 180px;">
                            <input type="text" id="feedback_entrega_${entId}" value="${esc(ent.feedback_profesor || '')}" placeholder="Retroalimentación para el alumno..." class="input-lay8" style="width: 100%; padding: 6px 10px;">
                        </div>
                        <button type="button" class="btn-subir-cta" id="btn_calificar_entrega_${entId}" onclick="guardarCalificacionEntrega(${entId}, this)" style="padding: 6px 14px; font-size: 0.82rem;">
                            <i class="bx bx-save"></i> ${calificada ? 'Actualizar Nota' : 'Calificar'}
                        </button>
                    </div>
                `;
                contenedor.appendChild(card);
            });
        } catch(e) {
            document.getElementById('cargando_entregas').style.display = 'none';
            document.getElementById('sin_entregas_msg').style.display = 'block';
            document.getElementById('sin_entregas_msg').innerHTML = '<p style="color: #ef4444;">Error al cargar las entregas.</p>';
        }
    }

    function cerrarModalRevisarEntregas() {
        document.getElementById('modalRevisarEntregas').style.display = 'none';
    }

    async function guardarCalificacionEntrega(entregaId, btnElem) {
        const notaInput = document.getElementById('nota_entrega_' + entregaId);
        const fbInput = document.getElementById('feedback_entrega_' + entregaId);
        const btn = btnElem || document.getElementById('btn_calificar_entrega_' + entregaId);

        if (!notaInput || notaInput.value === '') {
            window.Lay8UI.toast('Por favor ingresa una nota numérica.', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('entrega_id', entregaId);
        formData.append('calificacion', notaInput.value);
        formData.append('feedback', fbInput ? fbInput.value : '');

        const origHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Guardando...';
        }

        try {
            const res = await window.Lay8UI.apiFetch(BASE_URL + 'docente/calificar', {
                method: 'POST',
                body: formData
            });
            if (btn) {
                btn.innerHTML = '<i class="bx bx-save"></i> Actualizar Nota';
            }
            window.Lay8UI.toast(res.mensaje || 'Calificación registrada con éxito.', 'exito');
        } catch (e) {
            if (btn) btn.innerHTML = origHtml;
            window.Lay8UI.toast(e.message || 'Error al guardar la calificación.', 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    // =========================================================================
    // FILTRO DE SECCIONES (ANTIDAYANAS - CASE & ACCENT INSENSITIVE)
    // =========================================================================

    // normalizador antidayanas: descomposición canónica NFD + regex de marcas diacríticas
    function normalizarTextoDocente(txt) {
        return (txt || '')
            .toString()
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .trim();
    }

    let tarjetasDocenteCache = [];
    let tarjetasFiltradasDocente = [];
    let totalDocenteOriginal = 0;
    let carreraActivaDocente = '';
    let terminoActivoDocente = '';
    let paginaActualDocente = 1;
    let totalPaginasServidorDocente = 1;
    let tamanoPaginaDocente = 12;
    let debounceDocenteTimer = null;

    function esModoServidorDocente() {
        const cont = document.getElementById('tab-doc-secciones');
        return !!(cont && cont.dataset.modoServidor === '1');
    }

    /**
     * Reubica los modales directamente en document.body para garantizar
     * su posicionamiento fijo respecto al viewport sin importar el scroll.
     */
    function asegurarModalesEnBodyDocente() {
        ['modalEstadoSeccion', 'modalTareaDocente', 'modalRevisarEntregas'].forEach(id => {
            const modal = document.getElementById(id);
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
    }

    function inicializarBuscadorDocente() {
        const cont = document.getElementById('tab-doc-secciones');
        if (cont && cont.dataset.totalPaginas) {
            totalPaginasServidorDocente = Math.max(1, parseInt(cont.dataset.totalPaginas, 10) || 1);
        }
        if (esModoServidorDocente()) {
            return;
        }
        const tarjetas = document.querySelectorAll('#tab-doc-secciones .tarjeta-aula-classroom');
        totalDocenteOriginal = tarjetas.length;
        tarjetasDocenteCache = Array.from(tarjetas).map(t => ({
            element: t,
            carrera: (t.getAttribute('data-carrera') || '').trim().toUpperCase(),
            searchCorpus: normalizarTextoDocente(t.textContent || '')
        }));
        aplicarFiltrosYRenderizarDocente(false);
    }

    async function cargarSeccionesServidorDocente(hacerScroll) {
        const grid = document.querySelector('#tab-doc-secciones .grid-aulas-classroom');
        const sinResultados = document.getElementById('sin-resultados-docente');
        const txtTermino = document.getElementById('texto-sin-resultados-docente');
        const barTop = document.getElementById('docente-paginacion-top');
        const barBottom = document.getElementById('docente-paginacion-bottom');
        const badge = document.getElementById('badge-conteo-secciones');

        if (grid) grid.style.opacity = '0.55';

        try {
            const params = new URLSearchParams({
                ajax_secciones: '1',
                carrera: carreraActivaDocente,
                q: terminoActivoDocente,
                page: String(paginaActualDocente),
                por_pagina: String(tamanoPaginaDocente)
            });

            const res = await window.Lay8UI.apiFetch(BASE_URL + 'docente?' + params.toString());
            paginaActualDocente = res.paginaActual || 1;
            totalPaginasServidorDocente = Math.max(1, res.totalPaginas || 1);
            const totalFiltradas = res.totalFiltradas || 0;

            if (badge) badge.textContent = totalFiltradas;

            if (totalFiltradas === 0) {
                if (grid) {
                    grid.innerHTML = '';
                    grid.style.display = 'none';
                }
                if (barTop) barTop.style.display = 'none';
                if (barBottom) barBottom.style.display = 'none';
                if (sinResultados) {
                    sinResultados.style.display = 'block';
                    if (txtTermino) {
                        if (terminoActivoDocente) {
                            const escaped = window.Lay8UI.escapeHtml(terminoActivoDocente);
                            txtTermino.innerHTML = `No hay secciones que coincidan con "<b>${escaped}</b>".`;
                        } else {
                            txtTermino.textContent = 'No hay secciones registradas para este filtro.';
                        }
                    }
                }
                return;
            }

            if (sinResultados) sinResultados.style.display = 'none';
            if (grid) {
                grid.innerHTML = res.cardsHtml || '';
                grid.style.display = 'grid';
                grid.style.opacity = '1';
            }

            const inicio = ((paginaActualDocente - 1) * tamanoPaginaDocente) + 1;
            const fin = Math.min(paginaActualDocente * tamanoPaginaDocente, totalFiltradas);
            const etiqueta = totalFiltradas === 1 ? 'sección' : 'secciones';
            const textoInfo = `Mostrando ${inicio} - ${fin} de ${totalFiltradas} ${etiqueta}`;
            const textoPagina = `Pág. ${paginaActualDocente} / ${totalPaginasServidorDocente}`;

            ['top', 'bottom'].forEach(pos => {
                const infoEl = document.getElementById(`docente-pag-info-${pos}`);
                const indEl = document.getElementById(`docente-pag-indicador-${pos}`);
                const prevEl = document.getElementById(`btn-pag-prev-${pos}`);
                const nextEl = document.getElementById(`btn-pag-next-${pos}`);
                if (infoEl) infoEl.textContent = textoInfo;
                if (indEl) indEl.textContent = textoPagina;
                if (prevEl) prevEl.disabled = (paginaActualDocente <= 1);
                if (nextEl) nextEl.disabled = (paginaActualDocente >= totalPaginasServidorDocente);
            });

            if (barTop) barTop.style.display = totalFiltradas > 1 ? 'flex' : 'none';
            if (barBottom) barBottom.style.display = totalFiltradas > tamanoPaginaDocente ? 'flex' : 'none';

            if (hacerScroll && barTop) {
                barTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (err) {
            if (grid) grid.style.opacity = '1';
            window.Lay8UI.toast(err.message || 'Error al cargar secciones.', 'error');
        }
    }

    function filtrarSeccionesDocente(termino) {
        terminoActivoDocente = (termino || '').trim();
        paginaActualDocente = 1;

        const btnClear = document.getElementById('btn-limpiar-busqueda-docente');
        if (btnClear) {
            btnClear.style.display = terminoActivoDocente.length > 0 ? 'inline-flex' : 'none';
        }

        if (esModoServidorDocente()) {
            clearTimeout(debounceDocenteTimer);
            debounceDocenteTimer = setTimeout(() => cargarSeccionesServidorDocente(false), 240);
            return;
        }
        aplicarFiltrosYRenderizarDocente(false);
    }

    function filtrarCarreraDocente(carrera, botonActivo) {
        carreraActivaDocente = (carrera || '').trim().toUpperCase();
        paginaActualDocente = 1;

        document.querySelectorAll('#docente-filtros-carrera .btn-carrera-docente').forEach(btn => {
            const btnCar = (btn.getAttribute('data-carrera') || '').trim().toUpperCase();
            btn.classList.toggle('activo', btnCar === carreraActivaDocente);
        });
        if (botonActivo) {
            botonActivo.classList.add('activo');
        }

        if (esModoServidorDocente()) {
            cargarSeccionesServidorDocente(false);
            return;
        }
        aplicarFiltrosYRenderizarDocente(false);
    }

    function cambiarPaginaDocente(delta) {
        if (esModoServidorDocente()) {
            const nuevaPag = Math.min(totalPaginasServidorDocente, Math.max(1, paginaActualDocente + delta));
            if (nuevaPag === paginaActualDocente) return;
            paginaActualDocente = nuevaPag;
            cargarSeccionesServidorDocente(true);
            return;
        }

        const totalPaginas = Math.max(1, Math.ceil(tarjetasFiltradasDocente.length / tamanoPaginaDocente));
        const nuevaPagina = Math.min(totalPaginas, Math.max(1, paginaActualDocente + delta));
        if (nuevaPagina === paginaActualDocente) return;

        paginaActualDocente = nuevaPagina;
        renderizarPaginaSeccionesDocente(true);
    }

    function cambiarTamanoPaginaDocente(nuevoTamano) {
        const parsed = parseInt(nuevoTamano, 10);
        tamanoPaginaDocente = [12, 24, 48].includes(parsed) ? parsed : 12;
        paginaActualDocente = 1;

        if (esModoServidorDocente()) {
            cargarSeccionesServidorDocente(false);
            return;
        }
        renderizarPaginaSeccionesDocente(false);
    }

    function aplicarFiltrosYRenderizarDocente(hacerScroll) {
        if (tarjetasDocenteCache.length === 0 && document.querySelector('#tab-doc-secciones .tarjeta-aula-classroom')) {
            const tarjetas = document.querySelectorAll('#tab-doc-secciones .tarjeta-aula-classroom');
            totalDocenteOriginal = tarjetas.length;
            tarjetasDocenteCache = Array.from(tarjetas).map(t => ({
                element: t,
                carrera: (t.getAttribute('data-carrera') || '').trim().toUpperCase(),
                searchCorpus: normalizarTextoDocente(t.textContent || '')
            }));
        }

        const btnClear = document.getElementById('btn-limpiar-busqueda-docente');
        if (btnClear) {
            btnClear.style.display = terminoActivoDocente.length > 0 ? 'inline-flex' : 'none';
        }

        const normTermino = normalizarTextoDocente(terminoActivoDocente);
        const tokens = normTermino ? normTermino.split(/\s+/).filter(Boolean) : [];

        tarjetasFiltradasDocente = tarjetasDocenteCache.filter(item => {
            if (carreraActivaDocente && item.carrera !== carreraActivaDocente) {
                return false;
            }
            if (tokens.length > 0 && !tokens.every(token => item.searchCorpus.includes(token))) {
                return false;
            }
            return true;
        });

        const badge = document.getElementById('badge-conteo-secciones');
        if (badge) {
            badge.textContent = tarjetasFiltradasDocente.length;
        }

        const totalPaginas = Math.max(1, Math.ceil(tarjetasFiltradasDocente.length / tamanoPaginaDocente));
        paginaActualDocente = Math.min(totalPaginas, Math.max(1, paginaActualDocente));

        renderizarPaginaSeccionesDocente(hacerScroll);
    }

    function renderizarPaginaSeccionesDocente(hacerScroll) {
        const sinResultados = document.getElementById('sin-resultados-docente');
        const grid = document.querySelector('#tab-doc-secciones .grid-aulas-classroom');
        const txtTermino = document.getElementById('texto-sin-resultados-docente');
        const barTop = document.getElementById('docente-paginacion-top');
        const barBottom = document.getElementById('docente-paginacion-bottom');

        // Ocultar todas las tarjetas primero y luego mostrar solo el lote de la pagina actual
        tarjetasDocenteCache.forEach(item => {
            item.element.style.display = 'none';
        });

        const totalFiltradas = tarjetasFiltradasDocente.length;

        if (totalFiltradas === 0) {
            if (grid) grid.style.display = 'none';
            if (barTop) barTop.style.display = 'none';
            if (barBottom) barBottom.style.display = 'none';
            if (sinResultados) {
                sinResultados.style.display = 'block';
                if (txtTermino) {
                    if (terminoActivoDocente) {
                        const escaped = window.Lay8UI.escapeHtml(terminoActivoDocente);
                        txtTermino.innerHTML = `No hay secciones que coincidan con "<b>${escaped}</b>".`;
                    } else {
                        txtTermino.textContent = 'No hay secciones registradas para este filtro.';
                    }
                }
            }
            return;
        }

        if (sinResultados) sinResultados.style.display = 'none';
        if (grid) grid.style.display = 'grid';

        const totalPaginas = Math.max(1, Math.ceil(totalFiltradas / tamanoPaginaDocente));
        paginaActualDocente = Math.min(totalPaginas, Math.max(1, paginaActualDocente));

        const inicio = (paginaActualDocente - 1) * tamanoPaginaDocente;
        const fin = Math.min(inicio + tamanoPaginaDocente, totalFiltradas);

        for (let i = inicio; i < fin; i++) {
            tarjetasFiltradasDocente[i].element.style.display = 'flex';
        }

        const etiqueta = totalFiltradas === 1 ? 'sección' : 'secciones';
        const textoInfo = `Mostrando ${inicio + 1} - ${fin} de ${totalFiltradas} ${etiqueta}`;
        const textoPagina = `Pág. ${paginaActualDocente} / ${totalPaginas}`;

        ['top', 'bottom'].forEach(pos => {
            const infoEl = document.getElementById(`docente-pag-info-${pos}`);
            const indEl = document.getElementById(`docente-pag-indicador-${pos}`);
            const prevEl = document.getElementById(`btn-pag-prev-${pos}`);
            const nextEl = document.getElementById(`btn-pag-next-${pos}`);

            if (infoEl) infoEl.textContent = textoInfo;
            if (indEl) indEl.textContent = textoPagina;
            if (prevEl) prevEl.disabled = (paginaActualDocente <= 1);
            if (nextEl) nextEl.disabled = (paginaActualDocente >= totalPaginas);
        });

        if (barTop) {
            barTop.style.display = totalDocenteOriginal > 1 ? 'flex' : 'none';
        }
        if (barBottom) {
            barBottom.style.display = totalFiltradas > tamanoPaginaDocente ? 'flex' : 'none';
        }

        if (hacerScroll && barTop) {
            barTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function limpiarBuscadorDocente() {
        const input = document.getElementById('filtro-secciones-docente');
        if (input) {
            input.value = '';
            input.focus();
        }
        terminoActivoDocente = '';
        carreraActivaDocente = '';
        paginaActualDocente = 1;

        document.querySelectorAll('#docente-filtros-carrera .btn-carrera-docente').forEach(btn => {
            const btnCar = (btn.getAttribute('data-carrera') || '').trim();
            btn.classList.toggle('activo', btnCar === '');
        });

        const btnClear = document.getElementById('btn-limpiar-busqueda-docente');
        if (btnClear) btnClear.style.display = 'none';

        if (esModoServidorDocente()) {
            cargarSeccionesServidorDocente(false);
            return;
        }
        aplicarFiltrosYRenderizarDocente(false);
    }

    async function responderSolicitudExtemporaneaDocente(solicitudId, accion, nombreAlumno, materia) {
        let motivo = '';
        if (accion === 'aprobar') {
            const ok = await window.Lay8UI.confirm(
                `¿Deseas autorizar la inscripción extemporánea del alumno ${nombreAlumno} en la asignatura ${materia}?`,
                { title: 'Aprobar Solicitud Extemporánea', confirmText: 'Aprobar Alumno' }
            );
            if (!ok) return;
        } else {
            motivo = await window.Lay8UI.prompt(
                `¿Deseas rechazar la solicitud de ${nombreAlumno} para cursar ${materia}? Puedes indicar un motivo opcional:`,
                {
                    title: 'Rechazar Solicitud',
                    placeholder: 'Ej: Sección sin cupos disponibles o no cumple prerrequisitos...',
                    confirmText: 'Rechazar Solicitud',
                    maxLength: 255,
                    danger: true
                }
            );
            if (motivo === null) return;
        }

        const formData = new FormData();
        formData.append('solicitud_id', solicitudId);
        formData.append('accion', accion);
        formData.append('motivo', motivo || '');

        try {
            const data = await window.Lay8UI.apiFetch(BASE_URL + 'docente/responder-solicitud-extemporanea', {
                method: 'POST',
                body: formData
            });

            window.Lay8UI.toast(data.mensaje || 'Solicitud procesada correctamente.', accion === 'aprobar' ? 'exito' : 'info');

            const fila = document.getElementById('fila-solicitud-' + solicitudId);
            if (fila) {
                fila.style.transition = 'all 0.3s ease';
                fila.style.opacity = '0';
                fila.style.transform = 'scale(0.96)';
                setTimeout(() => {
                    fila.remove();
                    const badge = document.getElementById('badge-conteo-solicitudes');
                    if (badge) {
                        const actual = parseInt(badge.textContent || '0', 10);
                        const nuevo = Math.max(0, actual - 1);
                        badge.textContent = nuevo;
                        if (nuevo === 0) {
                            badge.classList.remove('badge-conteo-alerta');
                        }
                    }

                    const tbody = document.getElementById('tbody-solicitudes-extemporaneas');
                    if (tbody && tbody.querySelectorAll('tr').length === 0) {
                        const tablaWrap = document.getElementById('contenedor-tabla-solicitudes');
                        const vacioEl = document.getElementById('estado-vacio-solicitudes');
                        if (tablaWrap) tablaWrap.style.display = 'none';
                        if (vacioEl) vacioEl.style.display = '';
                    }
                }, 300);
            }
        } catch (err) {
            window.Lay8UI.toast(err.message || 'Error al procesar la solicitud.', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        asegurarModalesEnBodyDocente();
        inicializarBuscadorDocente();

        document.querySelectorAll('.btn-responder-solicitud-ext').forEach(btn => {
            btn.addEventListener('click', () => {
                responderSolicitudExtemporaneaDocente(
                    parseInt(btn.dataset.solicitudId, 10),
                    btn.dataset.accion || '',
                    btn.dataset.alumno || '',
                    btn.dataset.materia || ''
                );
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            cambiarTabDocente(tab);
        }
    });

    window.cambiarTabDocente = cambiarTabDocente;
    window.responderSolicitudExtemporaneaDocente = responderSolicitudExtemporaneaDocente;
