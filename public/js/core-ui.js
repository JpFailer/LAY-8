/**
 * Núcleo UI & HTTP compartido de LAY-8:
 * - Cliente Fetch con inyección automática de CSRF y manejo uniforme de JSON
 * - Sistema de notificaciones Toast (sin bloquear el hilo con alert())
 * - Modal de confirmación asíncrono (reemplazo no bloqueante de confirm())
 */
(function (global) {
    'use strict';

    function getCsrf() {
        const tokenName = global.CSRF_TOKEN || 'csrf_test_name';
        const headerName = global.CSRF_HEADER || 'X-CSRF-TOKEN';
        const input = document.querySelector('#global-csrf-container input[name="' + tokenName + '"]')
            || document.querySelector('input[name="' + tokenName + '"]')
            || document.querySelector('#global-csrf-container input');

        return {
            tokenName: input ? input.name : tokenName,
            headerName: headerName,
            value: input ? input.value : '',
            input: input
        };
    }

    function syncCsrfHash(hash) {
        if (!hash) return;
        const csrf = getCsrf();
        if (csrf.input) csrf.input.value = hash;
        document.querySelectorAll('input[name="' + csrf.tokenName + '"]').forEach(el => {
            el.value = hash;
        });
    }

    async function apiFetch(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }, options.headers || {});

        let body = options.body !== undefined ? options.body : null;

        if (method !== 'GET' && method !== 'HEAD') {
            const csrf = getCsrf();
            if (csrf.value) {
                headers[csrf.headerName] = csrf.value;
                if (body instanceof FormData) {
                    if (!body.has(csrf.tokenName)) {
                        body.set(csrf.tokenName, csrf.value);
                    }
                } else if (body === null) {
                    const fd = new FormData();
                    fd.set(csrf.tokenName, csrf.value);
                    body = fd;
                }
            }
        }

        const resp = await fetch(url, { method, headers, body });
        const text = await resp.text();
        let data = null;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Respuesta inválida del servidor (HTTP ' + resp.status + ').');
        }

        if (data && data.csrf_hash) {
            syncCsrfHash(data.csrf_hash);
        }

        if (!resp.ok || !data || data.success === false) {
            let errMsg = (data && (data.error || data.mensaje)) || ('Error HTTP ' + resp.status);
            if (data && data.errores && typeof data.errores === 'object') {
                errMsg = Object.values(data.errores).join('\n');
            }
            throw new Error(errMsg);
        }

        return data;
    }

    function ensureToastContainer() {
        let cont = document.getElementById('lay8-toast-container');
        if (!cont && document.body) {
            cont = document.createElement('div');
            cont.id = 'lay8-toast-container';
            cont.className = 'lay8-toast-container';
            document.body.appendChild(cont);
        }
        return cont;
    }

    function toast(mensaje, tipo = 'info', duracion = 3800) {
        const cont = ensureToastContainer();
        if (!cont) return;

        const normTipo = (tipo === 'success') ? 'exito' : tipo;
        const iconMap = {
            exito: 'bx-check-circle',
            error: 'bx-error-circle',
            warning: 'bx-error',
            info: 'bx-info-circle'
        };

        const item = document.createElement('div');
        item.className = 'lay8-toast ' + normTipo;

        const icon = document.createElement('i');
        icon.className = 'bx ' + (iconMap[normTipo] || iconMap.info) + ' lay8-toast-icon';

        const span = document.createElement('span');
        span.textContent = String(mensaje || '');

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'lay8-toast-close';
        closeBtn.setAttribute('aria-label', 'Cerrar');
        closeBtn.innerHTML = '&times;';

        const dismiss = () => {
            item.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateY(8px)';
            setTimeout(() => item.remove(), 200);
        };

        closeBtn.addEventListener('click', dismiss);
        item.appendChild(icon);
        item.appendChild(span);
        item.appendChild(closeBtn);
        cont.appendChild(item);

        if (duracion > 0) {
            setTimeout(dismiss, duracion);
        }
    }

    function confirmModal(mensaje, opts = {}) {
        return new Promise(resolve => {
            if (!document.body) {
                resolve(window.confirm(mensaje));
                return;
            }

            const overlay = document.createElement('div');
            overlay.className = 'lay8-confirm-overlay';

            const box = document.createElement('div');
            box.className = 'lay8-confirm-box';
            box.setAttribute('role', 'dialog');
            box.setAttribute('aria-modal', 'true');

            const header = document.createElement('div');
            header.className = 'lay8-confirm-header';
            const headerIcon = document.createElement('i');
            headerIcon.className = 'bx ' + (opts.danger ? 'bx-error-circle' : 'bx-help-circle');
            headerIcon.style.color = opts.danger ? '#dc2626' : '#2563eb';
            headerIcon.style.fontSize = '1.3rem';
            const titleEl = document.createElement('span');
            titleEl.textContent = opts.title || 'Confirmar acción';
            header.appendChild(headerIcon);
            header.appendChild(titleEl);

            const body = document.createElement('div');
            body.className = 'lay8-confirm-body';
            body.textContent = String(mensaje || '');

            const actions = document.createElement('div');
            actions.className = 'lay8-confirm-actions';

            const btnCancel = document.createElement('button');
            btnCancel.type = 'button';
            btnCancel.className = 'lay8-btn-sec';
            btnCancel.textContent = opts.cancelText || 'Cancelar';

            const btnOk = document.createElement('button');
            btnOk.type = 'button';
            btnOk.className = 'lay8-btn-pri' + (opts.danger ? ' danger' : '');
            btnOk.textContent = opts.confirmText || 'Confirmar';

            const cleanup = (result) => {
                document.removeEventListener('keydown', onKey);
                overlay.remove();
                resolve(result);
            };

            const onKey = (e) => {
                if (e.key === 'Escape') cleanup(false);
            };

            btnCancel.addEventListener('click', () => cleanup(false));
            btnOk.addEventListener('click', () => cleanup(true));
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) cleanup(false);
            });
            document.addEventListener('keydown', onKey);

            actions.appendChild(btnCancel);
            actions.appendChild(btnOk);
            box.appendChild(header);
            box.appendChild(body);
            box.appendChild(actions);
            overlay.appendChild(box);
            document.body.appendChild(overlay);
            btnOk.focus();
        });
    }

    function confirmSubmit(event, mensaje, opts = {}) {
        const form = event && event.currentTarget ? event.currentTarget : null;
        if (!form) return true;
        if (form.dataset.lay8Confirmed === '1') {
            form.dataset.lay8Confirmed = '';
            return true;
        }
        event.preventDefault();
        confirmModal(mensaje, opts).then(ok => {
            if (ok) {
                form.dataset.lay8Confirmed = '1';
                form.submit();
            }
        });
        return false;
    }

    global.Lay8UI = {
        getCsrf,
        syncCsrfHash,
        apiFetch,
        toast,
        confirm: confirmModal,
        confirmSubmit
    };
})(window);
