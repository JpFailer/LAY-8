/**
 * auth.js — Lógica de recuperación de contraseña y modales de autenticación.
 */
(function () {
    function getRecoveryModal() {
        return document.getElementById('modal-recuperacion-login');
    }

    function getCsrfField() {
        const modal = getRecoveryModal();
        const csrfName = modal?.dataset?.csrfName || '';
        const input = csrfName
            ? document.querySelector(`input[name="${csrfName}"]`)
            : document.querySelector('input[name^="csrf"]');
        return {
            name: csrfName || (input ? input.name : ''),
            value: input ? input.value : '',
        };
    }

    window.abrirModalRecuperacionLogin = function () {
        const m = getRecoveryModal();
        if (!m) return;
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        const p1 = document.getElementById('recup-login-paso-1');
        const p2 = document.getElementById('recup-login-paso-2');
        const p3 = document.getElementById('recup-login-paso-3');
        if (p1) p1.style.display = 'block';
        if (p2) p2.style.display = 'none';
        if (p3) p3.style.display = 'none';
        const a1 = document.getElementById('recup-login-alerta-1');
        if (a1) a1.style.display = 'none';
    };

    window.cerrarModalRecuperacionLogin = function () {
        const m = getRecoveryModal();
        if (m) {
            m.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    window.ejecutarVerificarCuentaLogin = function () {
        const modal = getRecoveryModal();
        const urlVerificar = modal?.dataset?.urlVerificar || '';
        const idenEl = document.getElementById('recup_login_identificador');
        const errBox = document.getElementById('recup-login-alerta-1');
        const btn = document.getElementById('btn-login-recup-1');
        if (!idenEl || !errBox || !btn || !urlVerificar) return;

        const iden = idenEl.value.trim();
        if (!iden) {
            errBox.textContent = 'Por favor ingresa tu usuario o correo.';
            errBox.style.display = 'block';
            return;
        }

        errBox.style.display = 'none';
        btn.textContent = 'Verificando...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('identificador', iden);
        const csrf = getCsrfField();
        if (csrf.name && csrf.value) formData.append(csrf.name, csrf.value);

        fetch(urlVerificar, { method: 'POST', body: formData })
            .then((r) => r.json())
            .then((data) => {
                btn.textContent = 'Verificar Cuenta';
                btn.disabled = false;
                if (data.exito) {
                    const userEl = document.getElementById('recup-login-user-info');
                    if (userEl) userEl.textContent = data.nombre || data.usuario;
                    const correoEl = document.getElementById('recup-login-correo-destino');
                    if (correoEl) correoEl.textContent = data.correo_ofuscado;

                    const devCard = document.getElementById('recup-login-dev-card');
                    const devLink = document.getElementById('recup-login-dev-link');
                    if (data.enlace_directo && devCard && devLink) {
                        devLink.href = data.enlace_directo;
                        devCard.style.display = 'block';
                    } else if (devCard) {
                        devCard.style.display = 'none';
                    }

                    const p1 = document.getElementById('recup-login-paso-1');
                    const p2 = document.getElementById('recup-login-paso-2');
                    if (p1) p1.style.display = 'none';
                    if (p2) p2.style.display = 'block';
                } else {
                    errBox.textContent = data.mensaje || 'Cuenta no encontrada.';
                    errBox.style.display = 'block';
                }
            })
            .catch(() => {
                btn.textContent = 'Verificar Cuenta';
                btn.disabled = false;
                errBox.textContent = 'Error al verificar la cuenta.';
                errBox.style.display = 'block';
            });
    };

    window.actualizarFeedbackPdfLogin = function (input) {
        const feedback = document.getElementById('recup-login-pdf-feedback');
        const nombreSpan = document.getElementById('recup-login-pdf-nombre');
        const tamanoSpan = document.getElementById('recup-login-pdf-tamano');
        if (input?.files && input.files[0]) {
            const file = input.files[0];
            if (nombreSpan) nombreSpan.textContent = file.name;
            if (tamanoSpan) {
                const kb = (file.size / 1024).toFixed(1);
                tamanoSpan.textContent = kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
            }
            if (feedback) feedback.style.display = 'flex';
        }
    };

    window.volverAPaso1RecupLogin = function () {
        const p2 = document.getElementById('recup-login-paso-2');
        const p1 = document.getElementById('recup-login-paso-1');
        if (p2) p2.style.display = 'none';
        if (p1) p1.style.display = 'block';
        const input = document.getElementById('recup_login_identificador');
        if (input) {
            input.focus();
            input.select();
        }
    };

    window.ejecutarGuardarClaveLogin = function () {
        const modal = getRecoveryModal();
        const urlRestablecer = modal?.dataset?.urlRestablecer || '';
        const uid = document.getElementById('recup_login_uid')?.value || '';
        const token = document.getElementById('recup_login_token')?.value || '';
        const clave = document.getElementById('recup_login_clave')?.value || '';
        const rep = document.getElementById('recup_login_rep')?.value || '';
        const errBox = document.getElementById('recup-login-alerta-2');
        const btn = document.getElementById('btn-login-recup-2');
        if (!errBox || !btn || !urlRestablecer) return;

        if (!clave || clave.length < 8) {
            errBox.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
            errBox.style.display = 'block';
            return;
        }

        if (clave !== rep) {
            errBox.textContent = 'Las contraseñas no coinciden.';
            errBox.style.display = 'block';
            return;
        }

        errBox.style.display = 'none';
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Guardando...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('usuario_id', uid);
        const csrf = getCsrfField();
        if (csrf.name && csrf.value) formData.append(csrf.name, csrf.value);
        if (token) formData.append('token', token);
        formData.append('nueva_contrasena', clave);
        formData.append('repetir_contrasena', rep);

        fetch(urlRestablecer, { method: 'POST', body: formData })
            .then((r) => r.json())
            .then((data) => {
                btn.innerHTML = '<i class="bx bx-save"></i> Guardar Nueva Contraseña';
                btn.disabled = false;
                if (data.exito) {
                    const p2 = document.getElementById('recup-login-paso-2');
                    const p3 = document.getElementById('recup-login-paso-3');
                    if (p2) p2.style.display = 'none';
                    if (p3) p3.style.display = 'block';
                } else {
                    errBox.textContent = data.mensaje || 'Error al actualizar contraseña.';
                    errBox.style.display = 'block';
                }
            })
            .catch(() => {
                btn.innerHTML = '<i class="bx bx-save"></i> Guardar Nueva Contraseña';
                btn.disabled = false;
                errBox.textContent = 'Error en el servidor al actualizar contraseña.';
                errBox.style.display = 'block';
            });
    };

    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.cerrarModalRecuperacionLogin();
        }
    });
})();
