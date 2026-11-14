// panel de control admin: no tocar sin cafe en mano

const BASE_URL = window.BASE_URL || '/';

// logica de pestañas y modales del admin

function mostrarTabAdmin(tabId) {
    const paneActiva = document.getElementById('tab-' + tabId);
    const btnActivo  = document.getElementById('tabbtn-' + tabId);
    if (!paneActiva || !btnActivo) return;

    document.querySelectorAll('.admin-tab-pane').forEach(pane => pane.classList.remove('activa'));
    document.querySelectorAll('.tab-btn-admin').forEach(btn => btn.classList.remove('activa'));

    paneActiva.classList.add('activa');
    btnActivo.classList.add('activa');

    try {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url);
    } catch (e) {}
}

function escHtmlAdmin(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function capitalizarAdmin(texto) {
    const s = String(texto ?? '');
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

function formatearFechaAdmin(valor) {
    if (!valor) return 'Reciente';
    const d = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return 'Reciente';
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    return `${dd}/${mm}/${d.getFullYear()}`;
}

function actualizarControlesPaginacionAdmin(prefix, mostrados, total, paginaActual, totalPaginas, etiquetaPlural, etiquetaSingular) {
    const bar = document.getElementById(`admin-${prefix}-paginacion`);
    const info = document.getElementById(`admin-${prefix}-pag-info`);
    const ind = document.getElementById(`admin-${prefix}-pag-indicador`);
    const prev = document.getElementById(`btn-${prefix}-prev`);
    const next = document.getElementById(`btn-${prefix}-next`);
    const pags = Math.max(1, Number(totalPaginas) || 1);
    const pag = Math.min(Math.max(1, Number(paginaActual) || 1), pags);
    const tot = Math.max(0, Number(total) || 0);

    if (bar) {
        bar.dataset.pagina = String(pag);
        bar.dataset.paginas = String(pags);
        bar.dataset.total = String(tot);
    }
    if (info) {
        info.textContent = `Mostrando ${mostrados} de ${tot} ${tot === 1 ? etiquetaSingular : etiquetaPlural}`;
    }
    if (ind) {
        ind.textContent = `Página ${pag} de ${pags}`;
    }
    if (prev) prev.disabled = pag <= 1;
    if (next) next.disabled = pag >= pags;
}

let timerFiltroUsuariosAdmin = null;
let paginaUsuariosAdminActual = 1;

function filtrarTablaUsuarios() {
    clearTimeout(timerFiltroUsuariosAdmin);
    timerFiltroUsuariosAdmin = setTimeout(() => {
        cargarUsuariosAdmin(1);
    }, 260);
}

function cambiarPaginaUsuariosAdmin(delta) {
    const bar = document.getElementById('admin-usuarios-paginacion');
    const pagActual = Number(bar?.dataset?.pagina || paginaUsuariosAdminActual || 1);
    const totalPags = Number(bar?.dataset?.paginas || 1);
    const destino = Math.min(Math.max(1, pagActual + delta), Math.max(1, totalPags));
    if (destino !== pagActual) {
        cargarUsuariosAdmin(destino);
    }
}

async function cargarUsuariosAdmin(pagina = 1) {
    const tbody = document.getElementById('tbody-usuarios-admin');
    const input = document.getElementById('filtro-usuarios-input');
    if (!tbody) return;

    paginaUsuariosAdminActual = Math.max(1, Number(pagina) || 1);
    const buscar = (input?.value || '').trim();
    const qs = new URLSearchParams({
        pagina: String(paginaUsuariosAdminActual),
        limite: '20',
    });
    if (buscar !== '') qs.set('buscar', buscar);

    try {
        const data = await adminFetchJson(BASE_URL + 'api/admin/usuarios?' + qs.toString());
        const lista = Array.isArray(data.usuarios) ? data.usuarios : [];
        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="admin-celda-vacia">No se encontraron usuarios con ese criterio.</td></tr>';
        } else {
            tbody.innerHTML = lista.map((u) => {
                const rol = String(u.rol || '');
                const esAdmin = rol === 'admin' || rol === 'administrador';
                const esProfOk = rol === 'profesor' && u.estado_verificacion === 'aprobado';
                let iconoVerif = '';
                if (esAdmin) {
                    iconoVerif = '<i class="bx bxs-check-circle admin-ico-verif admin-ico-verif-admin" title="Administrador Verificado"></i>';
                } else if (esProfOk) {
                    iconoVerif = '<i class="bx bxs-check-circle admin-ico-verif admin-ico-verif-prof" title="Profesor Verificado"></i>';
                }

                const estaBaneado = (u.estado_cuenta || 'activo') === 'baneado';
                const badgeEstado = estaBaneado
                    ? `<span class="badge-estado-baneado" title="${escHtmlAdmin(u.motivo_baneo || '')}"><i class="bx bx-block"></i> Baneado</span>`
                    : '<span class="badge-estado-activo"><i class="bx bx-check-circle"></i> Activo</span>';

                let btns = '';
                if (estaBaneado) {
                    btns += `<button type="button" class="btn-accion-admin btn-desbanear" data-accion="desbanear" data-id="${Number(u.id)}" data-usuario="${escHtmlAdmin(u.nombre_usuario)}"><i class="bx bx-undo"></i> Reactivar</button>`;
                } else if (!esAdmin) {
                    btns += `<button type="button" class="btn-accion-admin btn-banear" data-accion="banear" data-id="${Number(u.id)}" data-usuario="${escHtmlAdmin(u.nombre_usuario)}"><i class="bx bx-block"></i> Banear</button>`;
                }
                if (!esAdmin) {
                    btns += `<button type="button" class="btn-accion-admin btn-eliminar" data-accion="eliminar-usuario" data-id="${Number(u.id)}" data-usuario="${escHtmlAdmin(u.nombre_usuario)}"><i class="bx bx-trash"></i></button>`;
                }

                const nombreCompleto = `${u.primer_nombre || ''} ${u.apellidos || ''}`.trim();
                return `
                    <tr class="fila-usuario-item">
                        <td>#${Number(u.id)}</td>
                        <td><strong>${escHtmlAdmin(nombreCompleto)}</strong> ${iconoVerif}</td>
                        <td><code class="admin-code-cedula">${escHtmlAdmin(u.cedula || 'N/D')}</code></td>
                        <td>
                            <div>@${escHtmlAdmin(u.nombre_usuario)}</div>
                            <div class="admin-subtexto-muted">${escHtmlAdmin(u.correo)}</div>
                        </td>
                        <td><span class="badge-carrera-tag">${escHtmlAdmin(u.carrera || 'ING-INF')}</span></td>
                        <td><span class="badge-rol-tag badge-rol-${escHtmlAdmin(rol)}">${escHtmlAdmin(capitalizarAdmin(rol))}</span></td>
                        <td>${badgeEstado}</td>
                        <td class="admin-td-right"><div class="acciones-btn-group">${btns}</div></td>
                    </tr>`;
            }).join('');
        }

        actualizarControlesPaginacionAdmin(
            'usuarios',
            lista.length,
            data.total ?? lista.length,
            data.pagina ?? paginaUsuariosAdminActual,
            data.paginas ?? 1,
            'usuarios',
            'usuario'
        );
    } catch (err) {
        if (window.Lay8UI) {
            window.Lay8UI.toast('Error al filtrar usuarios: ' + err.message, 'error');
        }
    }
}

let timerFiltroBibliotecaAdmin = null;
let paginaBibliotecaAdminActual = 1;

function filtrarTablaBiblioteca() {
    clearTimeout(timerFiltroBibliotecaAdmin);
    timerFiltroBibliotecaAdmin = setTimeout(() => {
        cargarBibliotecaAdmin(1);
    }, 260);
}

function cambiarPaginaBibliotecaAdmin(delta) {
    const bar = document.getElementById('admin-biblioteca-paginacion');
    const pagActual = Number(bar?.dataset?.pagina || paginaBibliotecaAdminActual || 1);
    const totalPags = Number(bar?.dataset?.paginas || 1);
    const destino = Math.min(Math.max(1, pagActual + delta), Math.max(1, totalPags));
    if (destino !== pagActual) {
        cargarBibliotecaAdmin(destino);
    }
}

async function cargarBibliotecaAdmin(pagina = 1) {
    const tbody = document.getElementById('tbody-biblioteca-admin');
    const input = document.getElementById('filtro-biblioteca-admin-input');
    if (!tbody) return;

    paginaBibliotecaAdminActual = Math.max(1, Number(pagina) || 1);
    const buscar = (input?.value || '').trim();
    const qs = new URLSearchParams({
        pagina: String(paginaBibliotecaAdminActual),
        limite: '20',
        sin_huerfanos: '1',
    });
    if (buscar !== '') qs.set('buscar', buscar);

    try {
        const data = await adminFetchJson(BASE_URL + 'api/admin/biblioteca/aportes?' + qs.toString());
        const lista = Array.isArray(data.aportes) ? data.aportes : [];
        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="admin-celda-vacia">No se encontraron recursos con ese criterio.</td></tr>';
        } else {
            tbody.innerHTML = lista.map((ap) => {
                const autor = ap.nombre_usuario || ap.autor_usuario || '';
                const autorHtml = autor
                    ? `@${escHtmlAdmin(autor)}`
                    : '<span class="admin-texto-desvinculado">Desvinculado</span>';
                const tamanoKb = (Number(ap.tamano_bytes || 0) / 1024).toFixed(1);
                const fecha = formatearFechaAdmin(ap.fecha_subida || ap.created_at);
                return `
                    <tr>
                        <td>
                            <strong>${escHtmlAdmin(ap.titulo)}</strong>
                            <div class="admin-subtexto-muted">${escHtmlAdmin(ap.nombre_original)}</div>
                        </td>
                        <td><span class="badge-categoria-tag">${escHtmlAdmin(capitalizarAdmin(ap.categoria))}</span></td>
                        <td>${autorHtml}</td>
                        <td>${tamanoKb} KB</td>
                        <td>${fecha}</td>
                        <td class="admin-td-right">
                            <div class="acciones-btn-group">
                                <a href="${BASE_URL}biblioteca/descargar/${Number(ap.id)}" class="btn-accion-admin btn-descargar" target="_blank" title="Descargar comprobante">
                                    <i class="bx bx-download"></i>
                                </a>
                                <button type="button" class="btn-accion-admin btn-eliminar" data-accion="eliminar-aporte" data-id="${Number(ap.id)}" data-titulo="${escHtmlAdmin(ap.titulo)}">
                                    <i class="bx bx-trash"></i> Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        actualizarControlesPaginacionAdmin(
            'biblioteca',
            lista.length,
            data.total ?? lista.length,
            data.pagina ?? paginaBibliotecaAdminActual,
            data.paginas ?? 1,
            'recursos',
            'recurso'
        );
    } catch (err) {
        if (window.Lay8UI) {
            window.Lay8UI.toast('Error al filtrar recursos: ' + err.message, 'error');
        }
    }
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-accion]');
    if (!btn) return;
    const accion = btn.getAttribute('data-accion');
    const id = Number(btn.getAttribute('data-id'));
    if (!id) return;

    if (accion === 'desbanear') {
        desbanearUsuario(id, btn.getAttribute('data-usuario') || '');
    } else if (accion === 'banear') {
        abrirModalBanear(id, btn.getAttribute('data-usuario') || '');
    } else if (accion === 'eliminar-usuario') {
        eliminarUsuario(id, btn.getAttribute('data-usuario') || '');
    } else if (accion === 'eliminar-aporte') {
        abrirModalEliminarAporte(id, btn.getAttribute('data-titulo') || '');
    }
});

function actualizarNombreHorario(input) {
    const contenedor = document.getElementById('nombre-archivo-horario');
    if (input.files && input.files[0]) {
        contenedor.innerHTML = '<i class="bx bxs-file-pdf" style="color:#ef4444;"></i> ' + window.Lay8UI.escapeHtml(input.files[0].name);
    } else {
        contenedor.innerHTML = '';
    }
}

/**
 * Ejecuta peticiones AJAX administrativas delegando en Lay8UI.apiFetch.
 */
async function adminFetchJson(url, options = {}) {
    if (window.Lay8UI) {
        return window.Lay8UI.apiFetch(url, options);
    }
    const resp = await fetch(url, options);
    return resp.json();
}

// aceptar o rechazar el registro del docente
let docenteSeleccionadoId = null;

async function aprobarProfesor(id, username) {
    const ok = await window.Lay8UI.confirm('¿Confirmas aprobar al usuario @' + username + ' como Profesor oficial de la UMC?', {
        title: 'Aprobar solicitud docente',
        confirmText: 'Aprobar'
    });
    if (!ok) return;

    try {
        const data = await adminFetchJson(BASE_URL + 'api/admin/aprobar-profesor/' + id, { method: 'POST' });
        window.Lay8UI.toast(data.mensaje, 'exito');
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        window.Lay8UI.toast('Error: ' + err.message, 'error');
    }
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
        window.Lay8UI.toast('Por favor ingresa un motivo para el rechazo.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('motivo', motivo);

    adminFetchJson(BASE_URL + 'api/admin/denegar-profesor/' + docenteSeleccionadoId, {
        method: 'POST',
        body: formData
    })
        .then(data => {
            window.Lay8UI.toast(data.mensaje, 'exito');
            setTimeout(() => location.reload(), 700);
        })
        .catch(err => window.Lay8UI.toast('Error: ' + err.message, 'error'));
}

// suspension, reactivacion y eliminacion de cuentas
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
        window.Lay8UI.toast('Por favor ingresa un motivo para el baneo.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('motivo', motivo);

    adminFetchJson(BASE_URL + 'api/admin/usuarios/banear/' + usuarioSeleccionadoId, {
        method: 'POST',
        body: formData
    })
        .then(data => {
            window.Lay8UI.toast(data.mensaje, 'exito');
            setTimeout(() => location.reload(), 700);
        })
        .catch(err => window.Lay8UI.toast('Error: ' + err.message, 'error'));
}

async function desbanearUsuario(id, username) {
    const ok = await window.Lay8UI.confirm('¿Reactivar la cuenta de @' + username + '?', {
        title: 'Reactivar cuenta',
        confirmText: 'Reactivar'
    });
    if (!ok) return;

    try {
        const data = await adminFetchJson(BASE_URL + 'api/admin/usuarios/desbanear/' + id, { method: 'POST' });
        window.Lay8UI.toast(data.mensaje, 'exito');
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        window.Lay8UI.toast('Error: ' + err.message, 'error');
    }
}

async function eliminarUsuario(id, username) {
    const ok = await window.Lay8UI.confirm(
        '¿Estás seguro de eliminar permanentemente a @' + username + '? Esta acción no se puede deshacer.',
        { title: 'Eliminar cuenta', confirmText: 'Eliminar cuenta', danger: true }
    );
    if (!ok) return;

    try {
        const data = await adminFetchJson(BASE_URL + 'api/admin/usuarios/eliminar/' + id, { method: 'POST' });
        window.Lay8UI.toast(data.mensaje, 'exito');
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        window.Lay8UI.toast('Error: ' + err.message, 'error');
    }
}

// moderacion de aportes en biblioteca
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

    adminFetchJson(BASE_URL + 'api/admin/biblioteca/eliminar/' + aporteSeleccionadoId, {
        method: 'POST',
        body: formData
    })
        .then(data => {
            window.Lay8UI.toast(data.mensaje, 'exito');
            setTimeout(() => location.reload(), 700);
        })
        .catch(err => window.Lay8UI.toast('Error: ' + err.message, 'error'));
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
        labelFile.innerText = 'Seleccionar JSON / PDF';
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
        window.Lay8UI.toast('Por favor selecciona un archivo .json (o .pdf en local) primero.', 'warning');
        return;
    }

    const archivo = input.files[0];
    const esPdf = archivo.name.toLowerCase().endsWith('.pdf');
    const formData = new FormData();
    formData.append('horario_archivo', archivo);
    formData.append('carrera', codCarrera);

    btnSubir.disabled = true;
    btnSubir.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Sincronizando...';

    if (banner) {
        banner.style.display = 'block';
        banner.style.background = '#eff6ff';
        banner.style.color = '#1e40af';
        banner.style.border = '1px solid #bfdbfe';
        banner.innerHTML = esPdf
            ? '<i class="bx bx-loader-alt bx-spin"></i> Procesando PDF local de <strong>' + escHtmlAdmin(codCarrera) + '</strong>... Esto puede tomar unos segundos.'
            : '<i class="bx bx-loader-alt bx-spin"></i> Importando bloques horarios JSON para <strong>' + escHtmlAdmin(codCarrera) + '</strong>...';
    }

    try {
        const data = await adminFetchJson((window.BASE_URL || '/') + 'api/admin/horario/actualizar', {
            method: 'POST',
            body: formData
        });

        if (banner) {
            banner.style.background = '#ecfdf5';
            banner.style.color = '#065f46';
            banner.style.border = '1px solid #a7f3d0';
            banner.innerHTML = '<i class="bx bx-check-circle"></i> ¡Éxito! Se sincronizaron <strong>' + Number(data.clases_cargadas || 0) + '</strong> bloques de clases para <strong>' + escHtmlAdmin(codCarrera) + '</strong> en el mapa.';
        }

        const card = document.getElementById('card-horario-' + codCarrera);
        if (card) {
            const metaStatus = card.querySelector('.carrera-meta-status');
            if (metaStatus) {
                metaStatus.innerHTML = '<span class="badge-status-ok" style="color: #059669; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="bx bxs-check-circle"></i> <span id="conteo-clases-' + escHtmlAdmin(codCarrera) + '">' + Number(data.clases_cargadas || 0) + '</span> bloques sincronizados</span><span style="color: #94a3b8; margin: 0 4px;">•</span><span style="color: #64748b;" id="fecha-sync-' + escHtmlAdmin(codCarrera) + '">' + escHtmlAdmin(data.fecha || '') + '</span>';
            }
        }

        btnSubir.innerHTML = '<i class="bx bx-check"></i> Sincronizado';
        setTimeout(() => {
            btnSubir.innerHTML = '<i class="bx bx-sync"></i> Procesar';
            btnSubir.disabled = true;
            btnSubir.style.opacity = '0.6';
            const labelFile = document.getElementById('label-file-' + codCarrera);
            if (labelFile) labelFile.innerText = 'Seleccionar JSON / PDF';
            input.value = '';
        }, 3000);
    } catch (err) {
        if (banner) {
            banner.style.background = '#fef2f2';
            banner.style.color = '#991b1b';
            banner.style.border = '1px solid #fecaca';
            banner.innerHTML = '<i class="bx bx-error-circle"></i> Error al sincronizar: ' + escHtmlAdmin(err.message);
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
    const esc = window.Lay8UI.escapeHtml;
    const cont = document.getElementById('lista-secciones-asignadas');
    cont.innerHTML = '<div style="text-align: center; padding: 15px; color: #64748b;"><i class="bx bx-loader-alt bx-spin"></i> Cargando secciones...</div>';
    try {
        const data = await adminFetchJson((window.BASE_URL || '/') + 'api/admin/profesor-secciones/' + parseInt(profId, 10));
        if (data.secciones && data.secciones.length > 0) {
            cont.innerHTML = data.secciones.map(s => `
                <div class="item-seccion-asignada">
                    <div>
                        <strong>${esc(s.materia)}</strong> <span style="font-size: 0.82rem; color: #2563eb; font-weight: 600;">[${esc(s.codigo_materia)} - Secc. ${esc(s.codigo_seccion)}]</span>
                        <div style="font-size: 0.78rem; color: #64748b;">Carrera: ${esc(s.carrera || 'UMC')}</div>
                    </div>
                    <button type="button" class="btn-accion-admin btn-rechazar" style="padding: 5px 12px; font-size: 0.8rem;" onclick="desasignarSeccion(${parseInt(s.id, 10)})">
                        <i class="bx bx-trash"></i> Quitar
                    </button>
                </div>
            `).join('');
        } else {
            cont.innerHTML = '<p style="font-size: 0.85rem; color: #94a3b8; margin: 0; padding: 10px; text-align: center; background: var(--lay8-bg-subtle, #f8fafc); border-radius: 8px;">Este docente no tiene secciones asignadas actualmente.</p>';
        }
    } catch (e) {
        cont.innerHTML = '<p style="color: #ef4444; font-size: 0.85rem;">Error al cargar las secciones: ' + esc(e.message) + '</p>';
    }
}

let timerBusquedaSecciones = null;
function buscarSeccionesDisponibles(q) {
    const esc = window.Lay8UI.escapeHtml;
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
            const data = await adminFetchJson((window.BASE_URL || '/') + 'api/admin/buscar-secciones?q=' + encodeURIComponent(q));
            if (data.secciones && data.secciones.length > 0) {
                resCont.innerHTML = data.secciones.map(s => {
                    const docenteActual = s.prof_nombre ? `${esc(s.prof_nombre)} ${esc(s.prof_apellido || '')}` : '<span style="color:#059669; font-weight:600;">Disponible (Sin docente)</span>';
                    return `
                        <div class="item-seccion-busqueda">
                            <div>
                                <strong>${esc(s.materia)}</strong> (${esc(s.codigo_materia)} - Secc. ${esc(s.codigo_seccion)})
                                <div style="font-size: 0.76rem; color: #64748b;">Docente actual: <em>${docenteActual}</em></div>
                            </div>
                            <button type="button" class="btn-accion-admin btn-aprobar" style="padding: 5px 12px; font-size: 0.8rem;" onclick="asignarSeccion(${parseInt(s.id, 10)})">
                                <i class="bx bx-plus"></i> Asignar
                            </button>
                        </div>
                    `;
                }).join('');
            } else {
                resCont.innerHTML = '<p style="font-size: 0.82rem; color: #94a3b8; margin: 0;">No se encontraron secciones con ese criterio.</p>';
            }
        } catch (e) {
            resCont.innerHTML = '<p style="color: #ef4444; font-size: 0.82rem;">Error al buscar secciones: ' + esc(e.message) + '</p>';
        }
    }, 280);
}

async function asignarSeccion(seccionId) {
    if (!profIdActualAsignar) return;
    const formData = new FormData();
    formData.append('profesor_id', profIdActualAsignar);
    formData.append('seccion_id', seccionId);

    try {
        await adminFetchJson((window.BASE_URL || '/') + 'api/admin/asignar-seccion', {
            method: 'POST',
            body: formData
        });
        cargarSeccionesDocente(profIdActualAsignar);
        buscarSeccionesDisponibles(document.getElementById('input-buscar-seccion').value);
    } catch (e) {
        window.Lay8UI.toast('Error al asignar sección: ' + e.message, 'error');
    }
}

async function desasignarSeccion(seccionId) {
    const ok = await window.Lay8UI.confirm('¿Deseas quitar esta sección del docente?', {
        title: 'Quitar sección',
        confirmText: 'Quitar',
        danger: true
    });
    if (!ok) return;
    const formData = new FormData();
    formData.append('seccion_id', seccionId);

    try {
        await adminFetchJson((window.BASE_URL || '/') + 'api/admin/desasignar-seccion', {
            method: 'POST',
            body: formData
        });
        cargarSeccionesDocente(profIdActualAsignar);
        buscarSeccionesDisponibles(document.getElementById('input-buscar-seccion').value);
    } catch (e) {
        window.Lay8UI.toast('Error al desasignar sección: ' + e.message, 'error');
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
