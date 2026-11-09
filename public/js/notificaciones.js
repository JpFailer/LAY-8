// notificaciones en vivo y solicitudes de profes (polling)

const BASE_URL_NOTIF = (window.BASE_URL || '/');

    const CSRF_TOKEN_NOTIF = (window.CSRF_TOKEN || 'csrf_test_name');

    const CSRF_HEADER_NOTIF = (window.CSRF_HEADER || 'X-CSRF-TOKEN');

    function getNotifHeaders() {

      const headers = { 'X-Requested-With': 'XMLHttpRequest' };

      const csrfInput = document.querySelector('input[name="' + CSRF_TOKEN_NOTIF + '"]');

      if (csrfInput && csrfInput.value) {

        headers[CSRF_HEADER_NOTIF] = csrfInput.value;

      }

      return headers;

    }

    function confirmarCerrarSesion(e) {

      if (e) e.preventDefault();

      const m = document.getElementById('modal-confirmar-logout');

      if (m) m.style.display = 'flex';

    }

    function cerrarModalLogout() {

      const m = document.getElementById('modal-confirmar-logout');

      if (m) m.style.display = 'none';

    }

    function toggleDropdownNotificaciones(e) {

      if (e) e.stopPropagation();

      const box = document.getElementById('dropdown-notif-box');

      if (!box) return;

      const isHidden = (box.style.display === 'none' || box.style.display === '');

      box.style.display = isHidden ? 'flex' : 'none';

      // cerrar menu si abren la campanita

      const menuPerfil = document.getElementById('dropdown-perfil');

      if (menuPerfil) menuPerfil.classList.remove('mostrar');

      if (isHidden) {

        cargarNotificacionesTop();

      }

    }

    function cargarNotificacionesTop() {

      fetch(BASE_URL_NOTIF + 'api/notificaciones')

        .then(r => r.json())

        .then(data => {

          if (!data || !data.success) return;

          actualizarBadgeConteo(data.no_leidas || 0);

          const container = document.getElementById('notif-items-lista');

          if (!container) return;

          if (!data.notificaciones || data.notificaciones.length === 0) {

            container.innerHTML = `

              <div class="notif-vacio">

                <i class="bx bx-check-double"></i>

                <p>No tienes notificaciones pendientes.</p>

              </div>`;

            return;

          }

          let html = '';

          data.notificaciones.forEach(n => {

            let iconClass = 'notif-icon-general';

            let iconBx = 'bx-bell';

            if (n.tipo === 'like') {

              iconClass = 'notif-icon-like';

              iconBx = 'bxs-heart';

            } else if (n.tipo === 'comentario' || n.tipo === 'respuesta') {

              iconClass = 'notif-icon-comentario';

              iconBx = 'bx-comment-detail';

            } else if (n.tipo === 'clase_proxima' || n.tipo === 'clase_hoy') {

              iconClass = 'notif-icon-clase';

              iconBx = 'bx-time-five';

            }

            const noLeidaClass = (parseInt(n.leido) === 0) ? 'no-leida' : '';

            const enlace = n.enlace_accion ? (BASE_URL_NOTIF + n.enlace_accion) : '#';

            html += `

              <a href="${enlace}" class="notif-item-row ${noLeidaClass}" onclick="marcarNotifLeida(${n.id})">

                <div class="notif-item-icon ${iconClass}">

                  <i class="bx ${iconBx}"></i>

                </div>

                <div class="notif-item-content">

                  <span class="notif-item-title">${n.titulo || 'Notificación'}</span>

                  <span class="notif-item-msg">${n.mensaje || ''}</span>

                  <span class="notif-item-time">${n.created_at || ''}</span>

                </div>

              </a>`;

          });

          container.innerHTML = html;

        })

        .catch(err => console.error('Error cargando notificaciones:', err));

    }

    function actualizarBadgeConteo(conteo) {

      const badge = document.getElementById('badge-notif-count');

      if (!badge) return;

      if (conteo > 0) {

        badge.innerText = conteo > 99 ? '99+' : conteo;

        badge.style.display = 'flex';

      } else {

        badge.style.display = 'none';

      }

    }

    function marcarNotifLeida(id) {

      fetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-leida/' + id, { 

        method: 'POST',

        headers: getNotifHeaders()

      })

        .then(() => {

          cargarConteoNotificaciones();

        })

        .catch(() => {});

    }

    function marcarTodasNotificacionesLeidas(e) {

      if (e) e.stopPropagation();

      fetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-todas-leidas', { 

        method: 'POST',

        headers: getNotifHeaders()

      })

        .then(r => r.json())

        .then(() => {

          actualizarBadgeConteo(0);

          cargarNotificacionesTop();

        })

        .catch(err => console.error(err));

    }

    function cargarConteoNotificaciones() {

      fetch(BASE_URL_NOTIF + 'api/notificaciones/conteo')

        .then(r => r.json())

        .then(data => {

          if (data && typeof data.no_leidas !== 'undefined') {

            actualizarBadgeConteo(data.no_leidas);

          }

        })

        .catch(() => {});

    }

    // clic afuera pa cerrar

    document.addEventListener('click', function(e) {

      const box = document.getElementById('dropdown-notif-box');

      const btn = document.getElementById('btn-campana-notif');

      if (box && btn && !box.contains(e.target) && !btn.contains(e.target)) {

        box.style.display = 'none';

      }

    });

    document.addEventListener('DOMContentLoaded', function() {

      cargarConteoNotificaciones();

      setInterval(cargarConteoNotificaciones, 30000); // preguntar al server cada 30 seg a ver si hay algo nuevo

      if (window.ES_ADMIN) {

        cargarSolicitudesDocentesTop();

        setInterval(cargarSolicitudesDocentesTop, 30000); // chequear si algun profe nuevo envio constancia

      }

    });

    if (window.ES_ADMIN) {

    let docenteSeleccionadoRechazoId = null;

    function cargarSolicitudesDocentesTop() {

      fetch((window.BASE_URL || '/') + 'api/admin/solicitudes-profesores', {

        headers: { 'X-Requested-With': 'XMLHttpRequest' }

      })

      .then(r => r.json())

      .then(data => {

        if (!data || !data.success) return;

        const total = data.total || 0;

        const badge = document.getElementById('badge-docentes-top-count');

        if (badge) {

          badge.textContent = total;

          badge.style.display = total > 0 ? 'inline-block' : 'none';

        }

        renderizarSolicitudesDocentesModal(data.solicitudes || []);

      })

      .catch(() => {});

    }

    function renderizarSolicitudesDocentesModal(solicitudes) {

      const cont = document.getElementById('contenedor-lista-solicitudes-top');

      if (!cont) return;

      if (!solicitudes || solicitudes.length === 0) {

        cont.innerHTML = `

          <div style="text-align: center; padding: 35px 20px; color: #64748b;">

            <i class="bx bx-check-double" style="font-size: 2.8rem; color: #10b981; margin-bottom: 8px;"></i>

            <h4 style="margin: 0 0 4px 0; color: #0f172a; font-size: 1.1rem;">¡Al día con las validaciones!</h4>

            <p style="margin: 0; font-size: 0.88rem;">No hay profesores pendientes de aprobación en este momento.</p>

          </div>

        `;

        return;

      }

      let html = '<div style="display: flex; flex-direction: column; gap: 14px;">';

      solicitudes.forEach(s => {

        const nombreCompleto = (s.primer_nombre + ' ' + (s.segundo_nombre ? s.segundo_nombre + ' ' : '') + s.apellidos).trim();

        const cedula = s.cedula || 'No registrada';

        const carnetUrl = s.ruta_carnet ? ((window.BASE_URL || '/') + s.ruta_carnet) : null;

        html += `

          <div class="card-solicitud-docente-item" style="border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">

            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">

              <div>

                <div style="font-weight: 700; color: #0f172a; font-size: 0.98rem; display: flex; align-items: center; gap: 6px;">

                  <i class="bx bx-user" style="color: #2563eb;"></i> ${nombreCompleto}

                  <span style="font-size: 0.8rem; color: #64748b; font-weight: normal;">(@${s.nombre_usuario})</span>

                </div>

                <div style="font-size: 0.84rem; color: #334155; margin-top: 4px; display: flex; gap: 14px; flex-wrap: wrap;">

                  <span><strong>C.I:</strong> <code style="background: #eff6ff; color: #1e40af; padding: 2px 6px; border-radius: 4px;">${cedula}</code></span>

                  <span><strong>Correo:</strong> ${s.correo}</span>

                </div>

              </div>

              

              <div>

                ${carnetUrl ? `

                  <a href="${carnetUrl}" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 5px 10px; border-radius: 6px; font-size: 0.82rem; font-weight: 600; text-decoration: none;">

                    <i class="bx bx-file-blank" style="color: #dc2626; font-size: 1.1rem;"></i> Ver Comprobante

                  </a>

                ` : `<span style="font-size: 0.8rem; color: #94a3b8;">Sin comprobante adjunto</span>`}

              </div>

            </div>

            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">

              <button type="button" onclick="aprobarDocenteDesdeModal(${s.id}, '${nombreCompleto.replace(/'/g, "\'")}')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.83rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">

                <i class="bx bx-check"></i> Aprobar Profesor

              </button>

              <button type="button" onclick="abrirModalRechazoDocente(${s.id}, '${nombreCompleto.replace(/'/g, "\'")}')" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.83rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">

                <i class="bx bx-x"></i> Negar Solicitud

              </button>

            </div>

          </div>

        `;

      });

      html += '</div>';

      cont.innerHTML = html;

    }

    function abrirModalSolicitudesDocentesTop() {

      const modal = document.getElementById('modal-solicitudes-docentes-top');

      if (modal) {

        modal.style.display = 'flex';

        cargarSolicitudesDocentesTop();

      }

    }

    function cerrarModalSolicitudesDocentesTop() {

      const modal = document.getElementById('modal-solicitudes-docentes-top');

      if (modal) modal.style.display = 'none';

    }

    function aprobarDocenteDesdeModal(id, nombre) {

      if (!confirm('¿Confirmas aprobar al docente ' + nombre + ' como Profesor Oficial de la UMC?')) return;

      fetch((window.BASE_URL || '/') + 'api/admin/aprobar-profesor/' + id, {

        method: 'POST',

        headers: { 'X-Requested-With': 'XMLHttpRequest' }

      })

      .then(r => r.json())

      .then(data => {

        if (data.success) {

          alert(data.mensaje || 'Profesor aprobado exitosamente.');

          cargarSolicitudesDocentesTop();

        } else {

          alert(data.error || 'No se pudo aprobar la solicitud.');

        }

      })

      .catch(() => alert('Error de conexión al procesar la aprobación.'));

    }

    function abrirModalRechazoDocente(id, nombre) {

      docenteSeleccionadoRechazoId = id;

      const txt = document.getElementById('texto-docente-a-rechazar');

      if (txt) txt.textContent = 'Solicitante: ' + nombre;

      const input = document.getElementById('input-motivo-rechazo');

      if (input) input.value = '';

      const m = document.getElementById('modal-motivo-rechazo-docente');

      if (m) m.style.display = 'flex';

    }

    function cerrarModalRechazoDocente() {

      const m = document.getElementById('modal-motivo-rechazo-docente');

      if (m) m.style.display = 'none';

    }

    function confirmarRechazoDocenteDesdeModal() {

      if (!docenteSeleccionadoRechazoId) return;

      const motivo = document.getElementById('input-motivo-rechazo').value.trim();

      const formData = new FormData();

      if (motivo) formData.append('motivo', motivo);

      fetch((window.BASE_URL || '/') + 'api/admin/denegar-profesor/' + docenteSeleccionadoRechazoId, {

        method: 'POST',

        headers: { 'X-Requested-With': 'XMLHttpRequest' },

        body: formData

      })

      .then(r => r.json())

      .then(data => {

        cerrarModalRechazoDocente();

        if (data.success) {

          alert('Solicitud rechazada y notificada al usuario.');

          cargarSolicitudesDocentesTop();

        } else {

          alert(data.error || 'No se pudo rechazar la solicitud.');

        }

      })

      .catch(() => alert('Error al enviar rechazo al servidor.'));

    }

    }
