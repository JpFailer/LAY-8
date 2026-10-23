<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - LAY-8 MAP</title>

  <!-- precarga de fuentes-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
	<!-- stylesheets-->
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=5')?>">
	<link rel="stylesheet" href="<?= base_url('css/registro.css?v=5')?>">
</head>

<body>
  <div class="top-header-registro">

    <div>
      <div class="titulos-inline">
        <h2 class="form-titulo">Iniciar Sesión</h2>
        <span class="form-subtitulo-inline">Ingresa a tu cuenta</span>
      </div>

      <hr class="divisor-global">

    </div>
  
  
    <div class="registro-logo">
        <img src="<?= base_url('img/lay8_logo.png') ?>" alt="LOGO">
        <span>LAY-8 MAP</span>
    </div>
  </div>

  <div class="card-global" style="max-width: 500px; margin-top: 50px;"> 

    <?php if(session()->getFlashdata('error')): ?>
      <div class="error-msg">
        <?= session()->getFlashdata('error') ?>
      </div>
    <?php endif;?>

    <?php if(session()->getFlashdata('mensaje')): ?>
      <div class="success-msg" style="background-color: #d1e7dd; color: #0f5132; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; text-align: center;">
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

  <script src="<?= base_url('js/main.js?v=4')?>"></script>
</body>
</html>
