<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>LAY-8 MAP - Inicio</title>
    <!-- precarga de fuentes-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
		<!-- stylesheets-->
    <link rel="stylesheet" href="<?= base_url('css/global.css')?>">
		<link rel="stylesheet" href="<?= base_url('css/landing.css')?>">
  </head>

  <body>

    <main class="landing-container">

      <div class="upp-half">
        <header class="hero-header">
          <div class="logo">
            <img src="<?= base_url('img/lay8_logo.png')?>" alt="LAY-8 MAP">
            <span>LAY-8 MAP</span>
          </div>
          
          <div class="login">
            <span class="login-text">Ya tienes cuenta?</span>
            <button id="btn-login" class="btn-login-text">Ingresar</button>
          </div>
        
        </header>
        
        <section class="hero-section">
          <span class="subtitle">Seas estudiante o docente</span>
          <hr class="hero-divisor">
          <article class="hero-text">
            <h1>Tu rutina<br>universitaria,<br>más fácil</h1>
            <a href="<?= base_url('registro') ?>" class="btn-cta">Empieza ahora!</a>
          </article>
        </section>

         <nav class="nav-services">
          <button class="btn-service activo" data-target="aula">Aulas Virtuales</button>
          <button class="btn-service" data-target="mapa">Mapa UMC</button>
          <button class="btn-service" data-target="biblioteca">Biblioteca</button>
        </nav>

      </div>
      
      <section class="low-half">
        
        <div id="cards-zone" class="cards-container">
          <!--tarjeta aula-->
          <article id="tarjeta-aula" class="tarjeta-info activa">
            <div class="tarjeta-content">
              <h2>Aulas Virtuales</h2>
              <hr class="tarjeta-divisor">
              <p>Crea y accede a entornos virtuales de aprendizaje. Gestiona tus asignaciones, consulta el material de estudio y manten comunicacion directa con tus alumnos o profesores!</p>
            </div>

            <div class="tarjeta-icon">
              <img src="<?= base_url('img/aula_icono.png')?>" alt="Ilustracion Aulas Virtuales">
            </div>

          </article>
          <!--tarjeta biblio-->
          <article id="tarjeta-biblioteca" class="tarjeta-info">
            <div class="tarjeta-content">
              <h2>Biblioteca</h2>
              <hr class="tarjeta-divisor">
              <p>Accede a una amplia colección de recursos digitales, libros, revistas y más. Busca, reserva y descarga materiales para tu investigación y estudio.</p>
            </div>

            <div class="tarjeta-icon">
              <img src="<?= base_url('img/biblio_icono.png')?>" alt="Ilustracion Biblioteca">
            </div>

          </article>

          <!--tarjeta mapa-->

          <article id="tarjeta-mapa" class="tarjeta-info">
            <div class="tarjeta-content">
              <h2>Mapa UMC</h2>
              <hr class="tarjeta-divisor">
              <p>Explora el campus universitario con nuestro mapa interactivo. Encuentra aulas, servicios, instalaciones y más.</p>
            </div>

            <div class="tarjeta-icon">
              <img src="<?= base_url('img/mapa_icono.png')?>" alt="Ilustracion Mapa UMC">
            </div>

          </article>

        </div>
      
      </section>
    </main>

    <!--login-->

    <div id="modal-login-overlay" class="modal-overlay oculto" 
    data-error="<?= session()->getFlashdata('error') ? 'true' : 'false' ?>">
      <div class="modal-login">
        <button id="btn-close-login" class="btn-close">&times;</button>

        <div class="white-half">
          <div class="modal-header">
            <h2>Bienvenido de vuelta!</h2>
            <hr class="modal-divisor">
            <p>Inicia sesión</p>
          </div>

           <?php if(session()->getFlashdata('error')): ?>
              <div class="error-msg">
                <?= session()->getFlashdata('error')?>
              </div>
            <?php endif; ?>

          <form action=" <?= base_url('login/autenticar')?>" method="POST" class="form-login">

            <label for="nombre_usuario">Usuario</label>
            <input type="text" id="nombre_usuario" name="nombre_usuario" class="input-login" required>

            <div class="label-pass-group">
              <label for="contrasena">Contraseña</label>
              <a href="#" class="link-olvido">Olvidaste tu contraseña?</a>
            </div>

            <div class="input-pass-group">
              <input type="password" id="contrasena" name="contrasena" class="input-login" required>
              <button type="button" id="btn-toggle-pass" class="btn-toggle-pass">
                <img src="<?= base_url('img/ojo.svg')?>" alt="Mostrar contraseña" id="eye-icon">
              </button>
            </div>

            <button type="submit" class="btn-ingresar">Ingresar</button>
          </form>

        </div>

        <div class="blue-half">
          <img src="<?= base_url('img/stickers_overlay.png')?>" alt="Decoracion Login" class="stickers-bg">
          <img src="<?= base_url('img/lay8_logo.png')?>" alt="Logo LAY-8 MAP" class="logo-modal">
        </div>
        
      </div>
    </div>

    <footer class="footer-global">

      <div class="footer-columna">
    <img src="<? base_url('img/lay8_logo.png')?>" alt="LAY-8 MAP Logo" class="decoracion-logo">
    <p style="margin-top: 15px;">Prototipo funcional desarrollado exclusivamente con fines académicos para la Unidad Marítima del Caribe.</p>
  </div>

  <div class="footer-columna">
    <h4>ESTUDIANTES</h4>
    <p>Eric Mendez</p>
    <p>Abraham Angulo</p>
    <p>Juan Morles</p>
    <p>Haryerit Larez</p>
  </div>

  <div class="footer-columna">
    <h4>LABORES</h4>
    <p>Lider de Equipo, BDD</p>
    <p>Backend</p>
    <p>Backend</p>
    <p>Fronted</p>
  </div>

  <div class="footer-columna">
    <h4>CONTACTO</h4>
    <p>info@lay8map.com</p>
    
  </div>
    </footer>

    <script src="<?= base_url('js/landing.js') ?>"></script>

  </body>