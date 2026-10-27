<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LAY-8</title>

  <!-- Script inline antividrios/anti-flicker de Modo Oscuro -->
  <script>
    (function() {
      try {
        var tema = localStorage.getItem('lay8_theme');
        if (tema === 'dark') {
          document.documentElement.classList.add('dark-mode');
        }
      } catch(e) {}
    })();
  </script>

  <!-- Precarga de fuentes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=' . time())?>">
  <link rel="stylesheet" href="<?= base_url('css/feed.css?v=' . time())?>">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
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
        <span class="nombre-usuario"><?= esc($userNombreCompleto) ?></span>
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
        <a href="<?= base_url('logout')?>" class="logout-btn" title="Cerrar Sesión">
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
              <h2 class="perfil-user-name"><?= esc($userNombreCompleto) ?></h2>
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

  <!-- Modal Interactivo de Ajustes -->
  <div id="modal-ajustes" class="modal-ajustes-overlay" onclick="if(event.target === this) cerrarModalAjustes()">
    <div class="modal-ajustes-content">
      <div class="modal-ajustes-header">
        <h3><i class="bx bx-cog"></i> Ajustes del Sistema</h3>
        <button type="button" class="btn-cerrar-modal" onclick="cerrarModalAjustes()" title="Cerrar">&times;</button>
      </div>

      <div class="modal-ajustes-body">
        <div class="ajuste-item">
          <div class="ajuste-info">
            <div class="ajuste-titulo">
              <i class="bx bx-moon" id="icono-tema-visual"></i> Modo Oscuro
            </div>
            <div class="ajuste-descripcion">
              Reduce el brillo de la pantalla y descansa la vista con una paleta de colores oscuros optimizada para el campus.
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

      <div class="modal-ajustes-footer">
        <button type="button" class="btn-modal-guardar" onclick="cerrarModalAjustes()">Listo</button>
      </div>
    </div>
  </div>

  <!-- Modal Interactivo de Editar Perfil y Carrera -->
  <div id="modal-editar-perfil" class="modal-carreras-overlay" style="display: none;" onclick="if(event.target === this) cerrarModalEditarPerfil()">
    <div class="modal-editar-perfil-content">
      <div class="modal-editar-header">
        <h3><i class="bx bx-user-pin"></i> Editar Perfil</h3>
        <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalEditarPerfil()" title="Cerrar">&times;</button>
      </div>

      <form action="<?= base_url('perfil/actualizar') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="carrera" id="input-editar-carrera" value="<?= esc($userCodigoCarrera) ?>">

        <div class="modal-editar-body">
          <!-- Sección Foto de Perfil -->
          <div class="form-group-editar">
            <label class="form-label-editar"><i class="bx bx-camera"></i> Foto de Perfil</label>
            <p class="form-sublabel-editar">Sube una imagen (JPG o PNG, máx. 2MB)</p>
            <div class="editar-foto-preview-row">
              <img src="<?= !empty($userFoto) ? base_url($userFoto) : base_url('img/usuario.png') ?>" id="preview-foto-modal" class="preview-mini-avatar" alt="Foto">
              <div>
                <input type="file" name="foto" id="input-foto-editar" accept="image/png, image/jpeg, image/jpg" style="display:none;" onchange="previsualizarFotoPerfil(this)">
                <button type="button" class="btn-cambiar-carrera" onclick="document.getElementById('input-foto-editar').click()">
                  <i class="bx bx-upload"></i> Subir nueva foto
                </button>
              </div>
            </div>
          </div>

          <!-- Sección Selección de Carrera UMC -->
          <div class="form-group-editar" style="margin-bottom: 0;">
            <label class="form-label-editar"><i class="bx bx-graduation"></i> Carrera Universitaria (UMC)</label>
            <p class="form-sublabel-editar">Selecciona tu carrera oficial en la universidad:</p>

            <div class="grid-carreras-selector">
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

          <!-- Sección Verificación y Seguridad para Cambio de Carrera -->
          <div class="form-group-editar" style="background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid #cbd5e1; margin-top: 15px;">
            <label class="form-label-editar" style="color: #1e3a8a; font-size: 0.9rem;"><i class="bx bx-shield-quarter"></i> Certificación Oficial UMC</label>
            <p class="form-sublabel-editar" style="font-size: 0.8rem; margin-bottom: 8px;">
              Si cambias de carrera, adjunta tu nuevo <strong>Registro de Inscripción (PDF)</strong> de la UMC para certificarla y sincronizar tus aulas automáticamente:
            </p>
            <input type="file" name="planilla_inscripcion" accept=".pdf" class="input-global" style="margin-bottom: 12px; font-size: 0.82rem; background: white;">

            <label class="form-label-editar" style="font-size: 0.88rem;"><i class="bx bx-lock-alt"></i> Confirmar con tu Contraseña</label>
            <input type="password" name="confirmar_contrasena" class="input-global" placeholder="Ingresa tu contraseña actual para autorizar" style="background: white;">
          </div>
        </div>

        <div class="modal-editar-footer">
          <button type="button" class="btn-cancelar-modal" onclick="cerrarModalEditarPerfil()">Cancelar</button>
          <button type="submit" class="btn-guardar-perfil"><i class="bx bx-save"></i> Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>

  <script src="<?= base_url('js/main.js?v=11')?>"></script>

  <!-- Scripts de Notificaciones en Tiempo Real -->
  <script>
    const BASE_URL_NOTIF = '<?= base_url() ?>/';

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
      fetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-leida/' + id, { method: 'POST' })
        .then(() => {
          cargarConteoNotificaciones();
        })
        .catch(() => {});
    }

    function marcarTodasNotificacionesLeidas(e) {
      if (e) e.stopPropagation();
      fetch(BASE_URL_NOTIF + 'api/notificaciones/marcar-todas-leidas', { method: 'POST' })
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
    });
  </script>

</body>
</html>
