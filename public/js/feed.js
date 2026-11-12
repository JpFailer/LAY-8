/**
 * Interacciones AJAX en tiempo real del Tablón / Feed Comunitario UMC.
 */
(function (global) {
    'use strict';

    function baseUrl() {
        return global.BASE_URL || '/';
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function buildCheckBadge(rol) {
        if (rol === 'profesor') {
            return '<span class="check-verificado-profesor" title="Profesor Verificado" style="color: #10b981; font-size: 1.05rem; display: inline-flex; align-items: center; margin-left: 2px;"><i class="bx bxs-check-circle"></i></span>';
        }
        if (rol === 'admin' || rol === 'administrador') {
            return '<span class="check-verificado-admin" title="Administrador Verificado" style="color: #2563eb; font-size: 1.05rem; display: inline-flex; align-items: center; margin-left: 2px;"><i class="bx bxs-check-circle"></i></span>';
        }
        return '';
    }

    async function publicarNoticiaFeed(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit-comunicado');
        const form = document.getElementById('formPublicarNoticia');
        if (!form || !btn) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Publicando...';

        try {
            await global.Lay8UI.apiFetch(baseUrl() + 'api/feed/publicar', {
                method: 'POST',
                body: new FormData(form)
            });
            global.location.reload();
        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-paper-plane"></i> Publicar';
            global.Lay8UI.toast(err.message || 'Error al publicar el comunicado.', 'error');
        }
    }

    async function toggleLikeNoticia(noticiaId) {
        const btn = document.getElementById('btn-like-' + noticiaId);
        const conteoEl = document.getElementById('conteo-likes-' + noticiaId);
        if (!btn) return;

        try {
            const res = await global.Lay8UI.apiFetch(baseUrl() + 'api/feed/toggle-like/' + noticiaId, { method: 'POST' });
            const icon = btn.querySelector('i');
            if (res.liked) {
                btn.classList.add('dio-like');
                if (icon) icon.className = 'bx bxs-heart';
            } else {
                btn.classList.remove('dio-like');
                if (icon) icon.className = 'bx bx-heart';
            }
            if (conteoEl) {
                conteoEl.innerText = res.total_likes;
            }
        } catch (err) {
            console.error(err);
        }
    }

    function toggleHilosComentarios(noticiaId) {
        const seccion = document.getElementById('hilos-comentarios-' + noticiaId);
        const btn = document.getElementById('btn-toggle-comentarios-' + noticiaId);
        if (!seccion) return;

        const estaOculto = (seccion.style.display === 'none' || seccion.style.display === '');
        seccion.style.display = estaOculto ? 'flex' : 'none';
        if (btn) {
            btn.classList.toggle('abierto', estaOculto);
        }
    }

    async function enviarComentarioPrincipal(e, noticiaId) {
        e.preventDefault();
        const input = document.getElementById('input-comentario-' + noticiaId);
        if (!input || !input.value.trim()) return;

        const texto = input.value.trim();
        input.value = '';

        const formData = new FormData();
        formData.append('contenido', texto);

        try {
            const res = await global.Lay8UI.apiFetch(baseUrl() + 'api/feed/comentar/' + noticiaId, {
                method: 'POST',
                body: formData
            });

            if (res.comentario) {
                const contenedor = document.getElementById('contenedor-hilos-' + noticiaId);
                const conteoEl = document.getElementById('conteo-comentarios-' + noticiaId);
                if (conteoEl) conteoEl.innerText = res.total_comentarios;

                if (contenedor) {
                    const msgVacio = contenedor.querySelector('.msg-sin-comentarios');
                    if (msgVacio) msgVacio.remove();

                    const c = res.comentario;
                    const avatarUrl = c.foto_perfil ? (baseUrl() + c.foto_perfil) : (baseUrl() + 'img/usuario.png');
                    const badgeRolClass = (c.rol === 'profesor') ? 'badge-docente' : (['admin', 'administrador'].includes(c.rol) ? 'badge-admin' : 'badge-estudiante');
                    const badgeRolTexto = (c.rol === 'profesor') ? 'Docente' : (['admin', 'administrador'].includes(c.rol) ? 'Admin' : 'Estudiante');
                    const nombreAutor = escapeHtml((c.primer_nombre || '') + ' ' + (c.apellidos || ''));
                    const contenidoSeguro = escapeHtml(c.contenido || '');

                    const nuevoItemHtml = `
                        <div class="comentario-item-wrapper" id="comentario-item-${parseInt(c.id, 10)}">
                          <div class="comentario-burbuja">
                            <div class="comentario-header-row">
                              <img src="${escapeHtml(avatarUrl)}" class="avatar-comentario" alt="Avatar">
                              <span class="nombre-comentarista">${nombreAutor}</span>
                              ${buildCheckBadge(c.rol)}
                              <span class="rol-badge-comentario ${badgeRolClass}">${badgeRolTexto}</span>
                              <span class="tiempo-comentario">Justo ahora</span>
                            </div>
                            <p class="texto-comentario-p">${contenidoSeguro}</p>
                            <button type="button" class="btn-responder-comentario" onclick="abrirCajaRespuestaInline(${parseInt(noticiaId, 10)}, ${parseInt(c.id, 10)})">
                              <i class="bx bx-reply"></i> Responder
                            </button>
                            <div class="caja-respuesta-inline" id="caja-reply-${parseInt(c.id, 10)}" style="display: none;">
                              <input type="text" class="input-respuesta-inline" id="input-reply-${parseInt(c.id, 10)}" placeholder="Escribe tu respuesta...">
                              <button type="button" class="btn-enviar-respuesta-inline" onclick="enviarRespuestaInline(${parseInt(noticiaId, 10)}, ${parseInt(c.id, 10)})">Responder</button>
                            </div>
                          </div>
                          <div class="hilo-respuestas-anidadas" id="respuestas-padre-${parseInt(c.id, 10)}"></div>
                        </div>`;

                    contenedor.insertAdjacentHTML('beforeend', nuevoItemHtml);
                }
            }
        } catch (err) {
            global.Lay8UI.toast(err.message || 'Error al enviar comentario.', 'error');
        }
    }

    function abrirCajaRespuestaInline(noticiaId, comentarioId) {
        const caja = document.getElementById('caja-reply-' + comentarioId);
        if (!caja) return;
        const isHidden = (caja.style.display === 'none' || caja.style.display === '');
        caja.style.display = isHidden ? 'flex' : 'none';
        if (isHidden) {
            const input = document.getElementById('input-reply-' + comentarioId);
            if (input) input.focus();
        }
    }

    async function enviarRespuestaInline(noticiaId, parentId) {
        const input = document.getElementById('input-reply-' + parentId);
        if (!input || !input.value.trim()) return;

        const texto = input.value.trim();
        input.value = '';

        const caja = document.getElementById('caja-reply-' + parentId);
        if (caja) caja.style.display = 'none';

        const formData = new FormData();
        formData.append('contenido', texto);
        formData.append('parent_id', parentId);

        try {
            const res = await global.Lay8UI.apiFetch(baseUrl() + 'api/feed/comentar/' + noticiaId, {
                method: 'POST',
                body: formData
            });

            if (res.comentario) {
                const contenedorRespuestas = document.getElementById('respuestas-padre-' + parentId);
                const conteoEl = document.getElementById('conteo-comentarios-' + noticiaId);
                if (conteoEl) conteoEl.innerText = res.total_comentarios;

                if (contenedorRespuestas) {
                    const c = res.comentario;
                    const avatarUrl = c.foto_perfil ? (baseUrl() + c.foto_perfil) : (baseUrl() + 'img/usuario.png');
                    const badgeRolClass = (c.rol === 'profesor') ? 'badge-docente' : (['admin', 'administrador'].includes(c.rol) ? 'badge-admin' : 'badge-estudiante');
                    const badgeRolTexto = (c.rol === 'profesor') ? 'Docente' : (['admin', 'administrador'].includes(c.rol) ? 'Admin' : 'Estudiante');
                    const nombreAutor = escapeHtml((c.primer_nombre || '') + ' ' + (c.apellidos || ''));
                    const contenidoSeguro = escapeHtml(c.contenido || '');

                    const respuestaHtml = `
                        <div class="comentario-burbuja" id="comentario-item-${parseInt(c.id, 10)}">
                          <div class="comentario-header-row">
                            <img src="${escapeHtml(avatarUrl)}" class="avatar-comentario" alt="Avatar">
                            <span class="nombre-comentarista">${nombreAutor}</span>
                            ${buildCheckBadge(c.rol)}
                            <span class="rol-badge-comentario ${badgeRolClass}">${badgeRolTexto}</span>
                            <span class="tiempo-comentario">Justo ahora</span>
                          </div>
                          <p class="texto-comentario-p">${contenidoSeguro}</p>
                        </div>`;

                    contenedorRespuestas.insertAdjacentHTML('beforeend', respuestaHtml);
                }
            }
        } catch (err) {
            global.Lay8UI.toast(err.message || 'Error al responder.', 'error');
        }
    }

    async function eliminarNoticiaPost(noticiaId) {
        const ok = await global.Lay8UI.confirm(
            '¿Estás seguro de que deseas eliminar este comunicado del tablón universitario?',
            { title: 'Eliminar comunicado', confirmText: 'Eliminar', danger: true }
        );
        if (!ok) return;

        try {
            await global.Lay8UI.apiFetch(baseUrl() + 'api/feed/eliminar/' + noticiaId, { method: 'POST' });
            const card = document.getElementById('noticia-' + noticiaId);
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 300);
            }
            global.Lay8UI.toast('Comunicado eliminado.', 'exito');
        } catch (err) {
            global.Lay8UI.toast(err.message || 'No se pudo eliminar.', 'error');
        }
    }

    global.publicarNoticiaFeed = publicarNoticiaFeed;
    global.toggleLikeNoticia = toggleLikeNoticia;
    global.toggleHilosComentarios = toggleHilosComentarios;
    global.enviarComentarioPrincipal = enviarComentarioPrincipal;
    global.abrirCajaRespuestaInline = abrirCajaRespuestaInline;
    global.enviarRespuestaInline = enviarRespuestaInline;
    global.eliminarNoticiaPost = eliminarNoticiaPost;
})(window);
