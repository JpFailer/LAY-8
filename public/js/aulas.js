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

    // abrir la pestaña que venia en el link e inicializar buscadores antidayanas
    document.addEventListener('DOMContentLoaded', () => {
        inicializarBuscadorMaterias();
        inicializarComboboxMaterias();

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

            // validacion antidayanas de materia seleccionada
            const secId = document.getElementById('seccion_id')?.value;
            if (!secId) {
                mostrarToastAulas('Por favor busca y selecciona una materia inscrita.', 'error');
                const trigger = document.getElementById('comboMateriaTrigger');
                if (trigger) {
                    trigger.classList.add('combobox-error-glow');
                    setTimeout(() => trigger.classList.remove('combobox-error-glow'), 1800);
                }
                toggleComboMateria(true);
                return;
            }

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

    // =========================================================================
    // SISTEMA DE BÚSQUEDA Y FILTRADO DE MATERIAS (ANTIDAYANAS - CASE & ACCENT INSENSITIVE)
    // =========================================================================

    // normalizador antidayanas: porque ningun estudiante ni profesor estresado en semana de parciales va a poner acentos
    function normalizarTextoAulas(txt) {
        return (txt || '')
            .toString()
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
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
            
            // corpus indexado para busqueda relampago a 60fps
            const searchCorpus = normalizarTextoAulas(`${titulo} ${codigo} ${seccion} secc ${semestre} ${profesor} ${aulas}`);
            return {
                element: card,
                searchCorpus: searchCorpus
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
            // debe coincidir con todas las palabras (ej: "calculo sec a")
            const matchesAll = tokens.every(token => item.searchCorpus.includes(token));
            if (matchesAll) {
                item.element.style.display = '';
                visibleCount++;
            } else {
                item.element.style.display = 'none';
            }
        });

        if (badgeNum) badgeNum.textContent = visibleCount;

        if (visibleCount === 0) {
            if (grid) grid.style.display = 'none';
            if (sinResultados) {
                sinResultados.style.display = 'block';
                if (txtTermino) {
                    txtTermino.innerHTML = `No hay materias que coincidan con "<b>${rawQuery.replace(/</g, '&lt;')}</b>". Prueba buscando sin código o con otra palabra clave.`;
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

    // =========================================================================
    // COMBOBOX BUSCABLE PARA "MATERIA INSCRITA" (ANTIDAYANAS & ZERO-STRESS)
    // =========================================================================

    let comboOpcionesCache = [];

    function inicializarComboboxMaterias() {
        const items = document.querySelectorAll('.combo-option-item');
        comboOpcionesCache = Array.from(items).map(item => {
            const code = item.getAttribute('data-code') || '';
            const nombre = item.getAttribute('data-nombre') || '';
            const seccion = item.getAttribute('data-seccion') || '';
            const corpus = normalizarTextoAulas(`${code} ${nombre} ${seccion} secc`);
            return {
                element: item,
                corpus: corpus
            };
        });

        // cerrar al hacer clic fuera del combobox
        document.addEventListener('click', (e) => {
            const combo = document.getElementById('comboMateriaInscrita');
            if (combo && !combo.contains(e.target)) {
                cerrarComboMateria();
            }
        });

        // cerrar con escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cerrarComboMateria();
            }
        });
    }

    function toggleComboMateria(forzarAbrir = false) {
        const dropdown = document.getElementById('comboMateriaDropdown');
        const trigger = document.getElementById('comboMateriaTrigger');
        const inputSearch = document.getElementById('inputBuscarMateriaInscrita');
        if (!dropdown || !trigger) return;

        const estaAbierto = dropdown.style.display === 'block';
        if (estaAbierto && !forzarAbrir) {
            cerrarComboMateria();
        } else {
            dropdown.style.display = 'block';
            trigger.classList.add('activo');
            if (inputSearch) {
                inputSearch.value = '';
                filtrarComboMaterias('');
                setTimeout(() => inputSearch.focus(), 60);
            }
        }
    }

    function cerrarComboMateria() {
        const dropdown = document.getElementById('comboMateriaDropdown');
        const trigger = document.getElementById('comboMateriaTrigger');
        if (dropdown) dropdown.style.display = 'none';
        if (trigger) trigger.classList.remove('activo');
    }

    function filtrarComboMaterias(query) {
        const norm = normalizarTextoAulas(query);
        const vacio = document.getElementById('comboMateriaVacio');
        const tokens = norm.split(/\s+/).filter(Boolean);
        let visibles = 0;

        comboOpcionesCache.forEach(opt => {
            const matches = tokens.length === 0 || tokens.every(t => opt.corpus.includes(t));
            if (matches) {
                opt.element.style.display = 'flex';
                visibles++;
            } else {
                opt.element.style.display = 'none';
            }
        });

        if (vacio) vacio.style.display = visibles === 0 ? 'block' : 'none';
    }

    function seleccionarComboMateria(id, label, code, nombre, secc) {
        const hiddenInput = document.getElementById('seccion_id');
        const selectedContent = document.getElementById('comboMateriaSelected');
        const btnClear = document.getElementById('btnClearComboMateria');
        const trigger = document.getElementById('comboMateriaTrigger');

        if (hiddenInput) hiddenInput.value = id;

        if (selectedContent) {
            selectedContent.innerHTML = `
                <span class="combo-selected-pill">
                    <i class="bx bx-check-circle pill-check"></i>
                    <b class="pill-code">[${code}]</b>
                    <span class="pill-name">${nombre}</span>
                    <span class="pill-sec">Secc. ${secc}</span>
                </span>
            `;
        }

        if (btnClear) btnClear.style.display = 'inline-flex';
        if (trigger) trigger.classList.remove('combobox-error-glow');

        cerrarComboMateria();
    }

    function limpiarSeleccionComboMateria(event) {
        if (event) event.stopPropagation();
        const hiddenInput = document.getElementById('seccion_id');
        const selectedContent = document.getElementById('comboMateriaSelected');
        const btnClear = document.getElementById('btnClearComboMateria');

        if (hiddenInput) hiddenInput.value = '';
        if (selectedContent) {
            selectedContent.innerHTML = `
                <i class="bx bx-search combo-icon-search"></i>
                <span class="combobox-placeholder">Selecciona o busca tu materia (ej: Cálculo, LOG-103)...</span>
            `;
        }
        if (btnClear) btnClear.style.display = 'none';
    }

    // =========================================================================
    // SUB-NAVEGACIÓN DE PESTAÑA CARGAR/ARMAR HORARIO (VISTA LIMPIA Y SIMPLIFICADA)
    // =========================================================================

    function cambiarVistaCarga(modo) {
        const btnAmbos = document.getElementById('subnav-btn-ambos');
        const btnManual = document.getElementById('subnav-btn-manual');
        const btnPdf = document.getElementById('subnav-btn-pdf');
        const cardPdf = document.getElementById('card-carga-pdf');
        const cardManual = document.getElementById('card-carga-manual');
        const grid = document.getElementById('contenedor-grid-cargar');

        [btnAmbos, btnManual, btnPdf].forEach(b => b && b.classList.remove('activo'));

        if (modo === 'manual') {
            if (btnManual) btnManual.classList.add('activo');
            if (cardPdf) cardPdf.style.display = 'none';
            if (cardManual) cardManual.style.display = 'block';
            if (grid) grid.style.gridTemplateColumns = '1fr';
        } else if (modo === 'pdf') {
            if (btnPdf) btnPdf.classList.add('activo');
            if (cardManual) cardManual.style.display = 'none';
            if (cardPdf) cardPdf.style.display = 'block';
            if (grid) grid.style.gridTemplateColumns = '1fr';
        } else {
            if (btnAmbos) btnAmbos.classList.add('activo');
            if (cardPdf) cardPdf.style.display = 'block';
            if (cardManual) cardManual.style.display = 'block';
            if (grid) grid.style.gridTemplateColumns = '';
        }
    }

    // =========================================================================
    // GESTIÓN DE ASIGNATURAS EXTEMPORÁNEAS (MODAL, BÚSQUEDA E INSCRIPCIÓN FORMAL)
    // =========================================================================

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

    function ejecutarBusquedaExtemporanea(termino) {
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

        const url = BASE_URL + 'api/aulas/buscar-extemporaneas?q=' + encodeURIComponent(termino) + '&carrera=' + encodeURIComponent(carrera);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.secciones || data.secciones.length === 0) {
                contenedor.innerHTML = `
                    <div style="text-align: center; padding: 24px 10px; color: #94a3b8; font-size: 0.86rem;">
                        <i class="bx bx-info-circle" style="font-size: 1.8rem; display: block; margin-bottom: 6px;"></i>
                        No se encontraron asignaturas disponibles con los criterios seleccionados.
                    </div>
                `;
                return;
            }

            let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
            data.secciones.forEach(sec => {
                const prof = (sec.prof_nombre || sec.prof_apellido) ? `${sec.prof_nombre || ''} ${sec.prof_apellido || ''}`.trim() : 'Docente UMC por asignar';
                const yaInscrita = !!sec.ya_inscrita;
                const materiaEscapada = (sec.materia || '').replace(/'/g, "\\'");

                html += `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: rgba(30, 41, 59, 0.6); border-radius: 8px; border: 1px solid rgba(226, 232, 240, 0.08); gap: 10px;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px; flex-wrap: wrap;">
                                <span style="font-size: 0.72rem; padding: 2px 6px; background: #2563eb; color: #fff; border-radius: 4px; font-weight: 600;">${sec.codigo_materia || ''}</span>
                                <span style="font-size: 0.72rem; padding: 2px 6px; background: rgba(226, 232, 240, 0.1); color: #cbd5e1; border-radius: 4px;">Secc. ${sec.codigo_seccion || ''}</span>
                                <span style="font-size: 0.72rem; padding: 2px 6px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border-radius: 4px;">${sec.carrera || ''}</span>
                                ${sec.semestre_trayecto ? `<span style="font-size: 0.72rem; color: #94a3b8;">Semestre ${sec.semestre_trayecto}</span>` : ''}
                            </div>
                            <div style="font-size: 0.92rem; font-weight: 600; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${sec.materia || ''}
                            </div>
                            <div style="font-size: 0.78rem; color: #94a3b8; margin-top: 2px;">
                                <i class="bx bx-user"></i> ${prof}
                            </div>
                        </div>
                        <div>
                            ${yaInscrita ? `
                                <span style="font-size: 0.78rem; color: #10b981; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background: rgba(16, 185, 129, 0.1); border-radius: 6px;">
                                    <i class="bx bx-check"></i> Inscrita
                                </span>
                            ` : `
                                <button type="button" class="btn-subir-cta" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;" onclick="inscribirMateriaExtemporanea(${sec.id}, '${materiaEscapada}')">
                                    <i class="bx bx-plus"></i> Inscribir
                                </button>
                            `}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            contenedor.innerHTML = html;
        })
        .catch(() => {
            contenedor.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #ef4444; font-size: 0.86rem;">
                    <i class="bx bx-error" style="font-size: 1.5rem; display: block; margin-bottom: 6px;"></i>
                    Error de comunicación al consultar el catálogo de asignaturas.
                </div>
            `;
        });
    }

    function inscribirMateriaExtemporanea(seccionId, nombreMateria) {
        if (!confirm('¿Deseas formalizar la inscripción extemporánea de la asignatura ' + nombreMateria + '?')) {
            return;
        }

        const formData = new FormData();
        formData.append('seccion_id', seccionId);

        fetch(BASE_URL + 'api/aulas/inscribir-extemporanea', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.mensaje || 'Asignatura inscrita correctamente.');
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo inscribir la asignatura.'));
            }
        })
        .catch(() => {
            alert('Error de conexión al procesar la inscripción.');
        });
    }

    function confirmarDesinscribirMateria(seccionId, nombreMateria) {
        if (!confirm('¿Confirmas que deseas desincorporar la asignatura ' + nombreMateria + ' de tu registro académico?\n\nEsta acción quitará el aula y sus horarios asociados.')) {
            return;
        }

        const formData = new FormData();
        formData.append('seccion_id', seccionId);

        fetch(BASE_URL + 'api/aulas/desinscribir-materia', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.mensaje || 'Asignatura desincorporada correctamente.');
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo desincorporar la asignatura.'));
            }
        })
        .catch(() => {
            alert('Error de red al procesar la desincorporación.');
        });
    }
