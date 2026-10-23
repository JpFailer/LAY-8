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

      <div class="perfil-contenedor" style="display: flex; align-items: center; gap: 10px;">
        <span><?= session()->get('primer_nombre') . ' ' . session()->get('apellidos'); ?></span>
        <div class="avatar-trigger">
          <?php if (session()->get('foto_perfil')): ?>
            <img src="<?= base_url('uploads/' . session()->get('foto_perfil')); ?>" alt="Foto de perfil">
          <?php else: ?>
            <img src="<?= base_url('img/usuario.png'); ?>" alt="Avatar predeterminado">
          <?php endif; ?>
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

</body>
</html>