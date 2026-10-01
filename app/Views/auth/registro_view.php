<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - LAY-8 MAP</title>

  <!-- precarga de fuentes-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
	<!-- stylesheets-->
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=4')?>">
	<link rel="stylesheet" href="<?= base_url('css/registro.css?v=4')?>">
</head>

<body>
  <div class="top-header-registro">

    <div>
      <div class="titulos-inline">
        <h2 class="form-titulo">Registro</h2>
        <span class="form-subtitulo-inline">Crea tu cuenta</span>
      </div>

      <hr class="divisor-global">

    </div>
  
  
    <div class="registro-logo">
        <img src="<?= base_url('img/lay8_logo.png') ?>" alt="LOGO">
        <span>LAY-8 MAP</span>
    </div>
  </div>

  <div class=card-global> 

<!--BACKEND: mensajes d error y asi. revisa o cambialo si es necesario pero manten 
la clase error-msg para que se impriman con el estilo que diseñe ^u^ -->

    <?php if(isset($errores)): ?>
      <div class="error-msg">
        <?= $errores->listErrors() ?>
      </div>
    <?php endif;?>

    <div class="progress-bar">
      <div class="step activo" id="indicator-1">1. Datos Personales</div>
      <div class="step" id="indicator-2">2. Datos de Acceso</div>
    </div>

    <form id="registroForm" action="<?= base_url('registro/procesar')?>" method="POST" enctype="multipart/form-data">
      <!--PASO 1-->
      <div id="paso1">
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Primer Nombre</label>
            <input type="text" name="primer_nombre" class="input-global" required>
          </div>
          <div class="form-col">
            <label class="form-label">Apellidos</label>
            <input type="text" name="apellidos" class="input-global" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" name="correo" class="input-global" required>
          </div>
          <div class="form-col">
            <label class="form-label">Teléfono (Opc.)</label>
            <input type="text" name="telefono" class="input-global">
          </div>
        </div>

        <div class="docente-row">
          <div class="docente-col">
          <label class="form-label">¿Es docente?</label>
          <div class="radio-stack">
            <label class="radio-item">
              <input type="radio" name="es_profesor" value="no" checked onchange="mostrarCarnet(false)"> No
            </label>
            <label class="radio-item">
              <input type="radio" name="es_profesor" value="si" onchange="mostrarCarnet(true)"> Sí
            </label>
          </div>
        </div>

        <div id="div_carnet" class="docente-col" style="display: none;">
          <label class="form-label" style="color: #1c53b0;">Verifique su rol. Subir Carnet</label>
          <input type="file" name="carnet" accept=".png, .jpg, .jpeg, .pdf">
        </div>
      </div>

        <div class="botones-wizard">
          <button type="button" class="btn-primary btn-siguiente" onclick="siguientePaso()">Siguiente</button>
        </div>

      </div>

      <!--PASO 2-->

      <div id="paso2" style="display: none;">
        <div style="margin-bottom: 20px;">
          <label class="form-label">Nombre de Usuario</label>
          <input type="text" name="nombre_usuario" class="input-global" required>
        </div>
        
        <div class="form-row" style="margin-bottom: 35px;">
          <div class="form-col">
            <label class="form-label">Contraseña</label>

            <div class="input-pass-group">
              <input type="password" id="contrasena" name=contrasena class="input-global" required minlegth="8">
              <button type="button" id="btn-toggle-pass" class="btn-toggle-pass">
                <img src="<?= base_url('img/ojo.svg')?>" alt="Mostrar contraseña" id="eye-icon">
              </button>
            </div>

          </div>

          <div class="form-col">
            <label class="form-label">Confirmar contraseña</label>
            <input type="password" name="repetir_contrasena" class="input-global" required>
          </div>
        </div>

        <div class="botones-wizard">
          <button type"button" class="btn-atras btn-mitad" onclick="pasoAnterior()">Atrás</button>
          <button type"submit" class="btn-primary btn-mitad" style="flex: 2;">Finalizar</button>
        </div>
      </div>
    </form>

    <div class="form-footer">
      <span style="color: #64748b;">¿Ya tienes cuenta?</span> 
      <a href="<?= base_url('login')?>" class="link-global">Inicia Sesión</a>
    </div>
  </div>

  <script src="<?= base_url('js/registro.js?v=4')?>"></script>
</body>
</html>