// panel docente: secciones, estados en vivo y tareas (no moverle mucho)

const BASE_URL = window.BASE_URL || '/';

    function cambiarTabDocente(tabId) {
        document.querySelectorAll('.tab-contenido-docente').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.filtros-categorias .btn-filtro').forEach(b => b.classList.remove('activo'));

        const seccionTarget = document.getElementById('tab-doc-' + tabId);
        const btnTarget     = document.getElementById('tabbtn-doc-' + tabId);

        if (seccionTarget) seccionTarget.style.display = 'block';
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
            const formData = new FormData(form);
            const resp = await fetch(BASE_URL + 'docente/actualizar-estado', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const res = await resp.json();
            if (res.success) {
                alert(res.mensaje || '¡Estado transmitido correctamente a los alumnos!');
                window.location.reload();
            } else {
                alert(res.error || (res.errores ? Object.values(res.errores).join('\\n') : 'Error al guardar estado.'));
            }
        } catch(err) {
            alert('Error de red al actualizar estado.');
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
            const formData = new FormData(form);
            const resp = await fetch(BASE_URL + 'docente/crear-tarea', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const res = await resp.json();
            if (res.success) {
                alert(res.mensaje || '¡Evaluación publicada con éxito!');
                window.location.reload();
            } else {
                alert(res.error || (res.errores ? Object.values(res.errores).join('\\n') : 'Error al crear la evaluación.'));
            }
        } catch (err) {
            alert('Error de conexión al crear la tarea.');
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
            const resp = await fetch(BASE_URL + 'docente/entregas/' + actividadId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
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
                const nombreEst = (ent.primer_nombre + ' ' + (ent.segundo_nombre ? ent.segundo_nombre + ' ' : '') + ent.apellidos).trim();
                const calificada = ent.calificacion !== null && ent.calificacion !== undefined;

                const card = document.createElement('div');
                card.className = 'card-solicitud-docente-item';
                card.style.cssText = 'border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 8px;';
                card.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.96rem; display: flex; align-items: center; gap: 6px;">
                                <i class="bx bx-user" style="color: #2563eb;"></i> ${nombreEst}
                                <span style="font-size: 0.8rem; color: #64748b; font-weight: normal;">(C.I: ${ent.cedula || 'N/A'})</span>
                            </div>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                <i class="bx bx-calendar"></i> Entregado: ${ent.created_at || 'Reciente'}
                            </div>
                            ${ent.comentario_alumno ? `<div style="font-size: 0.84rem; color: #334155; margin-top: 6px; background: #f8fafc; padding: 6px 10px; border-radius: 6px; font-style: italic;">"${ent.comentario_alumno}"</div>` : ''}
                        </div>
                        <div>
                            ${ent.archivo_ruta ? `
                                <a href="${BASE_URL}docente/descargar-entrega/${ent.id}" class="btn-subir-cta" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="bx bx-download"></i> Descargar Trabajo (${ent.archivo_nombre || 'archivo'})
                                </a>
                            ` : `<span style="font-size: 0.8rem; color: #94a3b8;">Sin archivo adjunto</span>`}
                        </div>
                    </div>

                    // form rapido de calificar
                    <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #e2e8f0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <label style="font-size: 0.82rem; font-weight: 700;">Calificación:</label>
                            <input type="number" step="0.5" min="0" max="100" id="nota_entrega_${ent.id}" value="${calificada ? ent.calificacion : ''}" placeholder="0-20" class="input-lay8" style="width: 80px; padding: 6px 8px; font-weight: 700;">
                        </div>
                        <div style="flex: 1; min-width: 180px;">
                            <input type="text" id="feedback_entrega_${ent.id}" value="${ent.feedback_profesor || ''}" placeholder="Retroalimentación para el alumno..." class="input-lay8" style="width: 100%; padding: 6px 10px;">
                        </div>
                        <button type="button" class="btn-subir-cta" onclick="guardarCalificacionEntrega(${ent.id})" style="padding: 6px 14px; font-size: 0.82rem;">
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

    async function guardarCalificacionEntrega(entregaId) {
        const notaInput = document.getElementById('nota_entrega_' + entregaId);
        const fbInput = document.getElementById('feedback_entrega_' + entregaId);

        if (!notaInput || notaInput.value === '') {
            alert('Por favor ingresa una nota numérica.');
            return;
        }

        const formData = new FormData();
        formData.append('entrega_id', entregaId);
        formData.append('calificacion', notaInput.value);
        formData.append('feedback', fbInput ? fbInput.value : '');

        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        const csrfTokenName = window.CSRF_TOKEN || 'csrf_test_name';
        const csrfHeaderName = window.CSRF_HEADER || 'X-CSRF-TOKEN';
        const csrfInput = document.querySelector(`input[name="${csrfTokenName}"]`) || document.querySelector('#global-csrf-container input');
        if (csrfInput && csrfInput.value) {
            formData.append(csrfTokenName, csrfInput.value);
            // sistema de cabeceras csrf antidayanas (inyeccion de token en cada peticion)
            headers[csrfHeaderName] = csrfInput.value;
        }

        try {
            const resp = await fetch(BASE_URL + 'docente/calificar', {
                method: 'POST',
                body: formData,
                headers: headers
            });
            const res = await resp.json();
            if (res.success) {
                alert(res.mensaje || '¡Calificación registrada con éxito!');
            } else {
                alert(res.error || 'Error al guardar la calificación.');
            }
        } catch(e) {
            alert('Error de conexión al calificar.');
        }
    }

    function filtrarSeccionesDocente(termino) {
        termino = (termino || '').toLowerCase().trim();
        const tarjetas = document.querySelectorAll('#tab-doc-secciones .tarjeta-aula-classroom');
        let visibles = 0;
        tarjetas.forEach(t => {
            const texto = t.textContent.toLowerCase();
            if (!termino || texto.includes(termino)) {
                t.style.display = 'flex';
                visibles++;
            } else {
                t.style.display = 'none';
            }
        });
        const badge = document.getElementById('badge-conteo-secciones');
        if (badge) badge.textContent = visibles;
    }
