<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LAY-8 MAP</title>
  <!--precarga de recursos, carga de las fuentes y libreria de iconos-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!--carga de estilos-->
  <link rel="stylesheet" href="<?= base_url('css/global.css') ?>">
  <?php if(isset($css_extra)): ?>
    <link rel="stylesheet" href="<?= base_url('css/'.$css_extra) ?>">
  <?php endif; ?>
</head>

<body>
  <div class="layout-global">

  <header class="barra-superior">

    <div class="logo">
      <a href="<?= base_url('dashboard') ?>" style="text-decoration: none; color: inherit;">
        <h1>LAY-8 MAP</h1>
      </a>
    </div>

    <nav class="navegacion-principal">
      <a href="<?= base_url('dashboard') ?>" class="btn-nav activo">Inicio</a>
      <a href="<?= base_url('mapa') ?>" class="btn-nav">Mapa</a>
      <a href="<?= base_url('biblioteca') ?>" class="btn-nav">Biblioteca</a>
    </nav>

    <div class="perfil-contenedor">
      <button id="btn-avatar" class="avatar-trigger">
        <img src="<?= base_url('img/usuario.png')?>" alt="Perfil">
      </button>

      <div id="menu-popout" class="popout-perfil oculto">
        <div class="popout-header">
          <img src="<?= base_url('img/usuario.png')?>" alt="Perfil">
          <span class="nombre-usuario"><?= esc((session()->get('primer_nombre') ?? 'Usuario') . ' ' . (session()->get('apellidos') ?? '')) ?></span> 
          <span class="carrera-usuario" style="text-transform: capitalize;"><?= esc(session()->get('rol') ?? 'Estudiante') ?></span>
        </div>

        <div class="popout-enlaces">
          <a href="<?= base_url('perfil') ?>"><i class="fa-solid fa-user"></i> Mi Perfil</a>
          <a href="<?= base_url('mapa') ?>"><i class="fa-solid fa-map-location-dot"></i> Ver Mapa</a>
          <a href="<?= base_url('logout') ?>"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
        </div>
      </div>
    </div>

  </header>
