<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAY-8 - Inicio</title>
    <!-- precarga de fuentes-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- stylesheets-->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/global.css') . '?v=' . time() ?>">
    <link rel="stylesheet" href="<?= base_url('css/landing.css') . '?v=' . time() ?>">
  
    
  </head>

  <body>

    <main class="landing-container">

      <div class="upp-half">
        <header class="hero-header">
          <div class="logo">
            <img src="<?= base_url('img/lay8_logo.png')?>" alt="LAY-8">
            <span>LAY-8</span>
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
              <img src="<?= base_url('img/comu_icono.png')?>" alt="Ilustracion Comunidad">
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

      <!-- ========================================================================= -->
      <!-- SECCIÓN INTERACTIVA: MISIÓN, VISIÓN Y VALORES (LAY-8)                    -->
      <!-- ========================================================================= -->
      <section class="seccion-rumbo-lay8" id="rumbo-lay8">
        
        <!-- Cabecera de la sección -->
        <div class="rumbo-header">
          <div class="insignia-pill-rumbo">
            <i class="bx bx-compass"></i>
            <span>NUESTRO RUMBO ACADÉMICO</span>
          </div>
          <h2 class="rumbo-titulo">
            El Corazón y Rumbo de <span class="rumbo-gradient-text">LAY-8</span>
          </h2>
          <div class="divisor-decorativo">
            <span class="punto-linea"></span>
          </div>
          <p class="rumbo-subtitulo">
            Tecnología desarrollada por estudiantes de la <b>Universidad Marítima del Caribe</b> para modernizar la orientación en el campus, la gestión de horarios y el aprendizaje colaborativo.
          </p>
        </div>

        <!-- Grilla de las 3 Tarjetas con Animaciones -->
        <div class="rumbo-cards-grid">

          <!-- Tarjeta 1: MISIÓN -->
          <article class="card-filosofia card-mision-animada">
            <div class="card-glow-accent glow-blue"></div>
            <div class="card-top-row">
              <div class="icono-flotante icono-box-mision">
                <i class="bx bx-target-lock"></i>
              </div>
              <span class="badge-card-tipo tag-blue">Propósito Diario</span>
            </div>
            
            <h3 class="card-filosofia-titulo">Misión</h3>
            <p class="card-filosofia-desc">
              Transformar la experiencia universitaria en la Universidad Marítima del Caribe (UMC) mediante una plataforma integral que sincroniza la vida física y digital del campus. Conectamos a estudiantes y docentes a través de la geolocalización de aulas, horarios automatizados, verificación documental sin fricción y aprendizaje colaborativo, eliminando la desorientación y optimizando cada jornada académica.
            </p>

            <div class="card-highlights">
              <span class="highlight-chip"><i class="bx bx-map-pin"></i> Geolocalización UMC</span>
              <span class="highlight-chip"><i class="bx bx-time"></i> Horarios en Vivo</span>
              <span class="highlight-chip"><i class="bx bx-check-shield"></i> Cero Fricción</span>
            </div>
          </article>

          <!-- Tarjeta 2: VISIÓN -->
          <article class="card-filosofia card-vision-animada">
            <div class="card-glow-accent glow-purple"></div>
            <div class="card-top-row">
              <div class="icono-flotante icono-box-vision">
                <i class="bx bx-rocket"></i>
              </div>
              <span class="badge-card-tipo tag-purple">Hacia Dónde Vamos</span>
            </div>

            <h3 class="card-filosofia-titulo">Visión</h3>
            <p class="card-filosofia-desc">
              Consolidarnos como el modelo de referencia en Venezuela de un <b>Campus Inteligente</b> impulsado por estudiantes, demostrando que la tecnología creada desde las aulas moderniza la educación superior, convirtiendo a LAY-8 en un ecosistema universitario indispensable, accesible y en constante evolución.
            </p>

            <div class="card-highlights">
              <span class="highlight-chip"><i class="bx bx-buildings"></i> Campus Inteligente</span>
              <span class="highlight-chip"><i class="bx bx-trending-up"></i> Innovación Nacional</span>
              <span class="highlight-chip"><i class="bx bx-network-chart"></i> Ecosistema Abierto</span>
            </div>
          </article>

          <!-- Tarjeta 3: VALORES -->
          <article class="card-filosofia card-valores-animada">
            <div class="card-glow-accent glow-green"></div>
            <div class="card-top-row">
              <div class="icono-flotante icono-box-valores">
                <i class="bx bx-diamond"></i>
              </div>
              <span class="badge-card-tipo tag-green">Nuestros 5 Pilares</span>
            </div>

            <h3 class="card-filosofia-titulo">Valores</h3>
            
            <ul class="lista-valores-moderna">
              <li>
                <div class="valor-icon-wrapper"><i class="bx bx-user-check"></i></div>
                <div class="valor-text-wrapper">
                  <strong>Enfoque en la Capa 8:</strong>
                  <span>El factor humano y la comunidad universitaria en el centro de cada desarrollo.</span>
                </div>
              </li>
              <li>
                <div class="valor-icon-wrapper"><i class="bx bx-share-alt"></i></div>
                <div class="valor-text-wrapper">
                  <strong>Colaboración Abierta:</strong>
                  <span>Construcción colectiva del conocimiento y recursos compartidos sin barreras.</span>
                </div>
              </li>
              <li>
                <div class="valor-icon-wrapper"><i class="bx bx-shield-quarter"></i></div>
                <div class="valor-text-wrapper">
                  <strong>Integridad y Rigor:</strong>
                  <span>Transparencia, honestidad académica y cero tolerancia a la manipulación documental.</span>
                </div>
              </li>
              <li>
                <div class="valor-icon-wrapper"><i class="bx bx-bulb"></i></div>
                <div class="valor-text-wrapper">
                  <strong>Innovación Práctica:</strong>
                  <span>Soluciones ágiles y optimizadas a problemáticas cotidianas del campus.</span>
                </div>
              </li>
              <li>
                <div class="valor-icon-wrapper"><i class="bx bx-anchor"></i></div>
                <div class="valor-text-wrapper">
                  <strong>Pertenencia UMC:</strong>
                  <span>Orgullo, lealtad y compromiso permanente con el prestigio de nuestra alma máter.</span>
                </div>
              </li>
            </ul>
          </article>

        </div>
      </section>
    </main>

    <!--login-->

    <div id="modal-login-overlay" class="modal-overlay oculto" 
    data-error="<?= (session()->getFlashdata('error') || session()->getFlashdata('mensaje')) ? 'true' : 'false' ?>">
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
            <?php if(session()->getFlashdata('mensaje')): ?><div class="success-msg" style="background-color: #d1e7dd; color: #0f5132; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;"><?= session()->getFlashdata('mensaje') ?></div><?php endif; ?>

          <form action="<?= base_url('login/autenticar') ?>" method="POST" class="form-login"> <?= csrf_field() ?>

            <label for="nombre_usuario">Usuario</label>
            <input type="text" id="nombre_usuario" name="nombre_usuario" class="input-login" required>

            <label for="contrasena">Contraseña</label>
            <div class="input-pass-group">
              <input type="password" id="contrasena" name="contrasena" class="input-login" required>
              <button type="button" id="btn-toggle-pass" class="btn-toggle-pass">
                <img src="<?= base_url('img/ojo.svg')?>" alt="Mostrar contraseña" id="eye-icon">
              </button>
            </div>

            <!-- Posición correcta: debajo de la contraseña, no de usuario -->
            <div style="display: flex; justify-content: flex-end; margin-top: 8px; margin-bottom: 20px;">
              <a href="javascript:void(0)" class="link-olvido" onclick="abrirModalRecuperacion()" style="font-size: 0.83rem; color: #1a56db; text-decoration: none; font-weight: 600;">¿Olvidaste tu contraseña?</a>
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
          <img src="<?= base_url('img/lay8_logo.png')?>" alt="Logo LAY-8" class="logo-modal">
        </div>
        
      </div>
    </div>

    <!-- Modal Recuperación de Contraseña Interactivo (Con Scroll Adaptativo y Diseño Compacto) -->
    <div id="modal-recuperacion-pass" class="modal-overlay oculto" onclick="if(event.target === this) cerrarModalRecuperacion()" style="padding: 15px; box-sizing: border-box;">
      <div class="card-recuperar-adaptativa" style="background: #ffffff; width: 100%; max-width: 530px; max-height: calc(100vh - 30px); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4); position: relative; display: flex; flex-direction: column; overflow: hidden; animation: modalScaleUp 0.25s ease-out;">
        <button type="button" class="btn-close btn-cerrar-recup" onclick="cerrarModalRecuperacion()" title="Cerrar ventana" aria-label="Cerrar">&times;</button>
        
        <div style="padding: 24px 28px; overflow-y: auto; max-height: calc(100vh - 30px); box-sizing: border-box;">
          
          <!-- PASO 1: Ingresar usuario o correo -->
          <div id="recup-paso-1">
            <div style="text-align: center; margin-bottom: 18px;">
              <div style="width: 52px; height: 52px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 10px auto;">
                <i class="bx bx-key"></i>
              </div>
              <h3 style="font-family: 'Montserrat', sans-serif; font-size: 1.35rem; color: #0f172a; margin: 0 0 6px 0; font-weight: 700;">Recuperar Contraseña</h3>
              <p style="font-size: 0.88rem; color: #64748b; line-height: 1.5; margin: 0;">
                Ingresa tu usuario o correo electrónico de <strong>LAY-8</strong> para verificar tu cuenta:
              </p>
            </div>

            <div id="recup-alerta-error" style="display: none; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: 8px; font-size: 0.86rem; margin-bottom: 15px;"></div>

            <div style="margin-bottom: 20px;">
              <label style="font-size: 0.86rem; font-weight: 600; color: #334155; display: block; margin-bottom: 6px;">Usuario o Correo Registrado</label>
              <input type="text" id="recup_identificador" class="input-login" style="width: 100%; box-sizing: border-box;" placeholder="Ej: tu_usuario o correo@umc.edu.ve">
            </div>

            <button type="button" id="btn-verificar-recup" onclick="ejecutarVerificarCuenta()" class="btn-ingresar" style="width: 100%; margin-top: 0; padding: 12px; border-radius: 10px; font-weight: 600;">Verificar Cuenta</button>
          </div>

          <!-- PASO 2: Confirmación de Envío de Enlace Seguro -->
          <div id="recup-paso-2" style="display: none; text-align: center;">
            <div style="width: 56px; height: 56px; background: #ecfdf5; color: #059669; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.9rem; margin: 0 auto 10px auto;">
              <i class="bx bx-mail-send"></i>
            </div>
            <h3 style="font-family: 'Montserrat', sans-serif; font-size: 1.3rem; color: #0f172a; margin: 0 0 6px 0; font-weight: 700;">¡Enlace de Recuperación Enviado!</h3>
            <p style="font-size: 0.88rem; color: #64748b; margin: 0 0 16px 0; line-height: 1.5;">
              Hemos enviado un enlace seguro al correo registrado de la cuenta <strong id="recup-usuario-nombre" style="color: #0f172a;"></strong>:
            </p>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 16px; text-align: left;">
              <div style="display: flex; align-items: center; gap: 10px; color: #0f172a; font-weight: 600; font-size: 0.95rem; margin-bottom: 6px;">
                <i class="bx bx-envelope" style="color: #2563eb; font-size: 1.3rem;"></i>
                <span id="recup-correo-destino"></span>
              </div>
              <p style="font-size: 0.82rem; color: #64748b; margin: 0; line-height: 1.4; border-top: 1px dashed #cbd5e1; padding-top: 8px;">
                <i class="bx bx-shield-quarter" style="color: #059669;"></i> Por seguridad, solo quien tenga acceso a este correo puede cambiar la clave. El enlace caduca en <strong>2 horas</strong>.
              </p>
            </div>

            <!-- Botón de testing sólo si estamos en localhost/desarrollo -->
            <div id="recup-dev-card" style="display: none; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 12px; margin-bottom: 16px; text-align: left; font-size: 0.82rem; color: #92400e;">
              <div style="display: flex; align-items: flex-start; gap: 8px;">
                <i class="bx bx-info-circle" style="font-size: 1.2rem; color: #d97706; flex-shrink: 0; margin-top: 1px;"></i>
                <div>
                  <strong>Modo de pruebas local:</strong> Como tu servidor local no cuenta con servidor SMTP de correo configurado, puedes acceder a la página de restablecimiento directamente desde aquí:
                  <div style="margin-top: 8px;">
                    <a id="recup-dev-link" href="#" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; background: #2563eb; color: #ffffff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.8rem;">
                      <i class="bx bx-link-external"></i> Abrir Enlace de Recuperación
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <button type="button" onclick="cerrarModalRecuperacion()" class="btn-ingresar" style="width: 100%; margin-top: 0; padding: 12px; border-radius: 10px; font-weight: 600;">
              Entendido
            </button>

            <div style="margin-top: 14px;">
              <a href="javascript:void(0)" onclick="volverAPaso1Recup()" style="font-size: 0.83rem; color: #2563eb; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                <i class="bx bx-arrow-back"></i> Verificar otra cuenta
              </a>
            </div>
          </div>

          <!-- PASO 3: Éxito -->
          <div id="recup-paso-3" style="display: none; text-align: center; padding: 10px 0;">
            <div style="width: 58px; height: 58px; background: #dcfce7; color: #166534; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 12px auto;">
              <i class="bx bx-check"></i>
            </div>
            <h3 style="font-family: 'Montserrat', sans-serif; font-size: 1.3rem; color: #0f172a; margin: 0 0 6px 0; font-weight: 700;">¡Contraseña Actualizada!</h3>
            <p style="font-size: 0.88rem; color: #64748b; line-height: 1.5; margin: 0 0 18px 0;" id="recup-mensaje-exito">
              Tu contraseña ha sido actualizada con éxito. Ya puedes iniciar sesión con tu nueva clave.
            </p>
            <button type="button" onclick="volverALoginDesdeRecuperacion()" class="btn-ingresar" style="width: 100%; margin-top: 0; padding: 12px; border-radius: 10px; font-weight: 600;">Iniciar Sesión</button>
          </div>

          <div style="text-align: center; margin-top: 16px; font-size: 0.8rem; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px;">
            ¿Problemas con tu cuenta? Escribe a <a href="mailto:lay8contacto@gmail.com" style="color: #2563eb; text-decoration: underline;">lay8contacto@gmail.com</a>
          </div>

        </div>
      </div>
    </div>

    <footer class="footer-global">

      <div class="footer-columna">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
          <img src="<?= base_url('img/lay8_logo.png') ?>" alt="LAY-8" style="height: 38px; width: auto;">
          <span style="font-family: var(--fuente-logo); font-size: 1.8rem; color: #ffffff; letter-spacing: 0.5px;">LAY-8</span>
        </div>
        <p>Prototipo funcional desarrollado exclusivamente con fines académicos para la Universidad Marítima del Caribe.</p>
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
        <p>Frontend</p>
      </div>

      <div class="footer-columna">
        <h4>CONTACTO</h4>
        <p style="margin-bottom: 8px;"><i class="bx bx-envelope" style="vertical-align: middle;"></i> lay8contacto@gmail.com</p>
        <p style="margin-top: 6px;">
          <a href="<?= base_url('terminos') ?>" style="color: #60a5fa; text-decoration: none; font-size: 0.88rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; transition: color 0.2s;">
            <i class="bx bx-shield-quarter"></i> Términos y Condiciones
          </a>
        </p>
      </div>

      <!-- Marca de agua timón en la esquina inferior derecha del footer -->
      <img src="<?= base_url('img/lay8_logo.png') ?>" alt="LAY-8 Marca de Agua" class="decoracion-logo">
    </footer>

    <script>window.BASE_URL = '<?= base_url() ?>';</script>
    <script src="<?= base_url('js/landing.js?v=' . time()) ?>"></script>

    

  </body>
</html>

