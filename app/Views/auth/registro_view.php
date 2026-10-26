<?php
$carrerasDisponibles = [
    'ING-INF' => [
        'nombre'  => 'Ingeniería en Informática',
        'escuela' => 'Escuela de Informática',
        'icono'   => 'bx-code-alt'
    ],
    'ING-MAR' => [
        'nombre'  => 'Ingeniería Marítima',
        'escuela' => 'Escuela de Náutica',
        'icono'   => 'bx-anchor'
    ],
    'ING-AMB' => [
        'nombre'  => 'Ingeniería Ambiental',
        'escuela' => 'Escuela de Ciencias Aplicadas',
        'icono'   => 'bx-leaf'
    ],
    'ADM' => [
        'nombre'  => 'Licenciatura en Administración',
        'escuela' => 'Escuela de Ciencias Sociales',
        'icono'   => 'bx-briefcase-alt-2'
    ],
    'TUR' => [
        'nombre'  => 'Licenciatura en Turismo',
        'escuela' => 'Escuela de Turismo',
        'icono'   => 'bx-compass'
    ]
];
$carreraActual = old('carrera', 'ING-INF');
if (!isset($carrerasDisponibles[$carreraActual])) {
    $carreraActual = 'ING-INF';
}
?>
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
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=11')?>">
  <link rel="stylesheet" href="<?= base_url('css/registro.css?v=5')?>">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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

  <div class="card-global"> 

    <?php if(isset($errores)): ?>
      <div class="error-msg">
        <?= $errores->listErrors() ?>
      </div>
    <?php endif;?>

    <?php if(session()->getFlashdata('error')): ?>
      <div class="error-msg">
        <?= session()->getFlashdata('error') ?>
      </div>
    <?php endif;?>

    <?php if(session()->getFlashdata('exito')): ?>
      <div class="success-msg" style="background-color: #d1e7dd; color: #0f5132; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
        <?= session()->getFlashdata('exito') ?>
      </div>
    <?php endif;?>

    <div class="progress-bar">
      <div class="step activo" id="indicator-1">1. Datos Personales</div>
      <div class="step" id="indicator-2">2. Datos de Acceso</div>
    </div>

    <form id="registroForm" action="<?= base_url('registro/procesar')?>" method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <!--PASO 1-->
      <div id="paso1">
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Primer Nombre</label>
            <input type="text" name="primer_nombre" class="input-global" value="<?= old('primer_nombre') ?>" required>
          </div>
          <div class="form-col">
            <label class="form-label">Apellidos</label>
            <input type="text" name="apellidos" class="input-global" value="<?= old('apellidos') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" name="correo" class="input-global" value="<?= old('correo') ?>" required>
          </div>
          <div class="form-col">
            <label class="form-label">Teléfono (Opc.)</label>
            <input type="text" name="telefono" class="input-global" value="<?= old('telefono') ?>">
          </div>
        </div>

        <!-- Selector de Carrera con Ventana Modal -->
        <div style="margin-bottom: 20px;">
          <label class="form-label">Carrera Universitaria (UMC)</label>
          <input type="hidden" name="carrera" id="input_carrera" value="<?= esc($carreraActual) ?>">
          <div class="tarjeta-carrera-display" onclick="abrirModalCarreras()" title="Haz clic para seleccionar tu carrera">
            <div class="carrera-display-info">
              <span class="carrera-display-code" id="display_carrera_code"><?= esc($carreraActual) ?></span>
              <span class="carrera-display-nombre" id="display_carrera_nombre"><?= esc($carrerasDisponibles[$carreraActual]['nombre']) ?></span>
            </div>
            <button type="button" class="btn-cambiar-carrera" onclick="event.stopPropagation(); abrirModalCarreras();">
              <i class="bx bx-transfer-alt"></i> Cambiar
            </button>
          </div>
        </div>

        <!-- Comprobante Oficial de Inscripción UMC para Estudiantes -->
        <div id="div_inscripcion_estudiante" style="margin-bottom: 20px; display: <?= old('es_profesor') === 'si' ? 'none' : 'block' ?>;">
          <label class="form-label" style="color: #1c53b0;"><i class="bx bx-file-blank"></i> Registro de Inscripción UMC (PDF)</label>
          <p style="font-size: 0.82rem; color: #64748b; margin-top: -3px; margin-bottom: 8px;">
            Sube el comprobante oficial (PDF) emitido por <code>campus.umc.edu.ve</code>. El sistema certificará tu carrera y te asignará automáticamente a tus aulas.
          </p>
          <input type="file" name="planilla_inscripcion" id="input_planilla_inscripcion" accept=".pdf" class="input-global" <?= old('es_profesor') === 'si' ? '' : 'required' ?>>
        </div>

        <div class="docente-row">
          <div class="docente-col">
          <label class="form-label">¿Es docente?</label>
          <div class="radio-stack">
            <label class="radio-item">
              <input type="radio" name="es_profesor" value="no" <?= old('es_profesor', 'no') === 'no' ? 'checked' : '' ?> onchange="mostrarCarnet(false)"> No
            </label>
            <label class="radio-item">
              <input type="radio" name="es_profesor" value="si" <?= old('es_profesor') === 'si' ? 'checked' : '' ?> onchange="mostrarCarnet(true)"> Sí
            </label>
          </div>
        </div>

        <div id="div_carnet" class="docente-col" style="display: <?= old('es_profesor') === 'si' ? 'block' : 'none' ?>;">
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
          <input type="text" name="nombre_usuario" class="input-global" value="<?= old('nombre_usuario') ?>" required>
        </div>
        
        <div class="form-row" style="margin-bottom: 35px;">
          <div class="form-col">
            <label class="form-label">Contraseña</label>

            <div class="input-pass-group">
              <input type="password" id="contrasena" name="contrasena" class="input-global" required minlength="8">
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
          <button type="button" class="btn-atras btn-mitad" onclick="pasoAnterior()">Atrás</button>
          <button type="submit" class="btn-primary btn-mitad" style="flex: 2;">Finalizar</button>
        </div>
      </div>
    </form>

    <div class="form-footer">
      <span style="color: #64748b;">¿Ya tienes cuenta?</span> 
      <a href="<?= base_url('login')?>" class="link-global">Inicia Sesión</a>
    </div>
  </div>

  <!-- Modal Selector de Carreras UMC -->
  <div id="modal-carreras-registro" class="modal-carreras-overlay" style="display: none;" onclick="if(event.target === this) cerrarModalCarreras()">
    <div class="modal-carreras-content">
      <div class="modal-carreras-header">
        <h3><i class="bx bx-graduation"></i> Seleccionar Carrera</h3>
        <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalCarreras()" title="Cerrar">&times;</button>
      </div>

      <p class="modal-carreras-subtitulo">
        Selecciona tu carrera oficial en la Universidad Marítima del Caribe (UMC):
      </p>

      <div class="grid-carreras-opciones">
        <?php foreach ($carrerasDisponibles as $cod => $info): ?>
          <div class="card-carrera-item <?= ($cod === $carreraActual) ? 'activa' : '' ?>" 
               data-codigo="<?= esc($cod) ?>" 
               data-nombre="<?= esc($info['nombre']) ?>"
               onclick="seleccionarOpcionCarrera('<?= esc($cod) ?>', '<?= esc($info['nombre']) ?>')">
            <div class="carrera-icon">
              <i class="bx <?= esc($info['icono']) ?>"></i>
            </div>
            <div class="carrera-card-textos">
              <strong><?= esc($info['nombre']) ?></strong>
              <span><?= esc($cod) ?> &bull; <?= esc($info['escuela']) ?></span>
            </div>
            <div class="carrera-check">
              <i class="bx <?= ($cod === $carreraActual) ? 'bxs-check-circle' : 'bx-circle' ?>"></i>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="modal-carreras-footer">
        <button type="button" class="btn-cancelar-modal" onclick="cerrarModalCarreras()">Cancelar</button>
        <button type="button" class="btn-confirmar-carrera" onclick="confirmarCarreraSeleccionada()"><i class="bx bx-check"></i> Confirmar Selección</button>
      </div>
    </div>
  </div>

  <script src="<?= base_url('js/main.js?v=11')?>"></script>
  <script src="<?= base_url('js/registro.js?v=11')?>"></script>
</body>
</html>
