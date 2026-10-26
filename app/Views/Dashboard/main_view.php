<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio - LAY-8 MAP</title>
   <!-- precarga de fuentes-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
  <!-- stylesheets-->
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=7')?>">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <div class="layout-grid">
    <header class="barra-superior">
      <div class="logo">
        <h1>LAY-8 MAP</h1>
      </div>

      <!-- Contenedor del perfil clickeable -->
      <div class="perfil-contenedor user-menu-container" onclick="toggleMenu()">
        <span class="nombre-usuario"><?= session()->get('primer_nombre') . ' ' . session()->get('apellidos'); ?></span>
        <div class="avatar-trigger">
          <?php if (session()->get('foto_perfil')): ?>
            <img src="<?= base_url(session()->get('foto_perfil')) ?>" alt="Foto de perfil">
          <?php else: ?>
            <img src="<?= base_url('img/usuario.png') ?>" alt="Foto de perfil">
            <?php endif; ?>
        </div>

        <!-- Menú desplegable oculto -->
        <div id="dropdown-perfil" class="dropdown-menu">
          <a href="<?= base_url('perfil') ?>" class="dropdown-item">Perfil</a>
          <a href="<?= base_url('ajustes') ?>" class="dropdown-item">Ajustes</a>
        </div>
      </div>
    </header>

    <aside class="sidebar">
      <nav class="menu-links">
        <a href="<?= base_url('/feed'); ?>" class="active">Inicio</a>
        <a href="<?= base_url('/mapa'); ?>">Mapa UMC</a>
        <a href="<?= base_url('/biblioteca'); ?>">Biblioteca</a>
        <a href="<?= base_url('/aulas_view'); ?>">Aulas</a>
      </nav>

      <div class="sidebar-footer">
        <a href="<?= base_url('logout')?>" class="logout-btn">
          <i class="bx bx-log-out"></i>
        </a>
      </div>
    </aside>

    <main class="content-area">
        <div class="content-box">
          <button id="btn-expandir" class="btn-expandir-layout" onclick="toggleFocus()">
            <i id="focus-icon" class="bx bx-expand focus-icon"></i>
          </button>
          <?= $this->renderSection('content') ?>
        </div>
    </main>
  </div>

  <script src="<?= base_url('js/main.js?v=7')?>"></script>
</body>
</html>