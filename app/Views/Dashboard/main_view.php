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
  <link rel="stylesheet" href="<?= base_url('css/global.css?v=4')?>">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
  <div class="layout-grid">
    <header class="barra-superior">
      <div class="logo">
        <h1>LAY-8 MAP</h1>
      </div>

      <!-- Contenedor del perfil clickeable -->
      <div class="perfil-contenedor user-menu-container" style="display: flex; align-items: center; gap: 10px; position: relative; cursor: pointer;" onclick="toggleMenu()">
        <span style="color: white; font-weight: 500;"><?= session()->get('primer_nombre') . ' ' . session()->get('apellidos'); ?></span>
        
        <div class="avatar-trigger">
          <?php if (session()->get('foto_perfil')): ?>
            <img src="<?= base_url(session()->get('foto_perfil')); ?>" alt="Foto de perfil" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
          <?php else: ?>
            <img src="<?= base_url('img/usuario.png'); ?>" alt="Avatar predeterminado" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
          <?php endif; ?>
        </div>

        <!-- Menú desplegable oculto -->
        <div id="dropdown-perfil" style="display: none; position: absolute; right: 0; top: 50px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 160px; overflow: hidden; z-index: 1000; border: 1px solid #e5e7eb;">
            <a href="<?= base_url('perfil') ?>" style="display: block; padding: 12px 15px; color: #1e40af; text-decoration: none; border-bottom: 1px solid #f3f4f6; font-size: 14px; font-weight: 500;">
                Mi Perfil
            </a>
            <a href="<?= base_url('logout') ?>" style="display: block; padding: 12px 15px; color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 500;">
                Cerrar Sesión
            </a>
        </div>
      </div>
    </header>

    <aside class="sidebar">
      <nav class="menu-links">
        <a href="<?= base_url('/feed'); ?>" class="active">Inicio</a>
        <a href="<?= base_url('/mapa'); ?>">Mapa UMC</a>
        <a href="<?= base_url('/biblioteca'); ?>">Biblioteca</a>
        <a href="<?= base_url('/aulas'); ?>">Aulas</a>
      </nav>

      <div class="sidebar-footer">
        <a href="<?= base_url('logout')?>" class="logout-btn">
          <i class="bx bx-log-out"></i>
        </a>
      </div>
    </aside>

    <main class="content-area">
      <?= $this->renderSection('content') ?>
    </main>
  </div>

  <script>
  function toggleMenu() {
      var menu = document.getElementById('dropdown-perfil');
      menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
  }

  window.onclick = function(event) {
      if (!event.target.closest('.user-menu-container')) {
          var menu = document.getElementById('dropdown-perfil');
          if (menu && menu.style.display === 'block') {
              menu.style.display = 'none';
          }
      }
  }
  </script>
</body>
</html>