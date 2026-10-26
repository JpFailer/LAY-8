<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LAY-8 MAP - Campus UMC</title>
  
  <!-- Script anti-destello para Modo Oscuro (Carga Inmediata) -->
  <script>
    (function() {
      const temaGuardado = localStorage.getItem('lay8_theme');
      if (temaGuardado === 'dark') {
        document.documentElement.classList.add('dark-mode');
      }
    })();
  </script>

  <!-- Precarga de fuentes oficiales -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=10')?>">
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
    $userCarrera        = ($userRol === 'profesor') ? 'Docente UMC' : 'ING-INF';
    $userEstado         = 'Activo';

    $userTotalAportes = 0;
    if ($sesionId) {
        if (empty($userCorreo)) {
            try {
                $dbConn = \Config\Database::connect();
                $rowU = $dbConn->table('usuarios')->select('correo, estado_cuenta')->where('id', $sesionId)->get()->getRowArray();
                if ($rowU) {
                    $userCorreo = $rowU['correo'] ?? '';
                    $userEstado = ucfirst($rowU['estado_cuenta'] ?? 'Activo');
                }
            } catch (\Throwable $e) {}
        }
        try {
            $userTotalAportes = (new \App\Models\AporteBibliotecaModel())->where('usuario_id', $sesionId)->countAllResults();
        } catch (\Throwable $e) {}
    }
  ?>

  <div class="layout-grid">
    <header class="barra-superior">
      <div class="logo">
        <a href="<?= base_url('dashboard') ?>" style="color:white; text-decoration:none;">
          <h1>LAY-8 MAP</h1>
        </a>
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
          <a href="<?= base_url('perfil') ?>" class="dropdown-item" onclick="abrirModalPerfil(event)">
            <i class="bx bx-user" style="margin-right: 6px;"></i> Perfil
          </a>
          <a href="<?= base_url('ajustes') ?>" class="dropdown-item" onclick="abrirModalAjustes(event)">
            <i class="bx bx-cog" style="margin-right: 6px;"></i> Ajustes
          </a>
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
      </nav>

      <div class="sidebar-footer">
        <a href="<?= base_url('logout')?>" class="logout-btn" title="Cerrar Sesión">
          <i class="bx bx-log-out"></i>
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

        <!-- Pie de tarjeta con acción de editar/cambiar foto -->
        <div class="perfil-card-bottom">
          <form id="form-cambiar-foto-modal" action="<?= base_url('perfil/subirFoto') ?>" method="post" enctype="multipart/form-data" style="display:none;">
            <?= csrf_field() ?>
            <input type="file" id="input-subir-foto-modal" name="foto" accept="image/png, image/jpeg, image/jpg" onchange="this.form.submit()">
          </form>
          <button type="button" class="btn-editar-perfil-trigger" onclick="document.getElementById('input-subir-foto-modal').click()">
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

  <script src="<?= base_url('js/main.js?v=10')?>"></script>
</body>
</html>
