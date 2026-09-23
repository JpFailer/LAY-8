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
            <span class="login-text">¿Ya tienes cuenta?</span>
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
          <button class="btn-service activo" data-target="aulas">Aulas Virtuales</button>
          <button class="btn-service" data-target="mapa">Mapa UMC</button>
          <button class="btn-service" data-target="comunidad">Comunidad</button>
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
              <img src="<?= base_url('img/comu_icono.png')?>" alt="Ilustracion Comunidad">
            </div>

          </article>
          <!--resto d tarjetas-->
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
            
            <!-- ENLACE DE REGISTRO AÑADIDO AQUÍ -->
            <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #444;">
                ¿No tienes cuenta? <a href="<?= base_url('registro') ?>" style="color: #1a56db; font-weight: bold; text-decoration: none;">Regístrate</a>
            </div>

          </form>

        </div>

        <div class="blue-half">
          <img src="<?= base_url('img/stickers_overlay.png')?>" alt="Decoracion Login" class="stickers-bg">
          <img src="<?= base_url('img/lay8_logo.png')?>" alt="Logo LAY-8 MAP" class="logo-modal">
        </div>
        
      </div>
    </div>

    <script src="<?= base_url('js/landing.js') ?>"></script>

  </body>
</html>