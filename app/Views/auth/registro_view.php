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

// CAPTURA ÚNICA DE ERRORES EN FLASH (para no perderlos en CodeIgniter)
$erroresFlash = session()->getFlashdata('errores') ?? [];
$tieneArchivoEnSesion = session()->has('temp_registro_pdf') && file_exists((string)session()->get('temp_registro_pdf'));
$nombreArchivoEnSesion = session()->get('temp_registro_pdf_nombre') ?? 'Registro_Inscripcion_UMC.pdf';

// Determinar si debemos saltar directo al Paso 2
$irAPaso2 = false;
if (!empty($erroresFlash) && is_array($erroresFlash)) {
    $camposPaso1 = ['primer_nombre', 'apellidos', 'correo', 'telefono', 'carrera', 'carnet', 'planilla_inscripcion'];
    $erroresPaso1 = array_intersect_key($erroresFlash, array_flip($camposPaso1));
    // Si no hubo errores en el paso 1 y el usuario ya envió datos, mantenerlo en el paso 2
    if (empty($erroresPaso1) && old('primer_nombre')) {
        $irAPaso2 = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - LAY-8</title>

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
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=' . time())?>">
  <link rel="stylesheet" href="<?= base_url('css/registro.css?v=' . time())?>">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body data-tema-default="light">
  <div class="top-header-registro">

    <div>
      <div class="titulos-inline">
        <h2 class="form-titulo">Registro</h2>
        <span class="form-subtitulo-inline">Crea tu cuenta</span>
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

  <div class="card-global"> 

    <?php if(isset($errores)): ?>
      <div class="error-msg">
        <?= is_object($errores) ? $errores->listErrors() : (is_array($errores) ? implode('<br>', $errores) : esc($errores)) ?>
      </div>
    <?php endif;?>

    <?php if(!empty($erroresFlash)): ?>
      <div class="error-msg">
        <?php foreach((array)$erroresFlash as $err): ?>
          <div>&bull; <?= esc($err) ?></div>
        <?php endforeach; ?>
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
          <input type="hidden" id="tiene_archivo_previo" value="<?= $tieneArchivoEnSesion ? '1' : '0' ?>">
          <input type="file" name="planilla_inscripcion" id="input_planilla_inscripcion" accept=".pdf" class="input-global" <?= ($tieneArchivoEnSesion || old('es_profesor') === 'si') ? '' : 'required' ?>>

          <!-- Feedback visual de archivo cargado/seleccionado -->
          <div id="archivo-feedback-inscripcion" class="archivo-feedback-box" style="display: <?= $tieneArchivoEnSesion ? 'flex' : 'none' ?>;">
            <div class="archivo-feedback-info">
              <i class="bx bxs-file-pdf archivo-pdf-ico"></i>
              <div class="archivo-feedback-textos">
                <span class="archivo-nombre" id="nombre_archivo_inscripcion"><?= esc($nombreArchivoEnSesion) ?></span>
                <span class="archivo-tamano" id="tamano_archivo_inscripcion"><?= $tieneArchivoEnSesion ? 'Cargado y validado en sesión' : 'Documento listo' ?></span>
              </div>
            </div>
            <div class="archivo-feedback-estado">
              <span class="badge-archivo-ok"><i class="bx bx-check-circle"></i> Archivo listo</span>
              <button type="button" class="btn-quitar-archivo" onclick="removerArchivoInscripcion()" title="Quitar o cambiar documento">&times;</button>
            </div>
          </div>
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

        <!-- Casilla obligatoria de Términos y Condiciones con Modal Overlay -->
        <div class="form-row" style="margin-top: 12px; margin-bottom: 22px;">
          <label class="checkbox-terminos-label" style="display: flex; align-items: flex-start; gap: 10px; font-size: 0.84rem; color: #475569; cursor: pointer; line-height: 1.45;">
            <input type="checkbox" name="terminos" id="terminos" value="1" required style="width: 18px; height: 18px; margin-top: 2px; accent-color: #1e3a8a; cursor: pointer; flex-shrink: 0;" <?= old('terminos') ? 'checked' : '' ?>>
            <span>
              He leído y acepto los <a href="javascript:void(0)" onclick="abrirModalTerminos()" style="color: #1e3a8a; font-weight: 700; text-decoration: underline;">Términos, Condiciones de Uso y Políticas de Privacidad</a> de LAY-8.
            </span>
          </label>
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

  <!-- Modal de Términos y Condiciones dentro del Registro -->
  <div id="modal-terminos-registro" class="modal-terminos-overlay" style="display: none;" onclick="if(event.target === this) cerrarModalTerminos()">
    <div class="modal-terminos-content">
      <div class="modal-terminos-header">
        <h3><i class="bx bx-shield-quarter"></i> Términos y Condiciones de Uso</h3>
        <button type="button" class="btn-cerrar-terminos" onclick="cerrarModalTerminos()" title="Cerrar">&times;</button>
      </div>

      <div class="modal-terminos-body">
        <h4>1. Naturaleza y Propósito de la Plataforma</h4>
        <p>
          LAY-8 es una plataforma tecnológica desarrollada exclusivamente con fines de apoyo académico, formativo y de geolocalización dentro del campus de la <strong>Universidad Marítima del Caribe (UMC)</strong>.
        </p>

        <h4>2. Verificación Documental y Cero Tolerancia al Fraude</h4>
        <p>
          El registro como estudiante requiere la carga del Registro de Inscripción emitido por el sistema oficial <code>campus.umc.edu.ve</code>. La falsificación o alteración de datos documentales acarreará sanciones conforme al reglamento académico disciplinario de la UMC.
        </p>

        <h4>3. Confidencialidad y Uso de Información</h4>
        <p>
          Toda información proporcionada (nombres, matrículas, horarios y calificaciones) se resguarda bajo estrictos principios de privacidad y es utilizada exclusivamente para sincronizar tu jornada académica y las aulas del campus.
        </p>

        <h4>4. Convivencia y Recursos Colaborativos</h4>
        <p>
          Las publicaciones en el muro informativo y aportes compartidos en la biblioteca virtual deben regirse bajo principios de respeto mutuo, veracidad y rigor académico.
        </p>
      </div>

      <div class="modal-terminos-footer">
        <button type="button" onclick="cerrarModalTerminos()" style="background: none; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 8px; color: #475569; font-weight: 600; cursor: pointer;">Cerrar</button>
        <button type="button" class="btn-aceptar-terminos" onclick="aceptarTerminosModal()"><i class="bx bx-check"></i> Aceptar y Continuar</button>
      </div>
    </div>
  </div>

  <script src="<?= base_url('js/main.js?v=' . time())?>"></script>
  <script src="<?= base_url('js/registro.js?v=' . time())?>"></script>

  <?php if ($irAPaso2): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const p1 = document.getElementById('paso1');
        const p2 = document.getElementById('paso2');
        const ind1 = document.getElementById('indicator-1');
        const ind2 = document.getElementById('indicator-2');
        if (p1 && p2 && ind1 && ind2) {
          p1.style.display = 'none';
          p2.style.display = 'block';
          ind1.classList.remove('activo');
          ind2.classList.add('activo');
        }
      });
    </script>
  <?php endif; ?>
</body>
</html>