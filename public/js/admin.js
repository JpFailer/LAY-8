// panel de control admin: no tocar sin cafe en mano

const BASE_URL = window.BASE_URL || '/';

// logica de pestañas y modales del admin

function mostrarTabAdmin(tabId) {
    document.querySelectorAll('.admin-tab-pane').forEach(pane => pane.classList.remove('activa'));
    document.querySelectorAll('.tab-btn-admin').forEach(btn => btn.classList.remove('activa'));

    const paneActiva = document.getElementById('tab-' + tabId);
    const btnActivo  = document.getElementById('tabbtn-' + tabId);

    if (paneActiva) paneActiva.classList.add('activa');
    if (btnActivo) btnActivo.classList.add('activa');
}

function filtrarTablaUsuarios() {
    const busqueda = document.getElementById('filtro-usuarios-input').value.toLowerCase().trim();
    const filas = document.querySelectorAll('#tabla-usuarios-admin .fila-usuario-item');
    filas.forEach(fila => {
        const texto = fila.getAttribute('data-texto') || '';
        fila.style.display = (texto.indexOf(busqueda) !== -1) ? '' : 'none';
    });
}

function actualizarNombreHorario(input) {
    const contenedor = document.getElementById('nombre-archivo-horario');
    if (input.files && input.files[0]) {
        contenedor.innerHTML = '<i class="bx bxs-file-pdf" style="color:#ef4444;"></i> ' + input.files[0].name;
    } else {
        contenedor.innerHTML = '';
    }
}

// aceptar o mandarle f al registro del docente
let docenteSeleccionadoId = null;

function aprobarProfesor(id, username) {
    if (!confirm('¿Confirmas aprobar al usuario @' + username + ' como Profesor oficial de la UMC?')) return;

    fetch(BASE_URL + 'api/admin/aprobar-profesor/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.mensaje);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo aprobar.'));
        }
    })
    .catch(() => alert('Error al procesar la solicitud.'));
}

function abrirModalDenegarProfesor(id, username) {
    docenteSeleccionadoId = id;
    document.getElementById('denegar-docente-nombre').innerText = 'Docente: @' + username;
    document.getElementById('motivo-denegar-input').value = '';
    document.getElementById('modal-denegar-profesor').style.display = 'flex';
}

function cerrarModalDenegar() {
    document.getElementById('modal-denegar-profesor').style.display = 'none';
}

function confirmarDenegarProfesor() {
    const motivo = document.getElementById('motivo-denegar-input').value.trim();
    if (!motivo) {
        alert('Por favor ingresa un motivo para el rechazo.');
        return;
    }

    const formData = new FormData();
    formData.append('motivo', motivo);

    fetch(BASE_URL + 'api/admin/denegar-profesor/' + docenteSeleccionadoId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.mensaje);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo rechazar.'));
        }
    })
    .catch(() => alert('Error de conexión.'));
}

// ban martillo, desbanear y borrar cuentas
let usuarioSeleccionadoId = null;

function abrirModalBanear(id, username) {
    usuarioSeleccionadoId = id;
    document.getElementById('banear-usuario-nombre').innerText = 'Suspender cuenta de: @' + username;
    document.getElementById('motivo-banear-input').value = '';
    document.getElementById('modal-banear-usuario').style.display = 'flex';
}

function cerrarModalBanear() {
    document.getElementById('modal-banear-usuario').style.display = 'none';
}

function confirmarBanearUsuario() {
    const motivo = document.getElementById('motivo-banear-input').value.trim();
    if (!motivo) {
        alert('Por favor ingresa un motivo para el baneo.');
        return;
    }

    const formData = new FormData();
    formData.append('motivo', motivo);

    fetch(BASE_URL + 'api/admin/usuarios/banear/' + usuarioSeleccionadoId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.mensaje);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo banear.'));
        }
    })
    .catch(() => alert('Error de red.'));
}

function desbanearUsuario(id, username) {
    if (!confirm('¿Reactivar la cuenta de @' + username + '?')) return;

    fetch(BASE_URL + 'api/admin/usuarios/desbanear/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.mensaje);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo desbanear.'));
        }
    })
    .catch(() => alert('Error al procesar la solicitud.'));
}

function eliminarUsuario(id, username) {
    if (!confirm('⚠️ ¿Estás seguro de eliminar PERMANENTEMENTE a @' + username + '? Esta acción no se puede deshacer.')) return;

    fetch(BASE_URL + 'api/admin/usuarios/eliminar/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.mensaje);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo eliminar el usuario.'));
        }
    })
    .catch(() => alert('Error de conexión.'));
}

// limpiar archivos que no van en la biblioteca
let aporteSeleccionadoId = null;

function abrirModalEliminarAporte(id, titulo) {
    aporteSeleccionadoId = id;
    document.getElementById('eliminar-aporte-titulo').innerText = 'Aporte: ' + titulo;
    document.getElementById('motivo-eliminar-aporte-input').value = '';
    document.getElementById('modal-eliminar-aporte').style.display = 'flex';
}

function cerrarModalEliminarAporte() {
    document.getElementById('modal-eliminar-aporte').style.display = 'none';
}

function confirmarEliminarAporte() {
    const motivo = document.getElementById('motivo-eliminar-aporte-input').value.trim();
    const formData = new FormData();
    if (motivo) formData.append('motivo', motivo);

    fetch(BASE_URL + 'api/admin/biblioteca/eliminar/' + aporteSeleccionadoId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.mensaje);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo eliminar el recurso.'));
        }
    })
    .catch(() => alert('Error de red.'));
}

// procesar el pdf de horarios de cada carrera
function abrirModalHorariosCarreras() {
    const modal = document.getElementById('modal-actualizar-horarios-carreras');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function cerrarModalHorariosCarreras() {
    const modal = document.getElementById('modal-actualizar-horarios-carreras');
    if (modal) {
        modal.style.display = 'none';
    }
}

function onArchivoCarreraSeleccionado(codCarrera, input) {
    const file = input.files && input.files[0];
    const btnSubir = document.getElementById('btn-subir-' + codCarrera);
    const labelFile = document.getElementById('label-file-' + codCarrera);
    if (file) {
        labelFile.innerText = file.name.length > 20 ? (file.name.substring(0, 18) + '...') : file.name;
        if (btnSubir) {
            btnSubir.disabled = false;
            btnSubir.style.opacity = '1';
        }
    } else {
        labelFile.innerText = 'Seleccionar PDF';
        if (btnSubir) {
            btnSubir.disabled = true;
            btnSubir.style.opacity = '0.6';
        }
    }
}

async function subirHorarioCarrera(codCarrera) {
    const input = document.getElementById('file-horario-' + codCarrera);
    const btnSubir = document.getElementById('btn-subir-' + codCarrera);
    const banner = document.getElementById('banner-feedback-horarios');

    if (!input || !input.files || !input.files[0]) {
        alert('Por favor selecciona un archivo PDF de horario primero.');
        return;
    }

    const formData = new FormData();
    formData.append('horario_pdf', input.files[0]);
    formData.append('carrera', codCarrera);

    const csrfInput = document.querySelector('input[name="csrf_token_name"]') || (document.querySelector(`input[name=\"${window.CSRF_TOKEN || 'csrf_test_name'}\"]`) || document.querySelector('#global-csrf-container input'));
    if (csrfInput) {
        formData.append(csrfInput.name, csrfInput.value);
    }

    btnSubir.disabled = true;
    btnSubir.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Procesando...';

    if (banner) {
        banner.style.display = 'block';
        banner.style.background = '#eff6ff';
        banner.style.color = '#1e40af';
        banner.style.border = '1px solid #bfdbfe';
        banner.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Leyendo semestres, horas exactas de inicio/fin, secciones y aulas de <strong>' + codCarrera + '</strong>... Esto puede tomar entre 15 y 45 segundos según la cantidad de páginas del PDF.';
    }

    try {
        const res = await fetch((window.BASE_URL || '/') + 'api/admin/horario/actualizar', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (data.success) {
            if (banner) {
                banner.style.background = '#ecfdf5';
                banner.style.color = '#065f46';
                banner.style.border = '1px solid #a7f3d0';
                banner.innerHTML = '<i class="bx bx-check-circle"></i> ¡Éxito! Se sincronizaron <strong>' + data.clases_cargadas + '</strong> bloques de clases para <strong>' + codCarrera + '</strong> con horas exactas en el mapa.';
            }

            const card = document.getElementById('card-horario-' + codCarrera);
            if (card) {
                const metaStatus = card.querySelector('.carrera-meta-status');
                if (metaStatus) {
                    metaStatus.innerHTML = '<span class="badge-status-ok" style="color: #059669; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="bx bxs-check-circle"></i> <span id="conteo-clases-' + codCarrera + '">' + data.clases_cargadas + '</span> bloques sincronizados</span><span style="color: #94a3b8; margin: 0 4px;">•</span><span style="color: #64748b;" id="fecha-sync-' + codCarrera + '">' + data.fecha + '</span>';
                }
            }

            btnSubir.innerHTML = '<i class="bx bx-check"></i> Sincronizado';
            setTimeout(() => {
                btnSubir.innerHTML = '<i class="bx bx-sync"></i> Procesar';
                btnSubir.disabled = true;
                btnSubir.style.opacity = '0.6';
                const labelFile = document.getElementById('label-file-' + codCarrera);
                if (labelFile) labelFile.innerText = 'Seleccionar PDF';
                input.value = '';
            }, 3000);
        } else {
            throw new Error(data.error || 'Error al procesar el horario');
        }
    } catch (err) {
        if (banner) {
            banner.style.background = '#fef2f2';
            banner.style.color = '#991b1b';
            banner.style.border = '1px solid #fecaca';
            banner.innerHTML = '<i class="bx bx-error-circle"></i> Error al sincronizar: ' + err.message;
        }
        btnSubir.disabled = false;
        btnSubir.innerHTML = '<i class="bx bx-sync"></i> Reintentar';
    }
}

// asignarle sus materias a cada profe
let profIdActualAsignar = null;

function abrirModalAsignarSecciones(profId, profNombre) {
    profIdActualAsignar = profId;
    document.getElementById('docente-asignar-nombre').textContent = profNombre;
    document.getElementById('modal-asignar-secciones-docente').style.display = 'flex';
    document.getElementById('input-buscar-seccion').value = '';
    document.getElementById('lista-secciones-busqueda').innerHTML = '<p style="font-size: 0.82rem; color: #94a3b8; margin: 0;">Escribe arriba para buscar entre las secciones oficiales del campus.</p>';
    cargarSeccionesDocente(profId);
}

function cerrarModalAsignarSecciones() {
    document.getElementById('modal-asignar-secciones-docente').style.display = 'none';
    profIdActualAsignar = null;
}

async function cargarSeccionesDocente(profId) {
    const cont = document.getElementById('lista-secciones-asignadas');
    cont.innerHTML = '<div style="text-align: center; padding: 15px; color: #64748b;"><i class="bx bx-loader-alt bx-spin"></i> Cargando secciones...</div>';
    try {
        const resp = await fetch((window.BASE_URL || '/') + 'api/admin/profesor-secciones/' + profId);
        const data = await resp.json();
        if (data.success && data.secciones && data.secciones.length > 0) {
            cont.innerHTML = data.secciones.map(s => `
                <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 8px;">
                    <div>
                        <strong>${s.materia}</strong> <span style="font-size: 0.82rem; color: #2563eb; font-weight: 600;">[${s.codigo_materia} - Secc. ${s.codigo_seccion}]</span>
                        <div style="font-size: 0.78rem; color: #64748b;">Carrera: ${s.carrera || 'UMC'}</div>
                    </div>
                    <button type="button" class="btn-accion-admin btn-rechazar" style="padding: 5px 12px; font-size: 0.8rem;" onclick="desasignarSeccion(${s.id})">
                        <i class="bx bx-trash"></i> Quitar
                    </button>
                </div>
            `).join('');
        } else {
            cont.innerHTML = '<p style="font-size: 0.85rem; color: #94a3b8; margin: 0; padding: 10px; text-align: center; background: #f8fafc; border-radius: 8px;">Este docente no tiene secciones asignadas actualmente.</p>';
        }
    } catch(e) {
        cont.innerHTML = '<p style="color: #ef4444; font-size: 0.85rem;">Error al cargar las secciones.</p>';
    }
}

let timerBusquedaSecciones = null;
function buscarSeccionesDisponibles(q) {
    clearTimeout(timerBusquedaSecciones);
    q = (q || '').trim();
    const resCont = document.getElementById('lista-secciones-busqueda');
    if (q.length < 2) {
        resCont.innerHTML = '<p style="font-size: 0.82rem; color: #94a3b8; margin: 0;">Escribe al menos 2 caracteres para buscar...</p>';
        return;
    }

    timerBusquedaSecciones = setTimeout(async () => {
        resCont.innerHTML = '<div style="text-align: center; padding: 10px; color: #64748b;"><i class="bx bx-loader-alt bx-spin"></i> Buscando secciones...</div>';
        try {
            const resp = await fetch((window.BASE_URL || '/') + 'api/admin/buscar-secciones?q=' + encodeURIComponent(q));
            const data = await resp.json();
            if (data.success && data.secciones && data.secciones.length > 0) {
                resCont.innerHTML = data.secciones.map(s => {
                    const docenteActual = s.prof_nombre ? `${s.prof_nombre} ${s.prof_apellido}` : '<span style="color:#059669; font-weight:600;">Disponible (Sin docente)</span>';
                    return `
                        <div style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; margin-bottom: 6px; font-size: 0.85rem; background: #ffffff;">
                            <div>
                                <strong>${s.materia}</strong> (${s.codigo_materia} - Secc. ${s.codigo_seccion})
                                <div style="font-size: 0.76rem; color: #64748b;">Docente actual: <em>${docenteActual}</em></div>
                            </div>
                            <button type="button" class="btn-accion-admin btn-aprobar" style="padding: 5px 12px; font-size: 0.8rem;" onclick="asignarSeccion(${s.id})">
                                <i class="bx bx-plus"></i> Asignar
                            </button>
                        </div>
                    `;
                }).join('');
            } else {
                resCont.innerHTML = '<p style="font-size: 0.82rem; color: #94a3b8; margin: 0;">No se encontraron secciones con ese criterio.</p>';
            }
        } catch(e) {
            resCont.innerHTML = '<p style="color: #ef4444; font-size: 0.82rem;">Error al buscar secciones.</p>';
        }
    }, 280);
}

async function asignarSeccion(seccionId) {
    if (!profIdActualAsignar) return;
    const formData = new FormData();
    formData.append('profesor_id', profIdActualAsignar);
    formData.append('seccion_id', seccionId);

    const headers = { 'X-Requested-With': 'XMLHttpRequest' };
    const csrfInput = (document.querySelector(`input[name=\"${window.CSRF_TOKEN || 'csrf_test_name'}\"]`) || document.querySelector('#global-csrf-container input'));
    if (csrfInput) {
        formData.append(window.CSRF_TOKEN || 'csrf_test_name', csrfInput.value);
        // sistema de cabeceras csrf antidayanas (inyeccion de token en cada peticion)
        headers[window.CSRF_HEADER || 'X-CSRF-TOKEN'] = csrfInput.value;
    }

    try {
        const resp = await fetch((window.BASE_URL || '/') + 'api/admin/asignar-seccion', { method: 'POST', body: formData, headers: headers });
        const res = await resp.json();
        if (res.success) {
            cargarSeccionesDocente(profIdActualAsignar);
            buscarSeccionesDisponibles(document.getElementById('input-buscar-seccion').value);
        } else {
            alert(res.error || 'No se pudo asignar la sección.');
        }
    } catch(e) {
        alert('Error al procesar la asignación.');
    }
}

async function desasignarSeccion(seccionId) {
    if (!confirm('¿Deseas quitar esta sección del docente?')) return;
    const formData = new FormData();
    formData.append('seccion_id', seccionId);

    const headers = { 'X-Requested-With': 'XMLHttpRequest' };
    const csrfInput = (document.querySelector(`input[name=\"${window.CSRF_TOKEN || 'csrf_test_name'}\"]`) || document.querySelector('#global-csrf-container input'));
    if (csrfInput) {
        formData.append(window.CSRF_TOKEN || 'csrf_test_name', csrfInput.value);
        // sistema de cabeceras csrf antidayanas (inyeccion de token en cada peticion)
        headers[window.CSRF_HEADER || 'X-CSRF-TOKEN'] = csrfInput.value;
    }

    try {
        const resp = await fetch((window.BASE_URL || '/') + 'api/admin/desasignar-seccion', { method: 'POST', body: formData, headers: headers });
        const res = await resp.json();
        if (res.success) {
            cargarSeccionesDocente(profIdActualAsignar);
            buscarSeccionesDisponibles(document.getElementById('input-buscar-seccion').value);
        } else {
            alert(res.error || 'No se pudo desasignar la sección.');
        }
    } catch(e) {
        alert('Error al procesar la desasignación.');
    }
}


// Inicializar pestaña activa desde el parámetro URL ?tab=...
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab');
    if (tabParam) {
        mostrarTabAdmin(tabParam);
    }
});
