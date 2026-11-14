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
  <link rel="stylesheet" href="<?= asset_url('css/global.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('css/registro.css') ?>">
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
        <?= esc(session()->getFlashdata('error')) ?>
      </div>
    <?php endif;?>

    <?php if(session()->getFlashdata('mensaje')): ?>
      <div class="success-msg" style="background-color: #d1e7dd; color: #0f5132; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
        <?= esc(session()->getFlashdata('mensaje')) ?>
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
      <span>¿No tienes cuenta?</span> 
      <a href="<?= base_url('registro')?>" class="link-global">Regístrate</a>
    </div>
  </div>

  <!-- Modal Recuperación de Contraseña Interactivo (Adaptable y con Scroll) -->
  <div id="modal-recuperacion-login"
       class="modal-terminos-overlay"
       style="display: none; padding: 15px;"
       data-url-verificar="<?= base_url('login/verificar-recuperacion') ?>"
       data-url-restablecer="<?= base_url('login/restablecer-contrasena') ?>"
       data-csrf-name="<?= csrf_token() ?>"
       onclick="if(event.target === this) cerrarModalRecuperacionLogin()">
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

        <!-- PASO 2: Confirmación de Envío de Enlace Seguro -->
        <div id="recup-login-paso-2" style="display: none; text-align: center;">
          <div style="width: 54px; height: 54px; background: #ecfdf5; color: #059669; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 10px auto;">
            <i class="bx bx-mail-send"></i>
          </div>
          <h4 style="margin: 0 0 6px 0; font-size: 1.25rem;">¡Enlace de Recuperación Enviado!</h4>
          <p style="font-size: 0.88rem; color: #64748b; margin: 0 0 16px 0; line-height: 1.5;">
            Hemos enviado un enlace seguro al correo registrado de la cuenta <strong id="recup-login-user-info" style="color: #0f172a;"></strong>:
          </p>

          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 16px; text-align: left;">
            <div style="display: flex; align-items: center; gap: 10px; color: #0f172a; font-weight: 600; font-size: 0.95rem; margin-bottom: 6px;">
              <i class="bx bx-envelope" style="color: #2563eb; font-size: 1.3rem;"></i>
              <span id="recup-login-correo-destino"></span>
            </div>
            <p style="font-size: 0.82rem; color: #64748b; margin: 0; line-height: 1.4; border-top: 1px dashed #cbd5e1; padding-top: 8px;">
              <i class="bx bx-shield-quarter" style="color: #059669;"></i> Por seguridad institucional, solo quien tenga acceso a este correo puede cambiar la clave. El enlace caduca en <strong>2 horas</strong>.
            </p>
          </div>

          <!-- Botón de testing sólo si estamos en localhost/desarrollo -->
          <div id="recup-login-dev-card" style="display: none; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 12px; margin-bottom: 16px; text-align: left; font-size: 0.82rem; color: #92400e;">
            <div style="display: flex; align-items: flex-start; gap: 8px;">
              <i class="bx bx-info-circle" style="font-size: 1.2rem; color: #d97706; flex-shrink: 0; margin-top: 1px;"></i>
              <div>
                <strong>Modo de pruebas local:</strong> Como tu servidor local no cuenta con servidor SMTP de correo configurado, puedes acceder a la página de restablecimiento directamente:
                <div style="margin-top: 8px;">
                  <a id="recup-login-dev-link" href="#" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; background: #2563eb; color: #ffffff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.8rem;">
                    <i class="bx bx-link-external"></i> Abrir Enlace de Recuperación
                  </a>
                </div>
              </div>
            </div>
          </div>

          <button type="button" onclick="cerrarModalRecuperacionLogin()" class="btn-primary" style="width: 100%;">
            Entendido
          </button>

          <div style="margin-top: 14px;">
            <a href="javascript:void(0)" onclick="volverAPaso1RecupLogin()" style="font-size: 0.83rem; color: #2563eb; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
              <i class="bx bx-arrow-back"></i> Verificar otra cuenta
            </a>
          </div>
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

  <script src="<?= asset_url('js/main.js') ?>"></script>
  <script src="<?= asset_url('js/auth.js') ?>"></script>
</body>
</html>