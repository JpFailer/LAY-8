// notificaciones en vivo (polling consciente de visibilidad)

const BASE_URL_NOTIF = (window.BASE_URL || '/');

function escaparHtmlNotif(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function limpiarTextoSinEmojisNotif(str) {
  const sinEmojis = String(str || '')
    .replace(/[\u{1F000}-\u{1FFFF}\u2600-\u27BF\uFE0F]/gu, '')
    .replace(/\s{2,}/g, ' ')
    .trim();
  return escaparHtmlNotif(sinEmojis);
}

function getNotifHeaders() {
  const csrf = window.Lay8UI ? window.Lay8UI.getCsrf() : null;
  const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
  if (csrf && csrf.value) {
    headers[csrf.headerName] = csrf.value;
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

  const menuPerfil = document.getElementById('dropdown-perfil');
  if (menuPerfil) menuPerfil.classList.remove('show');

  if (isHidden) {
    cargarNotificacionesTop();
  }
}

function cargarNotificacionesTop() {
  fetch(BASE_URL_NOTIF + 'api/notificaciones', { headers: getNotifHeaders() })
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

        const noLeidaClass = (parseInt(n.leido, 10) === 0) ? 'no-leida' : '';
        const enlace = n.enlace_accion ? (BASE_URL_NOTIF + n.enlace_accion) : '#';

        html += `
          <a href="${escaparHtmlNotif(enlace)}" class="notif-item-row ${noLeidaClass}" onclick="marcarNotifLeida(${parseInt(n.id, 10)})">
            <div class="notif-item-icon ${iconClass}">
              <i class="bx ${iconBx}"></i>
            </div>
            <div class="notif-item-content">
              <span class="notif-item-title">${limpiarTextoSinEmojisNotif(n.titulo || 'Notificación')}</span>
              <span class="notif-item-msg">${limpiarTextoSinEmojisNotif(n.mensaje || '')}</span>
              <span class="notif-item-time">${escaparHtmlNotif(n.created_at || '')}</span>
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
  const req = window.Lay8UI
    ? window.Lay8UI.apiFetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-leida/' + id, { method: 'POST' })
    : fetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-leida/' + id, { method: 'POST', headers: getNotifHeaders() });

  Promise.resolve(req)
    .then(() => cargarConteoNotificaciones())
    .catch(() => {});
}

function marcarTodasNotificacionesLeidas(e) {
  if (e) e.stopPropagation();
  const req = window.Lay8UI
    ? window.Lay8UI.apiFetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-todas-leidas', { method: 'POST' })
    : fetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-todas-leidas', { method: 'POST', headers: getNotifHeaders() }).then(r => r.json());

  Promise.resolve(req)
    .then(() => {
      actualizarBadgeConteo(0);
      cargarNotificacionesTop();
    })
    .catch(err => console.error(err));
}

function cargarConteoNotificaciones() {
  if (document.hidden) return;
  fetch(BASE_URL_NOTIF + 'api/notificaciones/conteo', { headers: getNotifHeaders() })
    .then(r => r.json())
    .then(data => {
      if (data && typeof data.no_leidas !== 'undefined') {
        actualizarBadgeConteo(data.no_leidas);
      }
    })
    .catch(() => {});
}

function cargarConteoSolicitudesDocentesTop() {
  if (!window.ES_ADMIN || document.hidden) return;
  fetch(BASE_URL_NOTIF + 'api/admin/solicitudes-profesores', { headers: getNotifHeaders() })
    .then(r => r.json())
    .then(data => {
      if (!data || !data.success) return;
      const total = data.total || 0;
      const badge = document.getElementById('badge-docentes-top-count');
      if (badge) {
        badge.textContent = total;
        badge.style.display = total > 0 ? 'inline-block' : 'none';
      }
    })
    .catch(() => {});
}

document.addEventListener('click', function (e) {
  const box = document.getElementById('dropdown-notif-box');
  const btn = document.getElementById('btn-campana-notif');
  if (box && btn && !box.contains(e.target) && !btn.contains(e.target)) {
    box.style.display = 'none';
  }
});

document.addEventListener('DOMContentLoaded', function () {
  cargarConteoNotificaciones();
  setInterval(cargarConteoNotificaciones, 30000);

  if (window.ES_ADMIN) {
    cargarConteoSolicitudesDocentesTop();
    setInterval(cargarConteoSolicitudesDocentesTop, 30000);
  }
});

document.addEventListener('visibilitychange', function () {
  if (!document.hidden) {
    cargarConteoNotificaciones();
    if (window.ES_ADMIN) {
      cargarConteoSolicitudesDocentesTop();
    }
  }
});
