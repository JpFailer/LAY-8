<?php

  // Detección de tema oscuro desde el servidor vía cookie para CERO destellos blancos (Zero-FOUC)

  $temaCookie = $_COOKIE['lay8_theme'] ?? null;

  $esOscuroServidor = ($temaCookie === 'dark');

?>

<!DOCTYPE html>

<html lang="es" class="<?= $esOscuroServidor ? 'dark-mode' : '' ?>">

<head>

  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>LAY-8</title>

  <!-- Script inline antividrios/anti-flicker de Modo Oscuro (ejecución síncrona en 0ms antes del render) -->

  <script>

    (function() {

      try {

        var tema = localStorage.getItem('lay8_theme');

        if (tema === 'dark' || (!tema && document.documentElement.classList.contains('dark-mode'))) {

          document.documentElement.classList.add('dark-mode');

        } else if (tema === 'light') {

          document.documentElement.classList.remove('dark-mode');

        }

      } catch(e) {}

    })();

  </script>

  <!-- Estilos críticos inmediatos para eliminar destellos blancos durante la navegación -->

  <style>

    html.dark-mode {

      background-color: #0b1120 !important;

      color-scheme: dark;

    }

    html.dark-mode body {

      background: #0b1120 !important;

      color: #e2e8f0 !important;

    }

    html.dark-mode .barra-superior {

      background-color: #0c1427 !important;

    }

    html.dark-mode .content-box {

      background-color: #1e293b !important;

      color: #f1f5f9 !important;

    }

  </style>

  <!-- Precarga de fuentes -->

  <link rel="preconnect" href="https://fonts.googleapis.com">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">

  

  <!-- Stylesheets -->

  <link rel="stylesheet" href="<?= base_url('css/global.css?v=' . time())?>">

  <link rel="stylesheet" href="<?= base_url('css/feed.css?v=' . time())?>">

  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

</head>

<body class="<?= $esOscuroServidor ? 'dark-mode' : '' ?>">

  <!-- Aplicar clase al body inmediatamente al abrirlo, sin esperar a DOMContentLoaded -->

  <script>

    (function() {

      try {

        var tema = localStorage.getItem('lay8_theme');

        if (tema === 'dark' || (!tema && document.body.classList.contains('dark-mode'))) {

          document.body.classList.add('dark-mode');

        } else if (tema === 'light') {

          document.body.classList.remove('dark-mode');

        }

      } catch(e) {}

    })();

  </script>

  <?php

    // Extracción segura de datos de usuario para el perfil y la barra superior

    $sesionId           = session()->get('usuario_id');

    $userNombreCompleto = trim((session()->get('primer_nombre') ?? 'Usuario') . ' ' . (session()->get('apellidos') ?? ''));

    $userNombreUsuario  = session()->get('nombre_usuario') ?? 'usuario';

    $userRol            = session()->get('rol') ?? 'estudiante';

    $userFoto           = session()->get('foto_perfil');

    $userCorreo         = session()->get('correo');

    $carreraSesion      = session()->get('carrera');

    $userCodigoCarrera  = $carreraSesion ?: 'ING-INF';

    $userCarrera        = ($userRol === 'profesor') ? 'Docente UMC' : (in_array($userRol, ['administrador', 'admin']) ? 'Administración UMC' : $userCodigoCarrera);

    $userEstado         = 'Activo';

    $userTotalAportes = 0;

    if ($sesionId) {

        try {

            $dbConn = \Config\Database::connect();

            $rowU = $dbConn->table('usuarios')->select('correo, estado_cuenta, carrera')->where('id', $sesionId)->get()->getRowArray();

            if ($rowU) {

                if (empty($userCorreo)) {

                    $userCorreo = $rowU['correo'] ?? '';

                }

                $userEstado = ucfirst($rowU['estado_cuenta'] ?? 'Activo');

                if (!empty($rowU['carrera'])) {

                    $userCodigoCarrera = $rowU['carrera'];

                    if ($userRol !== 'profesor') {

                        $userCarrera = $rowU['carrera'];

                    }

                }

            }

        } catch (\Throwable $e) {}

        try {

            $userTotalAportes = (new \App\Models\AporteBibliotecaModel())->where('usuario_id', $sesionId)->countAllResults();

        } catch (\Throwable $e) {}

    }

  ?>

  <div class="layout-grid">

    <header class="barra-superior">

      <div class="logo">

        <a href="<?= base_url('dashboard') ?>" style="color:white; text-decoration:none;">

          <h1>LAY-8</h1>

        </a>

      </div>

      <div class="barra-superior-derecha" style="display: flex; align-items: center; gap: 14px;">

        <?php if (in_array($userRol, ['administrador', 'admin'], true)): ?>

          <!-- Botón de Solicitudes Docentes Pendientes (Exclusivo Administrador) -->

          <div class="solicitudes-topbar-wrapper" id="solicitudes-topbar-wrapper">

            <button type="button" class="btn-topbar-docentes" id="btn-topbar-solicitudes" onclick="abrirModalSolicitudesDocentesTop()" title="Revisar Solicitudes de Profesores" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); color: #ffffff; padding: 6px 12px; border-radius: 9999px; cursor: pointer; font-size: 0.84rem; font-weight: 600; transition: all 0.2s ease;">

              <i class="bx bx-user-voice" style="font-size: 1.15rem; color: #fbbf24;"></i>

              <span class="txt-btn-docentes">Docentes</span>

              <span class="badge-docentes-contador" id="badge-docentes-top-count" style="display: none; background: #ef4444; color: white; border-radius: 9999px; font-size: 0.72rem; padding: 2px 7px; font-weight: 700; line-height: 1;">0</span>

            </button>

          </div>

        <?php endif; ?>

        <!-- Campana de Notificaciones -->

        <div class="notif-topbar-wrapper" id="notif-topbar-wrapper">

          <button type="button" class="btn-campana-notif" id="btn-campana-notif" onclick="toggleDropdownNotificaciones(event)" title="Notificaciones">

            <i class="bx bx-bell"></i>

            <span class="badge-notif-contador" id="badge-notif-count" style="display: none;">0</span>

          </button>

          <!-- Dropdown Flotante de Notificaciones -->

          <div class="dropdown-notificaciones-box" id="dropdown-notif-box" style="display: none;">

            <div class="notif-box-header">

              <div class="notif-box-title">

                <i class="bx bx-bell"></i>

                <span>Notificaciones</span>

              </div>

              <button type="button" class="btn-marcar-leidas-top" onclick="marcarTodasNotificacionesLeidas(event)">

                Marcar leídas

              </button>

            </div>

            <div class="notif-box-body" id="notif-items-lista">

              <div class="notif-vacio">

                <i class="bx bx-check-double"></i>

                <p>Estás al día con tus notificaciones.</p>

              </div>

            </div>

          </div>

        </div>

        <!-- Contenedor del perfil clickeable -->

        <div class="perfil-contenedor user-menu-container" onclick="toggleMenu()">

        <span class="nombre-usuario">

          <?= esc($userNombreCompleto) ?>

          <?php if (in_array($userRol, ['administrador', 'admin'], true)): ?>

            <i class="bx bxs-check-circle" style="color: #60a5fa; font-size: 1.05rem; vertical-align: middle; margin-left: 2px;" title="Administrador Verificado"></i>

          <?php elseif ($userRol === 'profesor'): ?>

            <i class="bx bxs-check-circle" style="color: #34d399; font-size: 1.05rem; vertical-align: middle; margin-left: 2px;" title="Profesor Verificado"></i>

          <?php endif; ?>

        </span>

        <div class="avatar-trigger">

          <?php if ($userFoto): ?>

            <img src="<?= base_url($userFoto) ?>" alt="Foto de perfil">

          <?php else: ?>

            <img src="<?= base_url('img/usuario.png') ?>" alt="Foto de perfil">

          <?php endif; ?>

        </div>

        <!-- Menú desplegable oculto -->

        <div id="dropdown-perfil" class="dropdown-menu">

          <?php if (in_array($userRol, ['administrador', 'admin'], true)): ?>

            <a href="<?= base_url('admin') ?>" class="dropdown-item" style="color: #1e3a8a; font-weight: 700;">

              <i class="bx bx-shield-quarter" style="margin-right: 6px;"></i> Panel Admin

            </a>

          <?php endif; ?>

          <a href="<?= base_url('perfil') ?>" class="dropdown-item" onclick="abrirModalPerfil(event)">

            <i class="bx bx-user" style="margin-right: 6px;"></i> Perfil

          </a>

          <a href="<?= base_url('ajustes') ?>" class="dropdown-item" onclick="abrirModalAjustes(event)">

            <i class="bx bx-cog" style="margin-right: 6px;"></i> Ajustes

          </a>

        </div>

      </div>

      </div>

    </header>

    <aside class="sidebar">

      <?php $segmentoActual = service('uri')->getSegment(1); ?>

      <nav class="menu-links">

        <a href="<?= base_url('/dashboard'); ?>" class="<?= ($segmentoActual === 'dashboard' || empty($segmentoActual)) ? 'active' : '' ?>">

          <i class="bx bx-home-alt" style="margin-right: 8px;"></i> Inicio

        </a>

        <a href="<?= base_url('/mapa'); ?>" class="<?= ($segmentoActual === 'mapa') ? 'active' : '' ?>">

          <i class="bx bx-map-pin" style="margin-right: 8px;"></i> Mapa UMC

        </a>

        <a href="<?= base_url('/biblioteca'); ?>" class="<?= ($segmentoActual === 'biblioteca') ? 'active' : '' ?>">

          <i class="bx bx-book-reader" style="margin-right: 8px;"></i> Biblioteca

        </a>

        <a href="<?= base_url('/aulas'); ?>" class="<?= ($segmentoActual === 'aulas') ? 'active' : '' ?>">

          <i class="bx bx-door-open" style="margin-right: 8px;"></i> Aulas

        </a>

        <?php if (in_array($userRol, ['administrador', 'admin'], true)): ?>

          <a href="<?= base_url('/admin'); ?>" class="<?= ($segmentoActual === 'admin') ? 'active' : '' ?>" style="color: #3b82f6; font-weight: 700;">

            <i class="bx bx-shield-quarter" style="margin-right: 8px;"></i> Panel Admin

          </a>

        <?php endif; ?>

      </nav>

      <div class="sidebar-footer">

        <a href="javascript:void(0)" onclick="confirmarCerrarSesion(event)" class="logout-btn" title="Cerrar Sesión">

          <i class="bx bx-log-out"></i>

          <span>Cerrar sesión</span>

        </a>

      </div>

    </aside>

    <main class="content-area">

        <div class="content-box">

          <button id="btn-expandir" class="btn-expandir-layout" onclick="toggleFocus()" title="Modo enfoque / Pantalla completa">

            <i id="focus-icon" class="bx bx-expand focus-icon"></i>

          </button>

          <?= $this->renderSection('content') ?>

        </div>

    </main>

  </div>

  <!-- Modal Ventana Flotante de Perfil (Diseño de Referencia) -->

  <div id="modal-perfil" class="modal-perfil-overlay" onclick="if(event.target === this) cerrarModalPerfil()">

    <div class="modal-perfil-content">

      <button type="button" class="btn-cerrar-modal-perfil" onclick="cerrarModalPerfil()" title="Cerrar">&times;</button>

      

      <!-- Banner decorativo superior -->

      <div class="perfil-card-banner"></div>

      <!-- Zona de Avatar y Datos Principales -->

      <div class="perfil-card-inner">

        <div class="perfil-user-header">

          <div class="perfil-avatar-wrapper">

            <?php if ($userFoto): ?>

              <img src="<?= base_url($userFoto) ?>" class="perfil-card-avatar" alt="Foto de perfil">

            <?php else: ?>

              <img src="<?= base_url('img/usuario.png') ?>" class="perfil-card-avatar" alt="Foto de perfil">

            <?php endif; ?>

          </div>

          <div class="perfil-user-meta">

            <div class="perfil-name-badge-row">

              <h2 class="perfil-user-name">

                <?= esc($userNombreCompleto) ?>

                <?php if (in_array($userRol, ['administrador', 'admin'], true)): ?>

                  <i class="bx bxs-check-circle" style="color: #2563eb; font-size: 1.25rem; vertical-align: middle; margin-left: 4px;" title="Administrador Verificado"></i>

                <?php elseif ($userRol === 'profesor'): ?>

                  <i class="bx bxs-check-circle" style="color: #10b981; font-size: 1.25rem; vertical-align: middle; margin-left: 4px;" title="Profesor Verificado"></i>

                <?php endif; ?>

              </h2>

              <span class="perfil-pill-badge"><?= ucfirst(esc($userRol)) ?></span>

            </div>

            <div class="perfil-user-handle">@<?= esc($userNombreUsuario) ?></div>

          </div>

        </div>

        <!-- Línea azul divisoria continua -->

        <div class="perfil-blue-divider"></div>

        <!-- Grilla de Información 2x2 -->

        <div class="perfil-info-grid">

          <div class="perfil-grid-cell">

            <span class="grid-label">Carrera</span>

            <span class="grid-value"><?= esc($userCarrera) ?></span>

          </div>

          <div class="perfil-grid-cell">

            <span class="grid-label">Contacto</span>

            <span class="grid-value" title="<?= esc($userCorreo) ?>"><?= esc($userCorreo ?: 'No registrado') ?></span>

          </div>

          <div class="perfil-grid-cell">

            <span class="grid-label">Aportes</span>

            <span class="grid-value"><?= (int)$userTotalAportes ?> <?= ($userTotalAportes == 1) ? 'recurso' : 'recursos' ?></span>

          </div>

          <div class="perfil-grid-cell">

            <span class="grid-label">Estado</span>

            <span class="grid-value"><i class="bx bxs-check-circle" style="color:#10b981; font-size:1.05rem; vertical-align:middle;"></i> <?= esc($userEstado) ?></span>

          </div>

        </div>

        <!-- Pie de tarjeta con acción de editar perfil -->

        <div class="perfil-card-bottom">

          <button type="button" class="btn-editar-perfil-trigger" onclick="abrirModalEditarPerfil()">

            <i class="bx bx-edit-alt"></i> Editar perfil

          </button>

        </div>

      </div>

    </div>

  </div>

  <!-- Modal Interactivo de Ajustes (Reestructurado según Revisión Haryerit) -->

  <div id="modal-ajustes" class="modal-ajustes-overlay" onclick="if(event.target === this) cerrarModalAjustes()">

    <div class="modal-ajustes-content" style="max-width: 650px; max-height: calc(100vh - 40px); overflow-y: auto;">

      <div class="modal-ajustes-header">

        <h3><i class="bx bx-cog"></i> Ajustes de Cuenta y Sistema</h3>

        <button type="button" class="btn-cerrar-modal" onclick="cerrarModalAjustes()" title="Cerrar">&times;</button>

      </div>

      <div class="modal-ajustes-body" style="padding: 20px 24px;">

        

        <!-- SECCIÓN 1: AJUSTES DE INTERFAZ -->

        <div style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">

          <h4 style="margin: 0 0 12px 0; color: #1e3a8a; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">

            <i class="bx bx-palette"></i> Ajustes de Interfaz

          </h4>

          <div class="ajuste-item" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;">

            <div class="ajuste-info">

              <div class="ajuste-titulo" style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">

                <i class="bx bx-moon" id="icono-tema-visual"></i> Modo Oscuro

              </div>

              <div class="ajuste-descripcion" style="font-size: 0.83rem; color: #64748b;">

                Reduce el brillo y optimiza la paleta visual para navegación nocturna en el campus.

              </div>

            </div>

            <div class="ajuste-control">

              <label class="switch-toggle" for="toggle-dark-mode">

                <input type="checkbox" id="toggle-dark-mode" class="toggle-dark-mode-input">

                <span class="slider-toggle"></span>

              </label>

            </div>

          </div>

        </div>

        <!-- SECCIÓN 2: AJUSTES DE CUENTA Y SEGURIDAD -->

        <div>

          <h4 style="margin: 0 0 12px 0; color: #1e3a8a; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">

            <i class="bx bx-user-check"></i> Datos de la Cuenta y Carrera

          </h4>

          <form action="<?= base_url('perfil/actualizar') ?>" method="post" enctype="multipart/form-data" class="modal-editar-form">

            <?= csrf_field() ?>

            <input type="hidden" name="carrera" id="input-editar-carrera" value="<?= esc($userCodigoCarrera) ?>">

            <input type="hidden" id="carrera-inicial-perfil" value="<?= esc($userCodigoCarrera) ?>">

            <!-- Selector de Carrera UMC -->

            <div class="form-group-editar" style="margin-bottom: 16px;">

              <label class="form-label-editar"><i class="bx bx-graduation"></i> Carrera Universitaria (UMC)</label>

              <div class="grid-carreras-selector" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 10px; margin-top: 8px;">

                <?php 

                  $carrerasUMC = [

                      'ING-INF' => ['nombre' => 'Ingeniería en Informática', 'escuela' => 'Escuela de Informática', 'icono' => 'bx-code-alt'],

                      'ING-MAR' => ['nombre' => 'Ingeniería Marítima', 'escuela' => 'Escuela de Náutica', 'icono' => 'bx-anchor'],

                      'ING-AMB' => ['nombre' => 'Ingeniería Ambiental', 'escuela' => 'Escuela de Ciencias Aplicadas', 'icono' => 'bx-leaf'],

                      'ADM'     => ['nombre' => 'Licenciatura en Administración', 'escuela' => 'Escuela de Ciencias Sociales', 'icono' => 'bx-briefcase-alt-2'],

                      'TUR'     => ['nombre' => 'Licenciatura en Turismo', 'escuela' => 'Escuela de Turismo', 'icono' => 'bx-compass'],

                  ];

                ?>

                <?php foreach ($carrerasUMC as $codigoC => $infoC): ?>

                  <div class="carrera-card-opcion <?= ($codigoC === $userCodigoCarrera) ? 'seleccionada' : '' ?>"

                       data-codigo="<?= esc($codigoC) ?>"

                       onclick="seleccionarCarreraEditar('<?= esc($codigoC) ?>')">

                    <div class="carrera-icon-circle">

                      <i class="bx <?= esc($infoC['icono']) ?>"></i>

                    </div>

                    <div class="carrera-info-box">

                      <strong><?= esc($infoC['nombre']) ?></strong>

                      <span><?= esc($codigoC) ?> &bull; <?= esc($infoC['escuela']) ?></span>

                    </div>

                    <div class="check-radio">

                      <i class="bx <?= ($codigoC === $userCodigoCarrera) ? 'bxs-check-circle' : 'bx-circle' ?>"></i>

                    </div>

                  </div>

                <?php endforeach; ?>

              </div>

            </div>

            <!-- Certificación Oficial UMC para Cambio de Carrera -->

            <div class="editar-seguridad-box" style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 10px; padding: 14px; margin-bottom: 16px;">

              <label class="form-label-editar label-seguridad" style="color: #1e40af; font-weight: 700;">

                <i class="bx bx-shield-quarter"></i> Certificación UMC para Cambio de Carrera

              </label>

              <p class="form-sublabel-editar" style="font-size: 0.8rem; margin: 4px 0 10px 0; color: #3b82f6;">

                Si cambias de carrera, adjunta tu <strong>Registro de Inscripción oficial (PDF)</strong> emitido por <code>campus.umc.edu.ve</code>:

              </p>

              <input type="file" name="planilla_inscripcion" accept=".pdf" class="input-global" style="background: white; margin-bottom: 12px;">

              <label class="form-label-editar" style="font-size: 0.82rem; font-weight: 600; color: #1e3a8a;"><i class="bx bx-lock-alt"></i> Contraseña actual (solo para confirmar cambios de carrera):</label>

              <input type="password" name="confirmar_contrasena" id="input-pass-perfil" class="input-global" placeholder="Tu contraseña actual">

            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">

              <button type="submit" class="btn-guardar-perfil" style="padding: 10px 20px; font-weight: 700; border-radius: 8px;"><i class="bx bx-save"></i> Guardar Ajustes de Cuenta</button>

            </div>

          </form>

        </div>

      </div>

      <div class="modal-ajustes-footer">

        <button type="button" class="btn-modal-guardar" onclick="cerrarModalAjustes()">Listo</button>

      </div>

    </div>

  </div>

  <!-- Modal Interactivo de Editar Perfil (Solo Foto y Apariencia Pública - Revisión Haryerit) -->

  <div id="modal-editar-perfil" class="modal-carreras-overlay" style="display: none;" onclick="if(event.target === this) cerrarModalEditarPerfil()">

    <div class="modal-editar-perfil-content" style="max-width: 440px; border-radius: 18px;">

      <div class="modal-editar-header">

        <h3><i class="bx bx-camera"></i> Foto de Perfil</h3>

        <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalEditarPerfil()" title="Cerrar">&times;</button>

      </div>

      <form action="<?= base_url('perfil/actualizar') ?>" method="post" enctype="multipart/form-data" class="modal-editar-form">

        <?= csrf_field() ?>

        <div class="modal-editar-body" style="padding: 26px 20px; text-align: center;">

          <p class="form-sublabel-editar" style="font-size: 0.88rem; color: #64748b; margin-bottom: 18px;">

            Personaliza la imagen visible de tu cuenta para la comunidad universitaria de LAY-8:

          </p>

          <div style="display: inline-block; position: relative; margin-bottom: 16px;">

            <img src="<?= !empty($userFoto) ? base_url($userFoto) : base_url('img/usuario.png') ?>" id="preview-foto-modal" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #3b82f6; box-shadow: 0 6px 16px rgba(0,0,0,0.15);" alt="Foto">

          </div>

          <div>

            <input type="file" name="foto" id="input-foto-editar" accept="image/png, image/jpeg, image/jpg" style="display:none;" onchange="previsualizarFotoPerfil(this)">

            <button type="button" class="btn-subir-cta" onclick="document.getElementById('input-foto-editar').click()" style="background: #2563eb; display: inline-flex;">

              <i class="bx bx-upload"></i> Subir nueva foto

            </button>

          </div>

        </div>

        <div class="modal-editar-footer" style="padding: 14px 20px;">

          <button type="button" class="btn-cancelar-modal" onclick="cerrarModalEditarPerfil()">Cancelar</button>

          <button type="submit" class="btn-guardar-perfil"><i class="bx bx-save"></i> Guardar Foto</button>

        </div>

      </form>

    </div>

  </div>

  <!-- Modal Confirmación de Cerrar Sesión (Revisión Haryerit) -->

  <div id="modal-confirmar-logout" class="modal-carreras-overlay" style="display: none; padding: 15px; box-sizing: border-box; z-index: 100000;" onclick="if(event.target === this) cerrarModalLogout()">

    <div class="modal-editar-perfil-content" style="max-width: 420px; width: 100%; border-radius: 18px; text-align: center; padding: 25px 22px;">

      <div style="width: 58px; height: 58px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 12px auto;">

        <i class="bx bx-log-out-circle"></i>

      </div>

      <h3 style="margin: 0 0 6px 0; color: #0f172a; font-size: 1.25rem; font-weight: 700;">¿Cerrar Sesión?</h3>

      <p style="margin: 0 0 22px 0; font-size: 0.88rem; color: #64748b; line-height: 1.5;">

        ¿Estás seguro de que deseas salir de tu sesión actual en <strong>LAY-8</strong>?

      </p>

      <div style="display: flex; gap: 10px; justify-content: center;">

        <button type="button" class="btn-cancelar-modal" onclick="cerrarModalLogout()" style="flex: 1; padding: 10px; border-radius: 8px;">Cancelar</button>

        <a href="<?= base_url('logout') ?>" style="flex: 1; background: #ef4444; color: white; padding: 10px; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">

          <i class="bx bx-log-out"></i> Sí, Salir

        </a>

      </div>

    </div>

  </div>

  <div style="display:none;" id="global-csrf-container"><?= csrf_field() ?></div>

  <script src="<?= base_url('js/main.js?v=11')?>"></script>

  <!-- Scripts de Notificaciones en Tiempo Real -->

  <script>

    const BASE_URL_NOTIF = '<?= base_url() ?>/';

    const CSRF_TOKEN_NOTIF = '<?= csrf_token() ?>';

    const CSRF_HEADER_NOTIF = '<?= csrf_header() ?>';

    function getNotifHeaders() {

      const headers = { 'X-Requested-With': 'XMLHttpRequest' };

      const csrfInput = document.querySelector('input[name="' + CSRF_TOKEN_NOTIF + '"]');

      if (csrfInput && csrfInput.value) {

        headers[CSRF_HEADER_NOTIF] = csrfInput.value;

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

      // Cerrar menú de perfil si está abierto

      const menuPerfil = document.getElementById('dropdown-perfil');

      if (menuPerfil) menuPerfil.classList.remove('mostrar');

      if (isHidden) {

        cargarNotificacionesTop();

      }

    }

    function cargarNotificacionesTop() {

      fetch(BASE_URL_NOTIF + 'api/notificaciones')

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

            const noLeidaClass = (parseInt(n.leido) === 0) ? 'no-leida' : '';

            const enlace = n.enlace_accion ? (BASE_URL_NOTIF + n.enlace_accion) : '#';

            html += `

              <a href="${enlace}" class="notif-item-row ${noLeidaClass}" onclick="marcarNotifLeida(${n.id})">

                <div class="notif-item-icon ${iconClass}">

                  <i class="bx ${iconBx}"></i>

                </div>

                <div class="notif-item-content">

                  <span class="notif-item-title">${n.titulo || 'Notificación'}</span>

                  <span class="notif-item-msg">${n.mensaje || ''}</span>

                  <span class="notif-item-time">${n.created_at || ''}</span>

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

      fetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-leida/' + id, { 

        method: 'POST',

        headers: getNotifHeaders()

      })

        .then(() => {

          cargarConteoNotificaciones();

        })

        .catch(() => {});

    }

    function marcarTodasNotificacionesLeidas(e) {

      if (e) e.stopPropagation();

      fetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-todas-leidas', { 

        method: 'POST',

        headers: getNotifHeaders()

      })

        .then(r => r.json())

        .then(() => {

          actualizarBadgeConteo(0);

          cargarNotificacionesTop();

        })

        .catch(err => console.error(err));

    }

    function cargarConteoNotificaciones() {

      fetch(BASE_URL_NOTIF + 'api/notificaciones/conteo')

        .then(r => r.json())

        .then(data => {

          if (data && typeof data.no_leidas !== 'undefined') {

            actualizarBadgeConteo(data.no_leidas);

          }

        })

        .catch(() => {});

    }

    // Cerrar al hacer clic fuera

    document.addEventListener('click', function(e) {

      const box = document.getElementById('dropdown-notif-box');

      const btn = document.getElementById('btn-campana-notif');

      if (box && btn && !box.contains(e.target) && !btn.contains(e.target)) {

        box.style.display = 'none';

      }

    });

    document.addEventListener('DOMContentLoaded', function() {

      cargarConteoNotificaciones();

      setInterval(cargarConteoNotificaciones, 30000); // Polling cada 30s

      <?php if (in_array($userRol, ['administrador', 'admin'], true)): ?>

        cargarSolicitudesDocentesTop();

        setInterval(cargarSolicitudesDocentesTop, 30000); // Polling solicitudes admin

      <?php endif; ?>

    });

    <?php if (in_array($userRol, ['administrador', 'admin'], true)): ?>

    let docenteSeleccionadoRechazoId = null;

    function cargarSolicitudesDocentesTop() {

      fetch('<?= base_url('api/admin/solicitudes-profesores') ?>', {

        headers: { 'X-Requested-With': 'XMLHttpRequest' }

      })

      .then(r => r.json())

      .then(data => {

        if (!data || !data.success) return;

        const total = data.total || 0;

        const badge = document.getElementById('badge-docentes-top-count');

        if (badge) {

          badge.textContent = total;

          badge.style.display = total > 0 ? 'inline-block' : 'none';

        }

        renderizarSolicitudesDocentesModal(data.solicitudes || []);

      })

      .catch(() => {});

    }

    function renderizarSolicitudesDocentesModal(solicitudes) {

      const cont = document.getElementById('contenedor-lista-solicitudes-top');

      if (!cont) return;

      if (!solicitudes || solicitudes.length === 0) {

        cont.innerHTML = `

          <div style="text-align: center; padding: 35px 20px; color: #64748b;">

            <i class="bx bx-check-double" style="font-size: 2.8rem; color: #10b981; margin-bottom: 8px;"></i>

            <h4 style="margin: 0 0 4px 0; color: #0f172a; font-size: 1.1rem;">¡Al día con las validaciones!</h4>

            <p style="margin: 0; font-size: 0.88rem;">No hay profesores pendientes de aprobación en este momento.</p>

          </div>

        `;

        return;

      }

      let html = '<div style="display: flex; flex-direction: column; gap: 14px;">';

      solicitudes.forEach(s => {

        const nombreCompleto = (s.primer_nombre + ' ' + (s.segundo_nombre ? s.segundo_nombre + ' ' : '') + s.apellidos).trim();

        const cedula = s.cedula || 'No registrada';

        const carnetUrl = s.ruta_carnet ? ('<?= base_url() ?>' + s.ruta_carnet) : null;

        html += `

          <div class="card-solicitud-docente-item" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">

            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">

              <div>

                <div style="font-weight: 700; color: #0f172a; font-size: 0.98rem; display: flex; align-items: center; gap: 6px;">

                  <i class="bx bx-user" style="color: #2563eb;"></i> ${nombreCompleto}

                  <span style="font-size: 0.8rem; color: #64748b; font-weight: normal;">(@${s.nombre_usuario})</span>

                </div>

                <div style="font-size: 0.84rem; color: #334155; margin-top: 4px; display: flex; gap: 14px; flex-wrap: wrap;">

                  <span><strong>C.I:</strong> <code style="background: #eff6ff; color: #1e40af; padding: 2px 6px; border-radius: 4px;">${cedula}</code></span>

                  <span><strong>Correo:</strong> ${s.correo}</span>

                </div>

              </div>

              

              <div>

                ${carnetUrl ? `

                  <a href="${carnetUrl}" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 5px 10px; border-radius: 6px; font-size: 0.82rem; font-weight: 600; text-decoration: none;">

                    <i class="bx bx-file-blank" style="color: #dc2626; font-size: 1.1rem;"></i> Ver Comprobante

                  </a>

                ` : `<span style="font-size: 0.8rem; color: #94a3b8;">Sin comprobante adjunto</span>`}

              </div>

            </div>

            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">

              <button type="button" onclick="aprobarDocenteDesdeModal(${s.id}, '${nombreCompleto.replace(/'/g, "\'")}')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.83rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">

                <i class="bx bx-check"></i> Aprobar Profesor

              </button>

              <button type="button" onclick="abrirModalRechazoDocente(${s.id}, '${nombreCompleto.replace(/'/g, "\'")}')" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.83rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">

                <i class="bx bx-x"></i> Negar Solicitud

              </button>

            </div>

          </div>

        `;

      });

      html += '</div>';

      cont.innerHTML = html;

    }

    function abrirModalSolicitudesDocentesTop() {

      const modal = document.getElementById('modal-solicitudes-docentes-top');

      if (modal) {

        modal.style.display = 'flex';

        cargarSolicitudesDocentesTop();

      }

    }

    function cerrarModalSolicitudesDocentesTop() {

      const modal = document.getElementById('modal-solicitudes-docentes-top');

      if (modal) modal.style.display = 'none';

    }

    function aprobarDocenteDesdeModal(id, nombre) {

      if (!confirm('¿Confirmas aprobar al docente ' + nombre + ' como Profesor Oficial de la UMC?')) return;

      fetch('<?= base_url('api/admin/aprobar-profesor') ?>/' + id, {

        method: 'POST',

        headers: { 'X-Requested-With': 'XMLHttpRequest' }

      })

      .then(r => r.json())

      .then(data => {

        if (data.success) {

          alert(data.mensaje || 'Profesor aprobado exitosamente.');

          cargarSolicitudesDocentesTop();

        } else {

          alert(data.error || 'No se pudo aprobar la solicitud.');

        }

      })

      .catch(() => alert('Error de conexión al procesar la aprobación.'));

    }

    function abrirModalRechazoDocente(id, nombre) {

      docenteSeleccionadoRechazoId = id;

      const txt = document.getElementById('texto-docente-a-rechazar');

      if (txt) txt.textContent = 'Solicitante: ' + nombre;

      const input = document.getElementById('input-motivo-rechazo');

      if (input) input.value = '';

      const m = document.getElementById('modal-motivo-rechazo-docente');

      if (m) m.style.display = 'flex';

    }

    function cerrarModalRechazoDocente() {

      const m = document.getElementById('modal-motivo-rechazo-docente');

      if (m) m.style.display = 'none';

    }

    function confirmarRechazoDocenteDesdeModal() {

      if (!docenteSeleccionadoRechazoId) return;

      const motivo = document.getElementById('input-motivo-rechazo').value.trim();

      const formData = new FormData();

      if (motivo) formData.append('motivo', motivo);

      fetch('<?= base_url('api/admin/denegar-profesor') ?>/' + docenteSeleccionadoRechazoId, {

        method: 'POST',

        headers: { 'X-Requested-With': 'XMLHttpRequest' },

        body: formData

      })

      .then(r => r.json())

      .then(data => {

        cerrarModalRechazoDocente();

        if (data.success) {

          alert('Solicitud rechazada y notificada al usuario.');

          cargarSolicitudesDocentesTop();

        } else {

          alert(data.error || 'No se pudo rechazar la solicitud.');

        }

      })

      .catch(() => alert('Error al enviar rechazo al servidor.'));

    }

    <?php endif; ?>

  </script>

  <?php if (in_array($userRol, ['administrador', 'admin'], true)): ?>

  <!-- Modal Superior: Solicitudes de Profesores Pendientes (LAY-8) -->

  <div id="modal-solicitudes-docentes-top" class="modal-carreras-overlay" style="display: none; padding: 15px; box-sizing: border-box; z-index: 99999;">

    <div class="modal-editar-perfil-content" style="max-width: 680px; width: 100%; max-height: calc(100vh - 40px); display: flex; flex-direction: column; overflow: hidden; border-radius: 18px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);">

      <div class="modal-editar-header" style="padding: 16px 22px; background: #1e3a8a; color: white; display: flex; align-items: center; justify-content: space-between;">

        <h3 style="margin: 0; font-size: 1.15rem; color: white; display: flex; align-items: center; gap: 8px;">

          <i class="bx bx-user-voice" style="color: #fbbf24; font-size: 1.4rem;"></i> Solicitudes de Registro Docente

        </h3>

        <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalSolicitudesDocentesTop()" style="color: white; font-size: 1.5rem; background: none; border: none; cursor: pointer;">&times;</button>

      </div>

      

      <div style="padding: 14px 22px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 0.85rem; color: #475569;">

        Revisa los datos de identidad (Nombre y Cédula) y el comprobante oficial para aprobar o negar el rol de profesor en el sistema.

      </div>

      <div id="contenedor-lista-solicitudes-top" style="padding: 18px 22px; overflow-y: auto; max-height: 55vh; box-sizing: border-box;">

        <div style="text-align: center; padding: 30px; color: #64748b;">

          <i class="bx bx-loader-alt bx-spin" style="font-size: 2rem; color: #2563eb;"></i>

          <p style="margin-top: 8px;">Cargando solicitudes...</p>

        </div>

      </div>

      <div class="modal-editar-footer" style="padding: 12px 22px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">

        <button type="button" class="btn-cancelar-modal" onclick="cerrarModalSolicitudesDocentesTop()">Cerrar</button>

      </div>

    </div>

  </div>

  <!-- Modal para Motivo de Rechazo Docente -->

  <div id="modal-motivo-rechazo-docente" class="modal-carreras-overlay" style="display: none; padding: 15px; box-sizing: border-box; z-index: 100000;">

    <div class="modal-editar-perfil-content" style="max-width: 460px; width: 100%; border-radius: 16px;">

      <div class="modal-editar-header" style="padding: 14px 20px;">

        <h3 style="color: #dc2626; font-size: 1.1rem; margin: 0; display: flex; align-items: center; gap: 6px;">

          <i class="bx bx-user-x"></i> Denegar Solicitud Docente

        </h3>

        <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalRechazoDocente()">&times;</button>

      </div>

      <div class="modal-editar-body" style="padding: 16px 20px;">

        <p id="texto-docente-a-rechazar" style="font-size: 0.88rem; color: #334155; margin-top: 0; font-weight: 600;"></p>

        <label class="form-label" style="font-size: 0.83rem;">Motivo del rechazo (se enviará por notificación al usuario):</label>

        <textarea id="input-motivo-rechazo" class="input-global" rows="3" placeholder="Ej: El comprobante presentado no es legible o no corresponde al carnet oficial de la UMC."></textarea>

      </div>

      <div class="modal-editar-footer" style="padding: 12px 20px; display: flex; justify-content: flex-end; gap: 8px;">

        <button type="button" class="btn-cancelar-modal" onclick="cerrarModalRechazoDocente()">Cancelar</button>

        <button type="button" class="btn-accion-admin btn-rechazar" onclick="confirmarRechazoDocenteDesdeModal()" style="background: #dc2626; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer;">

          <i class="bx bx-check"></i> Confirmar Rechazo

        </button>

      </div>

    </div>

  </div>

  <?php endif; ?>

  <!-- ===================================================================== -->

  <!-- WIDGET FLOTANTE: GUÍA Y DESCRIPCIÓN DEL APARTADO (ESQUINA INFERIOR DER) -->

  <!-- ===================================================================== -->

  <?php

    $segmentoActual = service('uri')->getSegment(1) ?? '';

    if (empty($segmentoActual)) {

        $segmentoActual = 'dashboard';

    }

    $catalogoGuias = [

        'dashboard' => [

            'modulo'      => 'Inicio',

            'titulo'      => 'Tablón Comunitario UMC',

            'tag'         => 'Comunidad Universitaria',

            'icono'       => 'bx-home-alt',

            'color'       => '#2563eb',

            'descripcion' => 'Espacio central de interacción estudiantil y docente. Consulta noticias académicas oficiales, comunicados del decanato y publicaciones de la comunidad de todas las carreras.',

            'tips'        => [

                ['icono' => 'bx-edit', 'titulo' => 'Publicar en el muro', 'texto' => 'Comparte avisos, dudas académicas o novedades de tu escuela.'],

                ['icono' => 'bx-heart', 'titulo' => 'Reacciones y debate', 'texto' => 'Interactúa dando "Me gusta" y aportando en los comentarios de cada post.'],

                ['icono' => 'bx-bell', 'titulo' => 'Notificaciones', 'texto' => 'Revisa la campana superior para ver alertas de clases, tareas y avisos.']

            ]

        ],

        'mapa' => [

            'modulo'      => 'Mapa UMC',

            'titulo'      => 'Mapa Interactivo del Campus',

            'tag'         => 'Monitoreo en Tiempo Real',

            'icono'       => 'bx-map-pin',

            'color'       => '#0284c7',

            'descripcion' => 'Plano digital del campus universitario con geolocalización y monitoreo en vivo de aulas, edificios y actividades por carrera.',

            'tips'        => [

                ['icono' => 'bx-check-circle', 'titulo' => 'Aulas Libres (Verde)', 'texto' => 'Espacios disponibles y sin clases para estudiar o descansar.'],

                ['icono' => 'bx-radio-circle-marked', 'titulo' => 'Aulas Ocupadas (Rojo)', 'texto' => 'Muestra el logo de la carrera cursando clase (Informática, Marítima, etc.).'],

                ['icono' => 'bx-time-five', 'titulo' => 'Próxima Clase (Amarillo)', 'texto' => 'Alerta clases que comenzarán en menos de 15 minutos.'],

                ['icono' => 'bx-search-alt', 'titulo' => 'Zoom y vista completa', 'texto' => 'Haz zoom con la rueda o botones; en vista general los pines libres se ocultan solos para no tapar los edificios.']

            ]

        ],

        'biblioteca' => [

            'modulo'      => 'Biblioteca',

            'titulo'      => 'Biblioteca Académica Digital',

            'tag'         => 'Recursos de Estudio',

            'icono'       => 'bx-book-reader',

            'color'       => '#7c3aed',

            'descripcion' => 'Repositorio colaborativo de guías, exámenes pasados, libros y proyectos clasificados por carrera y semestre para apoyar tu preparación académica.',

            'tips'        => [

                ['icono' => 'bx-search', 'titulo' => 'Filtros por carrera', 'texto' => 'Encuentra guías y libros por carrera, semestre, materia o profesor.'],

                ['icono' => 'bx-download', 'titulo' => 'Descarga inmediata', 'texto' => 'Descarga documentos en PDF para estudiar en cualquier momento.'],

                ['icono' => 'bx-cloud-upload', 'titulo' => 'Compartir aportes', 'texto' => 'Sube tus propios resúmenes y material para ayudar a la comunidad.']

            ]

        ],

        'aulas' => [

            'modulo'      => 'Aulas',

            'titulo'      => 'Directorio y Programación de Aulas',

            'tag'         => 'Gestión de Espacios',

            'icono'       => 'bx-door-open',

            'color'       => '#059669',

            'descripcion' => 'Listado oficial de todos los salones de clase, laboratorios y talleres de la universidad con su capacidad, condiciones y asignación de horarios por día.',

            'tips'        => [

                ['icono' => 'bx-calendar', 'titulo' => 'Horario semanal', 'texto' => 'Revisa qué asignaturas y secciones ocupan cada aula por bloque.'],

                ['icono' => 'bx-group', 'titulo' => 'Aforo y condiciones', 'texto' => 'Verifica la capacidad máxima de estudiantes y el estado del aula.'],

                ['icono' => 'bx-user-voice', 'titulo' => 'Avisos docentes', 'texto' => 'Los profesores autorizados pueden agendar avisos o evaluaciones.']

            ]

        ],

        'admin' => [

            'modulo'      => 'Panel Admin',

            'titulo'      => 'Panel de Control Administrativo',

            'tag'         => 'Administración UMC',

            'icono'       => 'bx-shield-quarter',

            'color'       => '#dc2626',

            'descripcion' => 'Módulo central de gestión y supervisión institucional exclusivo para coordinadores y administradores autorizados de LAY-8.',

            'tips'        => [

                ['icono' => 'bx-file', 'titulo' => 'Sincronizar horarios', 'texto' => 'Sube y procesa los PDFs de horarios de las 5 carreras para el mapa.'],

                ['icono' => 'bx-user-check', 'titulo' => 'Aprobación docente', 'texto' => 'Revisa carnets oficiales y valida solicitudes de rol de profesor.'],

                ['icono' => 'bx-line-chart', 'titulo' => 'Métricas y auditoría', 'texto' => 'Supervisa métricas globales, seguridad y eventos del campus.']

            ]

        ],

        'perfil' => [

            'modulo'      => 'Perfil',

            'titulo'      => 'Perfil y Cuenta Universitaria',

            'tag'         => 'Datos del Estudiante',

            'icono'       => 'bx-user',

            'color'       => '#2563eb',

            'descripcion' => 'Información personal de tu cuenta, cédula de identidad, carrera inscrita, foto de avatar y conteo de aportes compartidos en la biblioteca.',

            'tips'        => [

                ['icono' => 'bx-camera', 'titulo' => 'Foto de perfil', 'texto' => 'Actualiza tu foto institucional para mostrar en el tablón.'],

                ['icono' => 'bx-id-card', 'titulo' => 'Verificación', 'texto' => 'Comprueba tu rol y estado de verificación en el campus.']

            ]

        ],

        'ajustes' => [

            'modulo'      => 'Ajustes',

            'titulo'      => 'Configuración de la Plataforma',

            'tag'         => 'Preferencias',

            'icono'       => 'bx-cog',

            'color'       => '#475569',

            'descripcion' => 'Personaliza tu experiencia de navegación, tema visual oscuro o claro y opciones de seguridad de tu cuenta.',

            'tips'        => [

                ['icono' => 'bx-moon', 'titulo' => 'Modo Oscuro', 'texto' => 'Cambia entre tema claro y tema oscuro cuando lo prefieras.'],

                ['icono' => 'bx-lock-alt', 'titulo' => 'Contraseña', 'texto' => 'Actualiza tus credenciales de acceso de forma segura.']

            ]

        ]

    ];

    $guiaInfo = $catalogoGuias[$segmentoActual] ?? $catalogoGuias['dashboard'];

  ?>

  <div class="guia-apartado-widget" id="guia-apartado-widget">

    <!-- Tarjeta emergente con descripción detallada -->

    <div class="guia-apartado-card" id="guia-apartado-card" role="dialog" aria-labelledby="guia-titulo-modulo">

      <div class="guia-card-header">

        <div class="guia-header-icon-box" style="color: <?= $guiaInfo['color'] ?>;">

          <i class="bx <?= esc($guiaInfo['icono']) ?>"></i>

        </div>

        <div class="guia-header-titles">

          <span class="guia-tag-modulo" style="color: <?= $guiaInfo['color'] ?>;"><?= esc($guiaInfo['tag']) ?></span>

          <h4 class="guia-card-title" id="guia-titulo-modulo"><?= esc($guiaInfo['titulo']) ?></h4>

        </div>

        <button type="button" class="guia-btn-cerrar" onclick="cerrarGuiaApartado(event)" title="Cerrar guía">&times;</button>

      </div>

      <div class="guia-card-body">

        <p class="guia-card-desc"><?= esc($guiaInfo['descripcion']) ?></p>

        <?php if (!empty($guiaInfo['tips'])): ?>

        <div class="guia-tips-box">

          <div class="guia-tips-titulo">

            <i class="bx bx-star" style="color: #f59e0b;"></i> Funciones y Tips Rápidos:

          </div>

          <?php foreach ($guiaInfo['tips'] as $tip): ?>

          <div class="guia-tip-item">

            <i class="bx <?= esc($tip['icono']) ?>" style="color: <?= $guiaInfo['color'] ?>;"></i>

            <div>

              <b><?= esc($tip['titulo']) ?>:</b> <?= esc($tip['texto']) ?>

            </div>

          </div>

          <?php endforeach; ?>

        </div>

        <?php endif; ?>

      </div>

      <div class="guia-card-footer">

        <i class="bx bx-bulb"></i>

        <span>Pasa el cursor o haz clic en el botón para consultar esta guía.</span>

      </div>

    </div>

    <!-- Botón disparador (mini cuadro en la esquina inferior derecha) -->

    <button type="button" 

            class="guia-apartado-btn" 

            id="guia-apartado-btn" 

            onclick="toggleGuiaApartado(event)"

            title="¿Dudas sobre <?= esc($guiaInfo['modulo']) ?>? Haz clic o pasa el cursor para ver la guía"

            aria-label="Guía de <?= esc($guiaInfo['modulo']) ?>">

      <i class="bx bx-help-circle guia-icono-principal"></i>

      <span class="guia-dot-pulso" title="Guía activa"></span>

    </button>

  </div>

  <script>

    // Control interactivo del widget de ayuda y guía del apartado

    (function() {

      const widget = document.getElementById('guia-apartado-widget');

      const btn = document.getElementById('guia-apartado-btn');

      if (!widget || !btn) return;

      window.toggleGuiaApartado = function(e) {

        if (e) {

          e.stopPropagation();

          e.preventDefault();

        }

        widget.classList.toggle('is-open');

      };

      window.cerrarGuiaApartado = function(e) {

        if (e) {

          e.stopPropagation();

          e.preventDefault();

        }

        widget.classList.remove('is-open');

      };

      // Cerrar al hacer clic fuera del widget

      document.addEventListener('click', function(e) {

        if (widget.classList.contains('is-open') && !widget.contains(e.target)) {

          widget.classList.remove('is-open');

        }

      });

      // Cerrar al presionar la tecla Escape

      document.addEventListener('keydown', function(e) {

        if (e.key === 'Escape' && widget.classList.contains('is-open')) {

          widget.classList.remove('is-open');

        }

      });

    })();

  </script>

</body>

</html>

