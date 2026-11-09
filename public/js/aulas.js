// aulas y horario semanal: grilla, bloques y entregas

const BASE_URL = window.BASE_URL || '/';

    // pestañas con url pa no perderse al recargar
    let bloquesModificados = false;

    function cambiarTabAulas(tabId) {
        if (tabId === 'horario' && bloquesModificados) {
            window.location.href = BASE_URL + 'aulas?tab=horario';
            return;
        }

        document.querySelectorAll('.tab-contenido-aulas').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.filtros-categorias .btn-filtro').forEach(b => b.classList.remove('activo'));

        const seccionTarget = document.getElementById('tab-seccion-' + tabId);
        const btnTarget     = document.getElementById('tabbtn-' + tabId);

        if (seccionTarget) seccionTarget.style.display = 'block';
        if (btnTarget) btnTarget.classList.add('activo');

        // guardar tab en url
        try {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        } catch (e) {}
    }

    // abrir la pestaña que venia en el link
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tabSolicitada = urlParams.get('tab');
        if (tabSolicitada && document.getElementById('tab-seccion-' + tabSolicitada)) {
            cambiarTabAulas(tabSolicitada);
        }
    });

    // filtrar por mañana, tarde o noche
    function filtrarTurnosBloques(turno) {
        document.querySelectorAll('.selector-turnos-bloques .btn-turno-filtro').forEach(b => {
            b.classList.toggle('activo', b.getAttribute('data-filtro-turno') === turno);
        });

        document.querySelectorAll('#gridBloquesSelector .card-bloque-check').forEach(card => {
            if (turno === 'todos' || card.getAttribute('data-turno') === turno) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // contar horas seleccionadas
    function actualizarResumenBloques() {
        const checked = Array.from(document.querySelectorAll('.chk-bloque-hora:checked'));
        const barra = document.getElementById('resumenBloquesBarra');
        const lblTotal = document.getElementById('lblTotalBloques');
        const lblRango = document.getElementById('lblRangoHoras');

        // pintar la tarjetica de azul
        document.querySelectorAll('.card-bloque-check').forEach(c => {
            const chk = c.querySelector('input[type="checkbox"]');
            c.classList.toggle('seleccionado', chk && chk.checked);
        });

        if (checked.length === 0) {
            if (barra) barra.style.display = 'none';
            return;
        }

        if (barra) barra.style.display = 'flex';
        if (lblTotal) lblTotal.textContent = checked.length + (checked.length === 1 ? ' bloque' : ' bloques');

        // ordenar las horas
        const horas = checked.map(c => {
            const parts = c.value.split('|');
            return { inicio: parts[0], fin: parts[1] };
        }).sort((a, b) => a.inicio.localeCompare(b.inicio));

        const minInicio = horas[0].inicio.substring(0, 5);
        const maxFin    = horas[horas.length - 1].fin.substring(0, 5);

        // calculo rapido de minutos
        const totalMins = checked.length * 45;
        const horasTotales = Math.floor(totalMins / 60);
        const minsRestantes = totalMins % 60;
        let duracionTxt = '';
        if (horasTotales > 0) duracionTxt += horasTotales + 'h ';
        if (minsRestantes > 0) duracionTxt += minsRestantes + 'm';

        if (lblRango) {
            lblRango.textContent = `(${minInicio} a ${maxFin} • ${duracionTxt.trim()})`;
        }
    }

    function deseleccionarTodosBloques() {
        document.querySelectorAll('.chk-bloque-hora').forEach(c => c.checked = false);
        actualizarResumenBloques();
    }

    // guardar los bloques seleccionados sin recargar
    const formArmar = document.getElementById('formArmarHorarioManual');
    if (formArmar) {
        formArmar.addEventListener('submit', async function(e) {
            e.preventDefault();
            const checked = this.querySelectorAll('.chk-bloque-hora:checked');
            if (checked.length === 0) {
                mostrarToastAulas('Por favor selecciona al menos un bloque de hora oficial.', 'error');
                return;
            }

            const btnSubmit = document.getElementById('btnSubmitArmarHorario');
            const origHtml = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Guardando horario...';

            try {
                const formData = new FormData(this);
                const resp = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const res = await resp.json();
                if (res.success) {
                    mostrarToastAulas(res.mensaje || '¡Bloques agregados con éxito!', 'exito');
                    deseleccionarTodosBloques();
                    bloquesModificados = true;
                    const badge = document.querySelector('#tabbtn-horario .badge-conteo');
                    if (badge && res.insertados) {
                        badge.textContent = parseInt(badge.textContent || '0') + res.insertados;
                    }
                } else {
                    mostrarToastAulas(res.error || 'Ocurrió un error al guardar.', 'error');
                }
            } catch (err) {
                mostrarToastAulas('Error de red al guardar los bloques.', 'error');
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = origHtml;
            }
        });
    }

    // borrar un bloque especifico
    async function eliminarBloqueHorario(id, btnElem) {
        if (!confirm('¿Deseas eliminar este bloque de clase de tu horario?')) return;

        const card = btnElem ? btnElem.closest('.bloque-clase-card') : document.getElementById('bloque-card-' + id);
        if (card) card.style.opacity = '0.35';

        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        const csrfTokenName = window.CSRF_TOKEN || 'csrf_test_name';
        const csrfHeaderName = window.CSRF_HEADER || 'X-CSRF-TOKEN';
        const csrfInput = document.querySelector(`input[name=\"${csrfTokenName}\"]`) || document.querySelector('#global-csrf-container input');
        if (csrfInput) // sistema de cabeceras csrf antidayanas (inyeccion de token en cada peticion)
            headers[csrfHeaderName] = csrfInput.value;

        try {
            const resp = await fetch(BASE_URL + 'aulas/eliminar-bloque/' + id, {
                method: 'POST',
                headers: headers
            });
            const data = await resp.json();
            if (data.success) {
                if (card) {
                    card.style.transition = 'all 0.25s ease';
                    card.style.transform = 'scale(0.8)';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 250);
                }
                const badge = document.querySelector('#tabbtn-horario .badge-conteo');
                if (badge) {
                    const act = parseInt(badge.textContent || '0');
                    if (act > 0) badge.textContent = act - 1;
                }
                mostrarToastAulas('Bloque eliminado correctamente.', 'exito');
            } else {
                if (card) card.style.opacity = '1';
                mostrarToastAulas(data.error || 'No se pudo eliminar el bloque.', 'error');
            }
        } catch (e) {
            if (card) card.style.opacity = '1';
            mostrarToastAulas('Error de conexión al eliminar el bloque.', 'error');
        }
    }

    // limpiar todo el horario si te equivocaste
    async function confirmarLimpiarHorario() {
        if (!confirm('¿Estás seguro de que deseas limpiar tu horario?\n\nSe eliminarán los bloques asignados para que puedas armar tus materias y horas desde cero sin clases conflictivas.')) {
            return;
        }

        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        const csrfTokenName = window.CSRF_TOKEN || 'csrf_test_name';
        const csrfHeaderName = window.CSRF_HEADER || 'X-CSRF-TOKEN';
        const csrfInput = document.querySelector(`input[name=\"${csrfTokenName}\"]`) || document.querySelector('#global-csrf-container input');
        if (csrfInput) // sistema de cabeceras csrf antidayanas (inyeccion de token en cada peticion)
            headers[csrfHeaderName] = csrfInput.value;

        try {
            const resp = await fetch(BASE_URL + 'aulas/limpiar-horario', {
                method: 'POST',
                headers: headers
            });
            const data = await resp.json();
            if (data.success) {
                mostrarToastAulas(data.mensaje || '¡Horario limpiado exitosamente!', 'exito');
                setTimeout(() => {
                    window.location.href = BASE_URL + 'aulas?tab=horario';
                }, 800);
            } else {
                mostrarToastAulas(data.error || 'No se pudo limpiar el horario.', 'error');
            }
        } catch (e) {
            window.location.href = BASE_URL + 'aulas?tab=horario';
        }
    }

    // mensajito toast abajo a la derecha
    function mostrarToastAulas(mensaje, tipo = 'exito') {
        const toast = document.createElement('div');
        toast.className = `toast-notif-aulas ${tipo}`;
        toast.innerHTML = `<i class="bx ${tipo === 'exito' ? 'bx-check-circle' : 'bx-error-circle'}" style="font-size: 1.3rem;"></i> <span>${mensaje}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // filtrar tareas pendientes o listas
    function filtrarActividades(filtro) {
        document.querySelectorAll('.filtros-actividades-chips .chip-actividad').forEach(c => c.classList.remove('activo'));
        event.target.classList.add('activo');

        document.querySelectorAll('.item-actividad-card').forEach(item => {
            const estado = item.getAttribute('data-estado');
            if (filtro === 'todas') {
                item.style.display = 'flex';
            } else if (filtro === 'pendientes' && estado === 'pendiente') {
                item.style.display = 'flex';
            } else if (filtro === 'completadas' && estado === 'completada') {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // marcar tarea como hecha
    function toggleActividadCheckbox(actividadId, checkboxElem) {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest'
        };
        const csrfTokenName = window.CSRF_TOKEN || 'csrf_test_name';
        const csrfHeaderName = window.CSRF_HEADER || 'X-CSRF-TOKEN';
        const csrfInput = document.querySelector(`input[name=\"${csrfTokenName}\"]`) || document.querySelector('#global-csrf-container input');
        if (csrfInput) {
            // sistema de cabeceras csrf antidayanas (inyeccion de token en cada peticion)
            headers[csrfHeaderName] = csrfInput.value;
        }

        fetch(BASE_URL + 'aulas/toggle-actividad/' + actividadId, {
            method: 'POST',
            headers: headers
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const card = checkboxElem.closest('.item-actividad-card');
                if (checkboxElem.checked) {
                    card.classList.add('actividad-completada');
                    card.setAttribute('data-estado', 'completada');
                } else {
                    card.classList.remove('actividad-completada');
                    card.setAttribute('data-estado', 'pendiente');
                }
            } else {
                checkboxElem.checked = !checkboxElem.checked;
                alert('No se pudo actualizar el estado de la tarea.');
            }
        })
        .catch(err => {
            checkboxElem.checked = !checkboxElem.checked;
            console.error('Error al actualizar tarea:', err);
        });
    }

    // modal de crear tarea
    function abrirModalNuevaActividad(seccionId = null, nombreMateria = null) {
        const modal = document.getElementById('modalNuevaActividad');
        if (seccionId) {
            const selectSec = document.getElementById('act_seccion_id');
            if (selectSec) selectSec.value = seccionId;
        }
        modal.style.display = 'flex';
    }

    function cerrarModalNuevaActividad() {
        document.getElementById('modalNuevaActividad').style.display = 'none';
    }

    // poner el nombre del archivo seleccionado
    function mostrarNombreArchivo(input) {
        const label = document.getElementById('texto-nombre-archivo');
        if (input.files && input.files[0]) {
            label.innerText = 'Archivo seleccionado: ' + input.files[0].name;
            label.style.fontWeight = 'bold';
            label.style.color = '#1e3a8a';
        }
    }

    // restaurar horario de la carrera
    async function confirmarRestaurarHorario() {
        if (!confirm('¿Deseas restaurar todos los bloques oficiales asignados por la universidad?')) return;
        try {
            const headers = { 'X-Requested-With': 'XMLHttpRequest' };
            const csrfTokenName = window.CSRF_TOKEN || 'csrf_test_name';
        const csrfHeaderName = window.CSRF_HEADER || 'X-CSRF-TOKEN';
        const csrfInput = document.querySelector(`input[name=\"${csrfTokenName}\"]`) || document.querySelector('#global-csrf-container input');
            if (csrfInput) // sistema de cabeceras csrf antidayanas (inyeccion de token en cada peticion)
            headers[csrfHeaderName] = csrfInput.value;

            const resp = await fetch(BASE_URL + 'aulas/restaurar-horario', {
                method: 'POST',
                headers: headers
            });
            const res = await resp.json();
            if (res.success) {
                mostrarToastAulas(res.mensaje, 'exito');
                setTimeout(() => { window.location.href = BASE_URL + 'aulas?tab=horario'; }, 800);
            } else {
                mostrarToastAulas(res.error || 'No se pudo restaurar.', 'error');
            }
        } catch(e) {
            window.location.href = BASE_URL + 'aulas?tab=horario';
        }
    }

    // entregar tarea del classroom
    let actividadEntregaSeleccionada = null;
    function abrirModalEntregarTarea(actividadId, titulo, materia) {
        actividadEntregaSeleccionada = actividadId;
        document.getElementById('entregar_modal_titulo').textContent = titulo;
        document.getElementById('entregar_modal_materia').textContent = materia;
        const form = document.getElementById('formEntregarTareaAlumno');
        form.action = BASE_URL + 'aulas/entregar-tarea/' + actividadId;
        document.getElementById('modalEntregarTarea').style.display = 'flex';
    }

    function cerrarModalEntregarTarea() {
        document.getElementById('modalEntregarTarea').style.display = 'none';
    }

    const formEntrega = document.getElementById('formEntregarTareaAlumno');
    if (formEntrega) {
        formEntrega.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitEntrega');
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Subiendo entrega...';

            try {
                const formData = new FormData(this);
                const resp = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const res = await resp.json();
                if (res.success) {
                    mostrarToastAulas(res.mensaje || '¡Tarea entregada con éxito!', 'exito');
                    cerrarModalEntregarTarea();
                    setTimeout(() => { window.location.reload(); }, 900);
                } else {
                    mostrarToastAulas(res.error || 'Error al subir la entrega.', 'error');
                }
            } catch(err) {
                mostrarToastAulas('Error de conexión al enviar la tarea.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origText;
            }
        });
    }
