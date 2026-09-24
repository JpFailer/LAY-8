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
  <!--plantilla header + la vista que se llame -->
  <link rel="stylesheet" href="<?= base_url('css/global.css') ?>">
  <?php if(isset($css_extra)): ?>
    <link rel="stylesheet" href="<?= base_url('css/'.$css_extra) ?>">
  <?php endif; ?>
</head>

<body>
  <div class="layout-global">

  <header class="barra-superior">

    <div class="logo">
      <h1>LAY-8 MAP</h1>
    </div>

    <nav class="navegacion-principal">
      <a href="#" class="btn-nav activo">Mis guardados</a>
      <a href="#" class="btn-nav activo">Mis materias</a>
    </nav>

    <div class="perfil-contenedor">
      <button id="btn-avatar" class="avatar-trigger">
        <img src="<?= base_url('img/usuario.png')?>" alt="Perfil"> <!--placeholder, cambiar por foto del usuario -->
      </button>

      <div id="menu-popout" class="popout-perfil oculto">
        <div class="popout-header">
          <img src="<?= base_url('img/usuario.png')?>" alt="Perfil">
          <span class="nombre-usuario">Usuario</span> 
          <span class="carrera-usuario">Carrera</span> <!--placeholder, cambiar por datos del usuario -->
        </div>

      <div class="popout-enlaces">
        <a href="#"><i class="fa-solid fa-user"></i> Perfil</a>
        <a href="#"><i class="fa-solid fa-gear"></i> Ajustes</a>
        <a href="#"><i class="fa-solid fa-pen"></i> Tema</a>
    </div>
   </div>

  </header>