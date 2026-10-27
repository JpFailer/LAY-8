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
  
    <style id="estilos-rumbo-lay8">
/* ==========================================================================
   SECCIÓN DE MISIÓN, VISIÓN Y VALORES (LAY-8) - ESTILOS Y ANIMACIONES
   ========================================================================== */

.seccion-rumbo-lay8 {
  width: 100%;
  padding: 90px 6% 110px;
  background: linear-gradient(180deg, #f8fafc 0%, #edf2f7 50%, #e2e8f0 100%);
  position: relative;
  overflow: hidden;
  box-sizing: border-box;
}

/* Fondo con efecto sutil de resplandor */
.seccion-rumbo-lay8::before {
  content: '';
  position: absolute;
  top: -150px;
  left: 50%;
  transform: translateX(-50%);
  width: 800px;
  height: 500px;
  background: radial-gradient(circle, rgba(37, 99, 235, 0.08) 0%, rgba(37, 99, 235, 0) 70%);
  pointer-events: none;
  z-index: 0;
}

.rumbo-header {
  text-align: center;
  max-width: 840px;
  margin: 0 auto 55px;
  position: relative;
  z-index: 1;
}

.insignia-pill-rumbo {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  color: #1e40af;
  border: 1px solid #bfdbfe;
  padding: 8px 20px;
  border-radius: 9999px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  box-shadow: 0 4px 12px rgba(30, 64, 175, 0.08);
  margin-bottom: 18px;
  cursor: default;
  user-select: none;
}

.insignia-pill-rumbo i {
  font-size: 1.15rem;
  color: #2563eb;
}

.rumbo-titulo {
  font-family: 'Montserrat', sans-serif;
  font-size: 2.6rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.25;
  margin: 0 0 16px 0;
}

.rumbo-gradient-text {
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #0284c7 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  display: inline-block;
}

.divisor-decorativo {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0 auto 20px;
  width: 90px;
  height: 4px;
  background: linear-gradient(90deg, transparent, #2563eb, transparent);
  border-radius: 4px;
  position: relative;
}

.divisor-decorativo .punto-linea {
  width: 10px;
  height: 10px;
  background: #1e3a8a;
  border: 2px solid #ffffff;
  border-radius: 50%;
  box-shadow: 0 0 8px rgba(37, 99, 235, 0.5);
}

.rumbo-subtitulo {
  font-family: 'Hind', sans-serif;
  font-size: 1.05rem;
  color: #475569;
  line-height: 1.7;
  margin: 0 auto;
}

/* Grilla de Tarjetas */
.rumbo-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(330px, 1fr));
  gap: 32px;
  max-width: 1280px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

/* Estilo Base de las Tarjetas */
.card-filosofia {
  background: #ffffff;
  border-radius: 22px;
  padding: 38px 32px;
  box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.03);
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.card-filosofia:hover {
  transform: translateY(-8px);
  box-shadow: 0 24px 38px -12px rgba(15, 23, 42, 0.12), 0 12px 16px -8px rgba(15, 23, 42, 0.06);
}

/* Barra superior de acento con gradiente */
.card-glow-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 5px;
}

.glow-blue {
  background: linear-gradient(90deg, #1d4ed8, #60a5fa);
}

.glow-purple {
  background: linear-gradient(90deg, #7c3aed, #c084fc);
}

.glow-green {
  background: linear-gradient(90deg, #059669, #34d399);
}

.card-mision-animada:hover {
  border-color: rgba(37, 99, 235, 0.35);
}

.card-vision-animada:hover {
  border-color: rgba(124, 58, 237, 0.35);
}

.card-valores-animada:hover {
  border-color: rgba(5, 150, 105, 0.35);
}

/* Cabecera interna de cada tarjeta */
.card-top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

/* Íconos Flotantes */
.icono-flotante {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.95rem;
  transition: transform 0.3s ease;
}

.icono-box-mision {
  background: #eff6ff;
  color: #2563eb;
  box-shadow: 0 8px 18px -4px rgba(37, 99, 235, 0.25);
}

.icono-box-vision {
  background: #faf5ff;
  color: #7c3aed;
  box-shadow: 0 8px 18px -4px rgba(124, 58, 237, 0.25);
}

.icono-box-valores {
  background: #ecfdf5;
  color: #059669;
  box-shadow: 0 8px 18px -4px rgba(5, 150, 105, 0.25);
}

.card-filosofia:hover .icono-flotante {
  transform: scale(1.08) rotate(3deg);
}

/* Tags/Badges */
.badge-card-tipo {
  font-family: 'Montserrat', sans-serif;
  font-size: 0.73rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 6px 14px;
  border-radius: 9999px;
  cursor: default;
  user-select: none;
}

.tag-blue {
  background: #e0e7ff;
  color: #3730a3;
  border: 1px solid #c7d2fe;
}

.tag-purple {
  background: #f3e8ff;
  color: #6b21a8;
  border: 1px solid #e9d5ff;
}

.tag-green {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

/* Títulos y textos */
.card-filosofia-titulo {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.55rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 14px 0;
}

.card-filosofia-desc {
  font-family: 'Hind', sans-serif;
  font-size: 0.98rem;
  color: #475569;
  line-height: 1.72;
  margin: 0 0 26px 0;
  flex-grow: 1;
}

/* Chips de aspectos destacados */
.card-highlights {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: auto;
  padding-top: 15px;
  border-top: 1px dashed #e2e8f0;
}

.highlight-chip {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 6px 13px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #334155;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.25s ease;
}

.highlight-chip i {
  font-size: 1rem;
  color: #1e40af;
}

.highlight-chip:hover {
  background: #ffffff;
  border-color: #94a3b8;
  color: #0f172a;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

/* Lista interactiva de Valores */
.lista-valores-moderna {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
  flex-grow: 1;
}

.lista-valores-moderna li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #f8fafc;
  padding: 10px 14px;
  border-radius: 14px;
  border: 1px solid #f1f5f9;
  transition: all 0.25s ease;
}

.lista-valores-moderna li:hover {
  background: #ffffff;
  border-color: #a7f3d0;
  transform: translateX(5px);
  box-shadow: 0 4px 10px rgba(5, 150, 105, 0.08);
}

.valor-icon-wrapper {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: #ecfdf5;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.18rem;
  flex-shrink: 0;
  margin-top: 2px;
  transition: transform 0.25s ease;
}

.lista-valores-moderna li:hover .valor-icon-wrapper {
  transform: scale(1.12);
  background: #059669;
  color: #ffffff;
}

.valor-text-wrapper {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.valor-text-wrapper strong {
  font-family: 'Montserrat', sans-serif;
  font-size: 0.88rem;
  font-weight: 700;
  color: #0f172a;
}

.valor-text-wrapper span {
  font-family: 'Hind', sans-serif;
  font-size: 0.82rem;
  color: #64748b;
  line-height: 1.45;
}

/* Responsive */
@media (max-width: 992px) {
  .rumbo-cards-grid {
    grid-template-columns: 1fr;
    max-width: 600px;
  }
}

@media (max-width: 768px) {
  .seccion-rumbo-lay8 {
    padding: 60px 5% 80px;
  }
  .rumbo-titulo {
    font-size: 2rem;
  }
  .card-filosofia {
    padding: 30px 22px;
  }
}
</style>
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
            Tecnología desarrollada por estudiantes de la <b>Universidad Marítima del Caribe (UMC)</b> para modernizar la orientación en el campus, la gestión de horarios y el aprendizaje colaborativo.
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
        <button type="button" class="btn-close" onclick="cerrarModalRecuperacion()" style="position: absolute; top: 12px; right: 15px; background: none; border: none; font-size: 1.8rem; cursor: pointer; color: #64748b; z-index: 10;">&times;</button>
        
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

          <!-- PASO 2: Verificación de Identidad con PDF y Nueva Contraseña -->
          <div id="recup-paso-2" style="display: none;">
            <div style="text-align: center; margin-bottom: 12px;">
              <div style="width: 46px; height: 46px; background: #eff6ff; color: #1e40af; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 6px auto;">
                <i class="bx bx-shield-quarter"></i>
              </div>
              <h3 style="font-family: 'Montserrat', sans-serif; font-size: 1.25rem; color: #0f172a; margin: 0 0 4px 0; font-weight: 700;">Verificación de Identidad</h3>
              <div style="background: #f1f5f9; border-radius: 8px; padding: 4px 12px; font-size: 0.82rem; color: #1e40af; display: inline-block; font-weight: 600;" id="recup-usuario-info">
                Usuario Verificado
              </div>
            </div>

            <!-- AVISO DE SEGURIDAD UMC ANTI-SUPLANTACIÓN COMPACTO -->
            <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 10px; padding: 10px 12px; margin-bottom: 14px;">
              <div style="display: flex; align-items: flex-start; gap: 8px;">
                <i class="bx bx-shield-check" style="font-size: 1.4rem; color: #1e40af; flex-shrink: 0; margin-top: 1px;"></i>
                <div style="font-size: 0.8rem; color: #1e3a8a; line-height: 1.4;">
                  <strong style="display: block; font-size: 0.84rem; margin-bottom: 1px;">Comprobación de Titularidad Anti-Suplantación</strong>
                  Para comprobar que eres el titular legítimo, sube tu <strong>Registro de Inscripción oficial UMC (PDF)</strong> emitido por <code>campus.umc.edu.ve</code> (válido el original o uno más reciente).
                </div>
              </div>
            </div>

            <div id="recup-alerta-error-2" style="display: none; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 8px 12px; border-radius: 8px; font-size: 0.84rem; margin-bottom: 12px;"></div>

            <input type="hidden" id="recup_usuario_id">

            <!-- Campo de subida de PDF obligatorio -->
            <div style="margin-bottom: 14px;">
              <label style="font-size: 0.84rem; font-weight: 700; color: #1e3a8a; display: block; margin-bottom: 4px;">
                <i class="bx bxs-file-pdf" style="color: #dc2626;"></i> Registro de Inscripción UMC (PDF) *
              </label>
              <input type="file" id="recup_pdf" accept=".pdf" class="input-login" style="width: 100%; box-sizing: border-box; padding: 8px 12px;" required onchange="actualizarFeedbackPdfRecup(this)">
              
              <!-- Feedback visual de PDF seleccionado -->
              <div id="recup-pdf-feedback" style="display: none; align-items: center; justify-content: space-between; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 8px; padding: 6px 10px; margin-top: 6px;">
                <div style="display: flex; align-items: center; gap: 6px; overflow: hidden;">
                  <i class="bx bxs-file-pdf" style="font-size: 1.3rem; color: #dc2626; flex-shrink: 0;"></i>
                  <div style="display: flex; flex-direction: column; overflow: hidden;">
                    <span id="recup-pdf-nombre" style="font-size: 0.8rem; font-weight: 600; color: #166534; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">documento.pdf</span>
                    <span id="recup-pdf-tamano" style="font-size: 0.72rem; color: #15803d;">0 KB</span>
                  </div>
                </div>
                <span style="font-size: 0.72rem; font-weight: 700; background: #dcfce7; color: #166534; padding: 2px 7px; border-radius: 9999px;"><i class="bx bx-check"></i> Listo</span>
              </div>
            </div>

            <!-- Campos de Contraseña lado a lado en 2 columnas para optimizar espacio vertical -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
              <div>
                <label style="font-size: 0.83rem; font-weight: 600; color: #334155; display: block; margin-bottom: 4px;">Nueva Contraseña *</label>
                <input type="password" id="recup_nueva_clave" class="input-login" style="width: 100%; box-sizing: border-box; padding: 10px 12px;" placeholder="Mín. 8 caracteres">
              </div>

              <div>
                <label style="font-size: 0.83rem; font-weight: 600; color: #334155; display: block; margin-bottom: 4px;">Confirmar Contraseña *</label>
                <input type="password" id="recup_repetir_clave" class="input-login" style="width: 100%; box-sizing: border-box; padding: 10px 12px;" placeholder="Repite la contraseña">
              </div>
            </div>

            <button type="button" id="btn-guardar-clave" onclick="ejecutarGuardarClave()" class="btn-ingresar" style="width: 100%; margin-top: 0; padding: 12px; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
              <i class="bx bx-check-shield"></i> Validar Documento y Restablecer
            </button>
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
            ¿Problemas con tu cuenta? Escribe a <a href="mailto:info@lay8.com" style="color: #2563eb; text-decoration: underline;">info@lay8.com</a>
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
        <p style="margin-bottom: 8px;"><i class="bx bx-envelope" style="vertical-align: middle;"></i> info@lay8.com</p>
        <p style="margin-top: 6px;">
          <a href="<?= base_url('terminos') ?>" style="color: #60a5fa; text-decoration: none; font-size: 0.88rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; transition: color 0.2s;">
            <i class="bx bx-shield-quarter"></i> Términos y Condiciones
          </a>
        </p>
      </div>

      <!-- Marca de agua timón en la esquina inferior derecha del footer -->
      <img src="<?= base_url('img/lay8_logo.png') ?>" alt="LAY-8 Marca de Agua" class="decoracion-logo">
    </footer>

    <script src="<?= base_url('js/landing.js?v=' . time()) ?>"></script>

    <!-- Script de Recuperación de Contraseña Garantizado -->
    <script>
      function abrirModalRecuperacion() {
        const modalLogin = document.getElementById('modal-login-overlay');
        const modalRecup = document.getElementById('modal-recuperacion-pass');
        if (modalLogin) modalLogin.classList.add('oculto');
        if (modalRecup) {
          modalRecup.classList.remove('oculto');
          document.body.style.overflow = 'hidden';
          // Resetear al paso 1
          document.getElementById('recup-paso-1').style.display = 'block';
          document.getElementById('recup-paso-2').style.display = 'none';
          document.getElementById('recup-paso-3').style.display = 'none';
          const errBox = document.getElementById('recup-alerta-error');
          if (errBox) errBox.style.display = 'none';
        }
      }

      function cerrarModalRecuperacion() {
        const modalRecup = document.getElementById('modal-recuperacion-pass');
        if (modalRecup) {
          modalRecup.classList.add('oculto');
          document.body.style.overflow = '';
        }
      }

      function ejecutarVerificarCuenta() {
        const iden = document.getElementById('recup_identificador').value.trim();
        const errBox = document.getElementById('recup-alerta-error');
        const btn = document.getElementById('btn-verificar-recup');

        if (!iden) {
          errBox.textContent = 'Por favor ingresa tu usuario o correo.';
          errBox.style.display = 'block';
          return;
        }

        errBox.style.display = 'none';
        btn.textContent = 'Verificando...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('identificador', iden);

        fetch('<?= base_url('login/verificar-recuperacion') ?>', {
          method: 'POST',
          body: formData
        })
        .then(r => r.json())
        .then(data => {
          btn.textContent = 'Verificar Cuenta';
          btn.disabled = false;
          if (data.exito) {
            document.getElementById('recup_usuario_id').value = data.usuario_id;
            document.getElementById('recup-usuario-info').innerHTML = '<i class="bx bxs-user-check"></i> ' + data.nombre + ' (' + data.rol + ' - ' + data.carrera + ')';
            document.getElementById('recup-paso-1').style.display = 'none';
            document.getElementById('recup-paso-2').style.display = 'block';
          } else {
            errBox.textContent = data.mensaje || 'Cuenta no encontrada.';
            errBox.style.display = 'block';
          }
        })
        .catch(err => {
          btn.textContent = 'Verificar Cuenta';
          btn.disabled = false;
          errBox.textContent = 'Error al verificar la cuenta. Intenta de nuevo.';
          errBox.style.display = 'block';
        });
      }

      function actualizarFeedbackPdfRecup(input) {
        const feedback = document.getElementById('recup-pdf-feedback');
        const nombreSpan = document.getElementById('recup-pdf-nombre');
        const tamanoSpan = document.getElementById('recup-pdf-tamano');
        if (input.files && input.files[0]) {
          const file = input.files[0];
          if (nombreSpan) nombreSpan.textContent = file.name;
          if (tamanoSpan) {
            const kb = (file.size / 1024).toFixed(1);
            tamanoSpan.textContent = (kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB');
          }
          if (feedback) feedback.style.display = 'flex';
        }
      }

      function ejecutarGuardarClave() {
        const uid = document.getElementById('recup_usuario_id').value;
        const clave = document.getElementById('recup_nueva_clave').value;
        const rep = document.getElementById('recup_repetir_clave').value;
        const fileInput = document.getElementById('recup_pdf');
        const errBox = document.getElementById('recup-alerta-error-2');
        const btn = document.getElementById('btn-guardar-clave');

        if (!fileInput.files || fileInput.files.length === 0) {
          errBox.textContent = 'Por seguridad anti-suplantación, debes adjuntar obligatoriamente tu Registro de Inscripción UMC en formato PDF.';
          errBox.style.display = 'block';
          return;
        }

        const archivo = fileInput.files[0];
        if (!archivo.name.toLowerCase().endsWith('.pdf')) {
          errBox.textContent = 'El comprobante debe ser exclusivamente un archivo digital PDF oficial.';
          errBox.style.display = 'block';
          return;
        }

        if (!clave || clave.length < 8) {
          errBox.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
          errBox.style.display = 'block';
          return;
        }

        if (clave !== rep) {
          errBox.textContent = 'Las contraseñas no coinciden.';
          errBox.style.display = 'block';
          return;
        }

        errBox.style.display = 'none';
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Verificando documento con motor UMC...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('usuario_id', uid);
        formData.append('nueva_contrasena', clave);
        formData.append('repetir_contrasena', rep);
        formData.append('planilla_inscripcion', archivo);

        fetch('<?= base_url('login/restablecer-contrasena') ?>', {
          method: 'POST',
          body: formData
        })
        .then(r => r.json())
        .then(data => {
          btn.innerHTML = '<i class="bx bx-check-shield"></i> Validar Documento y Restablecer';
          btn.disabled = false;
          if (data.exito) {
            const exitoMsg = document.getElementById('recup-mensaje-exito');
            if (exitoMsg) exitoMsg.textContent = data.mensaje;
            document.getElementById('recup-paso-2').style.display = 'none';
            document.getElementById('recup-paso-3').style.display = 'block';
          } else {
            errBox.textContent = data.mensaje || 'Error al validar el documento o actualizar contraseña.';
            errBox.style.display = 'block';
          }
        })
        .catch(err => {
          btn.innerHTML = '<i class="bx bx-check-shield"></i> Validar Documento y Restablecer';
          btn.disabled = false;
          errBox.textContent = 'Ocurrió un error al verificar el comprobante en el servidor.';
          errBox.style.display = 'block';
        });
      }

      function volverALoginDesdeRecuperacion() {
        cerrarModalRecuperacion();
        const modalLogin = document.getElementById('modal-login-overlay');
        if (modalLogin) {
          modalLogin.classList.remove('oculto');
          document.body.style.overflow = 'hidden';
        }
      }
    </script>

  </body>
</html>

