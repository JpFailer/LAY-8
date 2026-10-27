<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - LAY-8</title>

  <!-- Script anti-flicker: tema claro por defecto en auth -->
  <script>
    (function() {
      try {
        if (localStorage.getItem('lay8_auth_override') !== 'true') {
          document.documentElement.classList.remove('dark-mode');
        } else if (localStorage.getItem('lay8_theme') === 'dark') {
          document.documentElement.classList.add('dark-mode');
        }
      } catch(e) {}
    })();
  </script>

  <!-- precarga de fuentes-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
  <!-- stylesheets-->
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=' . time())?>">
  <link rel="stylesheet" href="<?= base_url('css/registro.css?v=' . time())?>">
</head>

<body data-tema-default="light">
  <div class="top-header-registro">

    <div>
      <div class="titulos-inline">
        <h2 class="form-titulo">Iniciar Sesión</h2>
        <span class="form-subtitulo-inline">Ingresa a tu cuenta</span>
      </div>

      <hr class="divisor-global">

    </div>
  
  
    <div class="registro-logo">
        <a href="<?= base_url('/') ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
          <img src="<?= base_url('img/lay8_logo.png') ?>" alt="LOGO">
          <span>LAY-8</span>
        </a>
    </div>
  </div>

  <div class="card-global" style="max-width: 500px; margin-top: 50px;"> 

    <?php if(session()->getFlashdata('error')): ?>
      <div class="error-msg">
        <?= session()->getFlashdata('error') ?>
      </div>
    <?php endif;?>

    <?php if(session()->getFlashdata('mensaje')): ?>
      <div class="success-msg" style="background-color: #d1e7dd; color: #0f5132; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
        <?= session()->getFlashdata('mensaje') ?>
      </div>
    <?php endif;?>

    <form id="registroForm" action="<?= base_url('login/autenticar')?>" method="POST">
      <?= csrf_field() ?>

      <div style="max-width: 450px; margin: 0 auto;">

        <div class="form-col" style="margin-bottom: 20px;">
          <label class="form-label">Nombre de Usuario</label>
          <input type="text" name="nombre_usuario" class="input-global" required>
        </div>
        
        <div class="form-col">
          <label class="form-label">Contraseña</label>

          <div class="input-pass-group">
            <input type="password" id="contrasena" name="contrasena" class="input-global" required minlength="8">
            <button type="button" id="btn-toggle-pass" class="btn-toggle-pass">
              <img src="<?= base_url('img/ojo.svg')?>" alt="Mostrar contraseña" id="eye-icon">
            </button>
          </div>

          <!-- Posición por debajo de la contraseña, no de usuario -->
          <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
            <a href="javascript:void(0)" onclick="abrirModalRecuperacionLogin()" class="link-olvido" style="font-size: 0.83rem; color: #1a56db; text-decoration: none; font-weight: 600;">¿Olvidaste tu contraseña?</a>
          </div>
        </div>

        <div class="botones-wizard" style="margin-top: 30px;">
          <button type="submit" class="btn-primary btn-mitad" style="flex: 2; width: 100%;">Ingresar</button>
        </div>
      </div>
    </form>

    <div class="form-footer">
      <span style="color: #64748b;">¿No tienes cuenta?</span> 
      <a href="<?= base_url('registro')?>" class="link-global">Regístrate</a>
    </div>
  </div>

  <!-- Modal Recuperación de Contraseña Interactivo (Adaptable y con Scroll) -->
  <div id="modal-recuperacion-login" class="modal-terminos-overlay" style="display: none; padding: 15px;" onclick="if(event.target === this) cerrarModalRecuperacionLogin()">
    <div class="modal-terminos-content" style="max-width: 530px; max-height: calc(100vh - 30px); border-radius: 18px; overflow: hidden; display: flex; flex-direction: column;">
      <div class="modal-terminos-header" style="padding: 14px 20px;">
        <h3 style="font-size: 1.2rem;"><i class="bx bx-key"></i> Recuperar Contraseña</h3>
        <button type="button" class="btn-cerrar-terminos" onclick="cerrarModalRecuperacionLogin()">&times;</button>
      </div>

      <div class="modal-terminos-body" style="padding: 20px 24px; overflow-y: auto; max-height: calc(100vh - 100px); box-sizing: border-box;">
        <!-- PASO 1 -->
        <div id="recup-login-paso-1">
          <p style="font-size: 0.88rem; color: #64748b; margin-top: 0; margin-bottom: 16px;">
            Ingresa tu nombre de usuario o correo para verificar tu cuenta institucional:
          </p>

          <div id="recup-login-alerta-1" style="display: none; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: 8px; font-size: 0.86rem; margin-bottom: 14px;"></div>

          <div style="margin-bottom: 18px;">
            <label class="form-label">Usuario o Correo Registrado</label>
            <input type="text" id="recup_login_identificador" class="input-global" placeholder="Ej: tu_usuario o correo@umc.edu.ve">
          </div>

          <button type="button" id="btn-login-recup-1" onclick="ejecutarVerificarCuentaLogin()" class="btn-primary" style="width: 100%;">Verificar Cuenta</button>
        </div>

        <!-- PASO 2: Verificación de Identidad con PDF y Nueva Contraseña -->
        <div id="recup-login-paso-2" style="display: none;">
          <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 6px 12px; font-size: 0.84rem; color: #1e40af; margin-bottom: 12px; font-weight: 600;" id="recup-login-user-info">
            Cuenta Verificada
          </div>

          <!-- AVISO DE SEGURIDAD UMC ANTI-SUPLANTACIÓN COMPACTO -->
          <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 10px; padding: 10px 12px; margin-bottom: 14px;">
            <div style="display: flex; align-items: flex-start; gap: 8px;">
              <i class="bx bx-shield-check" style="font-size: 1.4rem; color: #1e40af; flex-shrink: 0; margin-top: 1px;"></i>
              <div style="font-size: 0.8rem; color: #1e3a8a; line-height: 1.4;">
                <strong style="display: block; font-size: 0.84rem; margin-bottom: 2px;">Seguridad Anti-Suplantación de Identidad</strong>
                Para comprobar que eres el titular legítimo, adjunta tu <strong>Registro de Inscripción oficial UMC (PDF)</strong> emitido por <code>campus.umc.edu.ve</code> (válido el original o uno más reciente).
              </div>
            </div>
          </div>

          <div id="recup-login-alerta-2" style="display: none; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 8px 12px; border-radius: 8px; font-size: 0.84rem; margin-bottom: 12px;"></div>

          <input type="hidden" id="recup_login_uid">

          <!-- Campo de subida de PDF obligatorio -->
          <div style="margin-bottom: 14px;">
            <label class="form-label" style="font-weight: 700; color: #1e3a8a;">
              <i class="bx bxs-file-pdf" style="color: #dc2626;"></i> Registro de Inscripción UMC (PDF) *
            </label>
            <input type="file" id="recup_login_pdf" accept=".pdf" class="input-global" style="padding: 8px 12px;" required onchange="actualizarFeedbackPdfLogin(this)">

            <!-- Feedback visual de PDF seleccionado -->
            <div id="recup-login-pdf-feedback" style="display: none; align-items: center; justify-content: space-between; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 8px; padding: 6px 10px; margin-top: 6px;">
              <div style="display: flex; align-items: center; gap: 6px; overflow: hidden;">
                <i class="bx bxs-file-pdf" style="font-size: 1.3rem; color: #dc2626; flex-shrink: 0;"></i>
                <div style="display: flex; flex-direction: column; overflow: hidden;">
                  <span id="recup-login-pdf-nombre" style="font-size: 0.8rem; font-weight: 600; color: #166534; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">documento.pdf</span>
                  <span id="recup-login-pdf-tamano" style="font-size: 0.72rem; color: #15803d;">0 KB</span>
                </div>
              </div>
              <span style="font-size: 0.72rem; font-weight: 700; background: #dcfce7; color: #166534; padding: 2px 7px; border-radius: 9999px;"><i class="bx bx-check"></i> Listo</span>
            </div>
          </div>

          <!-- Campos de contraseña en 2 columnas -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
            <div>
              <label class="form-label">Nueva Contraseña *</label>
              <input type="password" id="recup_login_clave" class="input-global" placeholder="Mín. 8 caract.">
            </div>

            <div>
              <label class="form-label">Confirmar Contraseña *</label>
              <input type="password" id="recup_login_rep" class="input-global" placeholder="Repite la clave">
            </div>
          </div>

          <button type="button" id="btn-login-recup-2" onclick="ejecutarGuardarClaveLogin()" class="btn-primary" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="bx bx-check-shield"></i> Validar Documento y Restablecer
          </button>
        </div>

        <!-- PASO 3 -->
        <div id="recup-login-paso-3" style="display: none; text-align: center; padding: 12px 0;">
          <div style="width: 55px; height: 55px; background: #dcfce7; color: #166534; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 10px;">
            <i class="bx bx-check"></i>
          </div>
          <h4 style="margin: 0 0 6px 0; font-size: 1.25rem;">¡Contraseña Restablecida!</h4>
          <p style="font-size: 0.88rem; color: #64748b; margin-bottom: 18px;">
            Tu contraseña se ha actualizado correctamente. Ya puedes ingresar con tu nueva clave.
          </p>
          <button type="button" onclick="cerrarModalRecuperacionLogin()" class="btn-primary" style="width: 100%;">Iniciar Sesión Ahora</button>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= base_url('js/main.js?v=' . time())?>"></script>
  <script>
    function abrirModalRecuperacionLogin() {
      const m = document.getElementById('modal-recuperacion-login');
      if (m) {
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.getElementById('recup-login-paso-1').style.display = 'block';
        document.getElementById('recup-login-paso-2').style.display = 'none';
        document.getElementById('recup-login-paso-3').style.display = 'none';
        const a1 = document.getElementById('recup-login-alerta-1');
        if (a1) a1.style.display = 'none';
      }
    }

    function cerrarModalRecuperacionLogin() {
      const m = document.getElementById('modal-recuperacion-login');
      if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
    }

    function ejecutarVerificarCuentaLogin() {
      const iden = document.getElementById('recup_login_identificador').value.trim();
      const errBox = document.getElementById('recup-login-alerta-1');
      const btn = document.getElementById('btn-login-recup-1');

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

      fetch('<?= base_url('login/verificar-recuperacion') ?>', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        btn.textContent = 'Verificar Cuenta';
        btn.disabled = false;
        if (data.exito) {
          document.getElementById('recup_login_uid').value = data.usuario_id;
          document.getElementById('recup-login-user-info').innerHTML = '<i class="bx bxs-user-check"></i> ' + data.nombre + ' (' + data.rol + ')';
          document.getElementById('recup-login-paso-1').style.display = 'none';
          document.getElementById('recup-login-paso-2').style.display = 'block';
        } else {
          errBox.textContent = data.mensaje || 'Cuenta no encontrada.';
          errBox.style.display = 'block';
        }
      })
      .catch(err => {
        btn.textContent = 'Verificar Cuenta';
        btn.disabled = false;
        errBox.textContent = 'Error al verificar la cuenta.';
        errBox.style.display = 'block';
      });
    }

    function actualizarFeedbackPdfLogin(input) {
      const feedback = document.getElementById('recup-login-pdf-feedback');
      const nombreSpan = document.getElementById('recup-login-pdf-nombre');
      const tamanoSpan = document.getElementById('recup-login-pdf-tamano');
      if (input.files && input.files[0]) {
        const file = input.files[0];
        if (nombreSpan) nombreSpan.textContent = file.name;
        if (tamanoSpan) {
          const kb = (file.size / 1024).toFixed(1);
          tamanoSpan.textContent = (kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB');
        }
        if (feedback) feedback.style.display = 'flex';
      }
    }

    function ejecutarGuardarClaveLogin() {
      const uid = document.getElementById('recup_login_uid').value;
      const clave = document.getElementById('recup_login_clave').value;
      const rep = document.getElementById('recup_login_rep').value;
      const fileInput = document.getElementById('recup_login_pdf');
      const errBox = document.getElementById('recup-login-alerta-2');
      const btn = document.getElementById('btn-login-recup-2');

      if (!fileInput.files || fileInput.files.length === 0) {
        errBox.textContent = 'Por seguridad anti-suplantación, debes adjuntar obligatoriamente tu Registro de Inscripción UMC en formato PDF.';
        errBox.style.display = 'block';
        return;
      }

      const archivo = fileInput.files[0];
      if (!archivo.name.toLowerCase().endsWith('.pdf')) {
        errBox.textContent = 'El comprobante debe ser exclusivamente un archivo digital PDF oficial.';
        errBox.style.display = 'block';
        return;
      }

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
      btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Verificando documento con motor UMC...';
      btn.disabled = true;

      const formData = new FormData();
      formData.append('usuario_id', uid);
      formData.append('nueva_contrasena', clave);
      formData.append('repetir_contrasena', rep);
      formData.append('planilla_inscripcion', archivo);

      fetch('<?= base_url('login/restablecer-contrasena') ?>', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        btn.innerHTML = '<i class="bx bx-check-shield"></i> Validar Documento y Restablecer';
        btn.disabled = false;
        if (data.exito) {
          document.getElementById('recup-login-paso-2').style.display = 'none';
          document.getElementById('recup-login-paso-3').style.display = 'block';
        } else {
          errBox.textContent = data.mensaje || 'Error al validar el documento o actualizar contraseña.';
          errBox.style.display = 'block';
        }
      })
      .catch(err => {
        btn.innerHTML = '<i class="bx bx-check-shield"></i> Validar Documento y Restablecer';
        btn.disabled = false;
        errBox.textContent = 'Error en el servidor al verificar el comprobante.';
        errBox.style.display = 'block';
      });
    }

    window.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') cerrarModalRecuperacionLogin();
    });
  </script>
</body>
</html>