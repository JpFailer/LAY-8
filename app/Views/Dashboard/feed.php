<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>

<div class="feed-contenedor-principal">

  <!-- ========================================================================= -->
  <!-- 1. BANNER DINÁMICO DE BIENVENIDA Y JORNADA                              -->
  <!-- ========================================================================= -->
  <div class="feed-welcome-banner">
    <div class="welcome-info-col">
      <?php
        $hora = (int)date('H');
        $saludoMomento = ($hora >= 5 && $hora < 12) ? '¡Buenos días' : (($hora >= 12 && $hora < 19) ? '¡Buenas tardes' : '¡Buenas noches');
        $primerNombre = session()->get('primer_nombre') ?? 'Navegante';
      ?>
      <h2><?= $saludoMomento ?>, <?= esc($primerNombre) ?>! <span style="font-size: 1.5rem;"></span></h2>
      <p>Bienvenido al centro de operaciones de tu vida académica en la Universidad Marítima del Caribe.</p>
      
      <div class="welcome-meta-chips">
        <span class="welcome-chip">
          <i class="bx bx-calendar"></i> <?= esc($fechaTexto) ?>
        </span>
        <span class="welcome-chip">
          <i class="bx bx-shield-quarter"></i>
          <?= ($userRol === 'profesor') ? 'Cuerpo Docente UMC' : (in_array($userRol, ['admin', 'administrador']) ? 'Panel de Administración' : 'Estudiante Pregrado') ?>
        </span>
      </div>
    </div>

    <div class="welcome-badge-icon">
      <i class="bx bxs-ship"></i>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- 2. GRID PRINCIPAL (COLUMNA MURO/JORNADA + COLUMNA LATERAL WIDGETS)       -->
  <!-- ========================================================================= -->
  <div class="feed-dashboard-grid">

    <!-- COLUMNA PRINCIPAL (IZQUIERDA) -->
    <div class="columna-principal-feed">

      <!-- ===================================================================== -->
      <!-- A. JORNADA DE HOY (CLASES DEL DÍA O ESTADO VACÍO)                     -->
      <!-- ===================================================================== -->
      <section class="seccion-card-feed" id="seccion-jornada-hoy">
        <div class="card-header-feed">
          <h3>
            <i class="bx bx-calendar-check"></i>
            Mi Jornada de Hoy
          </h3>
          <span class="pill-dia-actual"><?= esc($diaSemana) ?></span>
        </div>

        <?php if (!empty($clasesHoy)): ?>
          <div class="lista-clases-hoy">
            <?php foreach ($clasesHoy as $clase): ?>
              <div class="clase-item-feed <?= !empty($clase['en_curso']) ? 'en-curso' : '' ?>">
                <div class="clase-item-left">
                  <div class="clase-horario-pill">
                    <?= esc($clase['hora_inicio_formato']) ?> - <?= esc($clase['hora_fin_formato']) ?>
                  </div>
                  <div class="clase-datos">
                    <div class="clase-titulo-row">
                      <span class="clase-materia-nombre"><?= esc($clase['materia_nombre']) ?></span>
                      <span class="badge-secc">Secc. <?= esc($clase['codigo_seccion']) ?></span>
                      <?php if (!empty($clase['en_curso'])): ?>
                        <span class="badge-en-curso-pulse"><i class="bx bxs-circle"></i> EN CURSO</span>
                      <?php endif; ?>
                    </div>
                    <div class="clase-aula-info">
                      <i class="bx bx-map-pin" style="color: #2563eb;"></i>
                      <span><?= esc($clase['aula']) ?></span>
                    </div>
                  </div>
                </div>

                <div class="clase-item-right">
                  <a href="<?= base_url('mapa?aula=' . urlencode($clase['aula'])) ?>" class="btn-ubicar-mapa-feed" title="Ubicar aula en el mapa interactivo">
                    <i class="bx bx-compass"></i> Ver en Mapa
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <!-- Estado Vacío amigable cuando no hay clases hoy -->
          <div class="empty-jornada-box">
            <div class="empty-icon-jornada">
              <i class="bx bx-coffee"></i>
            </div>
            <h4>¡Hoy no tienes clases programadas!</h4>
            <p>Buen momento para repasar apuntes en la Biblioteca, organizar tus tareas o adelantar proyectos académicos.</p>
            <div class="empty-actions-row">
              <a href="<?= base_url('biblioteca') ?>" class="btn-empty-jornada-cta">
                <i class="bx bx-book-reader"></i> Explorar Biblioteca
              </a>
              <a href="<?= base_url('aulas') ?>" class="btn-empty-jornada-secondary">
                <i class="bx bx-time"></i> Ver Horario Semanal
              </a>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <!-- ===================================================================== -->
      <!-- B. COMPOSER DE COMUNICADOS (SOLO DOCENTES Y ADMINISTRADORES)          -->
      <!-- ===================================================================== -->
      <?php if (in_array($userRol, ['profesor', 'admin', 'administrador'], true)): ?>
        <section class="composer-noticia-card">
          <div class="composer-header-row">
            <i class="bx bx-edit-alt"></i>
            <h3>Emitir Comunicado Oficial</h3>
          </div>

          <form id="formPublicarNoticia" class="form-publicar-noticia" onsubmit="publicarNoticiaFeed(event)">
            <input type="text" name="titulo" id="composer-titulo" class="composer-input-titulo" placeholder="Título del comunicado o anuncio universitario..." required>
            <textarea name="contenido" id="composer-contenido" class="composer-textarea-cuerpo" placeholder="Escribe aquí los detalles del aviso, directrices de clase o novedades para la comunidad..." required></textarea>

            <div class="composer-bottom-controls">
              <div style="display: flex; align-items: center; gap: 10px;">
                <select name="categoria" id="composer-categoria" class="composer-select-categoria">
                  <option value="Institucional">📌 Institucional</option>
                  <option value="Académico">📚 Académico</option>
                  <option value="Urgente">🚨 Urgente / Importante</option>
                  <option value="Evento">⚓ Evento Náutico / Cultural</option>
                  <option value="Comunidad">👥 Comunidad Estudiantil</option>
                </select>

                <?php if (in_array($userRol, ['admin', 'administrador'], true)): ?>
                  <label class="checkbox-fijar-label">
                    <input type="checkbox" name="fijado" value="1">
                    <span>Fijar arriba</span>
                  </label>
                <?php endif; ?>
              </div>

              <button type="submit" class="btn-publicar-comunicado" id="btn-submit-comunicado">
                <i class="bx bx-paper-plane"></i> Publicar
              </button>
            </div>
          </form>
        </section>
      <?php endif; ?>

      <!-- ===================================================================== -->
      <!-- C. TABLÓN DE NOTICIAS CON LIKES E HILOS ESTILO REDDIT                 -->
      <!-- ===================================================================== -->
      <section class="seccion-tablon-muro">
        <div class="card-header-feed" style="margin-bottom: 8px;">
          <h3>
            <i class="bx bx-news"></i>
            Tablón de Noticias y Comunicados UMC
          </h3>
          <span style="font-size: 0.82rem; color: #64748b; font-weight: 600;">
            <?= count($noticias) ?> <?= (count($noticias) == 1) ? 'publicación' : 'publicaciones' ?>
          </span>
        </div>

        <?php if (!empty($noticias)): ?>
          <?php foreach ($noticias as $noticia): ?>
            <?php
              $idN = (int)$noticia['id'];
              $dioLike = !empty($noticia['usuario_dio_like']) && $noticia['usuario_dio_like'] > 0;
              $autorFoto = !empty($noticia['foto_perfil']) ? base_url($noticia['foto_perfil']) : base_url('img/usuario.png');
              $autorNombre = trim($noticia['primer_nombre'] . ' ' . $noticia['apellidos']);
              $rolAutor = $noticia['autor_rol'] ?? 'estudiante';
              $badgeRolClass = ($rolAutor === 'profesor') ? 'badge-docente' : (in_array($rolAutor, ['admin', 'administrador']) ? 'badge-admin' : 'badge-estudiante');
              $badgeRolTexto = ($rolAutor === 'profesor') ? 'Docente UMC' : (in_array($rolAutor, ['admin', 'administrador']) ? 'Administración' : 'Estudiante');

              $cat = $noticia['categoria'] ?? 'Institucional';
              $chipCatClass = 'chip-categoria-noticia';
              if (stripos($cat, 'urgente') !== false) $chipCatClass .= ' chip-urgente';
              elseif (stripos($cat, 'acad') !== false) $chipCatClass .= ' chip-academico';
              elseif (stripos($cat, 'evento') !== false) $chipCatClass .= ' chip-evento';
            ?>

            <article class="noticia-muro-card <?= !empty($noticia['fijado']) ? 'fijada' : '' ?>" id="noticia-<?= $idN ?>">
              <!-- Meta superior del autor -->
              <div class="noticia-top-meta">
                <div class="autor-info-row">
                  <img src="<?= $autorFoto ?>" alt="Avatar" class="autor-avatar-img">
                  <div class="autor-datos-col">
                    <div class="autor-nombre-line">
                      <span class="autor-nombre-txt"><?= esc($autorNombre) ?></span>
                      <span class="badge-rol-autor <?= $badgeRolClass ?>"><?= $badgeRolTexto ?></span>
                    </div>
                    <span class="noticia-fecha-txt"><?= date('d/m/Y \a \l\a\s h:i A', strtotime($noticia['created_at'])) ?></span>
                  </div>
                </div>

                <div class="noticia-tags-right">
                  <?php if (!empty($noticia['fijado'])): ?>
                    <span class="tag-aviso-fijado"><i class="bx bxs-pin"></i> Fijado</span>
                  <?php endif; ?>
                  <span class="<?= $chipCatClass ?>"><?= esc($cat) ?></span>

                  <?php if ((int)$noticia['autor_id'] === $usuarioId || in_array($userRol, ['admin', 'administrador'])): ?>
                    <button type="button" class="btn-eliminar-post" onclick="eliminarNoticiaPost(<?= $idN ?>)" title="Eliminar comunicado">
                      <i class="bx bx-trash"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Contenido principal -->
              <div class="noticia-cuerpo-contenido">
                <h4 class="noticia-titulo-txt"><?= esc($noticia['titulo']) ?></h4>
                <p class="noticia-texto-p"><?= nl2br(esc($noticia['contenido'])) ?></p>
              </div>

              <!-- Barra de Interacción (Likes y Comentarios) -->
              <div class="noticia-acciones-bar">
                <button type="button" 
                        class="btn-accion-interactiva btn-like-noticia <?= $dioLike ? 'dio-like' : '' ?>" 
                        id="btn-like-<?= $idN ?>"
                        onclick="toggleLikeNoticia(<?= $idN ?>)"
                        title="Confirmar recepción / Me gusta">
                  <i class="bx <?= $dioLike ? 'bxs-heart' : 'bx-heart' ?>"></i>
                  <span class="conteo-likes" id="conteo-likes-<?= $idN ?>"><?= (int)$noticia['total_likes'] ?></span>
                  <span>Me gusta</span>
                </button>

                <button type="button" 
                        class="btn-accion-interactiva btn-comentarios-toggle" 
                        id="btn-toggle-comentarios-<?= $idN ?>"
                        onclick="toggleHilosComentarios(<?= $idN ?>)"
                        title="Ver comentarios e hilos">
                  <i class="bx bx-comment-dots"></i>
                  <span class="conteo-comentarios" id="conteo-comentarios-<?= $idN ?>"><?= (int)$noticia['total_comentarios'] ?></span>
                  <span>Comentarios</span>
                </button>
              </div>

              <!-- Sección Desplegable de Comentarios e Hilos (Reddit-Style) -->
              <div class="seccion-hilos-comentarios" id="hilos-comentarios-<?= $idN ?>" style="display: none;">
                <!-- Caja para agregar nuevo comentario principal -->
                <form class="caja-nuevo-comentario-flex" onsubmit="enviarComentarioPrincipal(event, <?= $idN ?>)">
                  <input type="text" class="input-nuevo-comentario" id="input-comentario-<?= $idN ?>" placeholder="Escribe un comentario o pregunta..." required>
                  <button type="submit" class="btn-enviar-comentario">
                    <i class="bx bx-send"></i> Comentar
                  </button>
                </form>

                <!-- Lista de Hilos Anidados -->
                <div class="lista-hilos-contenedor" id="contenedor-hilos-<?= $idN ?>">
                  <?php if (!empty($noticia['hilos_comentarios'])): ?>
                    <?php foreach ($noticia['hilos_comentarios'] as $coment): ?>
                      <div class="comentario-item-wrapper" id="comentario-item-<?= $coment['id'] ?>">
                        <div class="comentario-burbuja">
                          <div class="comentario-header-row">
                            <img src="<?= !empty($coment['foto_perfil']) ? base_url($coment['foto_perfil']) : base_url('img/usuario.png') ?>" class="avatar-comentario" alt="Avatar">
                            <span class="nombre-comentarista"><?= esc($coment['primer_nombre'] . ' ' . $coment['apellidos']) ?></span>
                            <span class="rol-badge-comentario <?= ($coment['rol'] === 'profesor') ? 'badge-docente' : (in_array($coment['rol'], ['admin','administrador']) ? 'badge-admin' : 'badge-estudiante') ?>">
                              <?= ($coment['rol'] === 'profesor') ? 'Docente' : (in_array($coment['rol'], ['admin','administrador']) ? 'Admin' : 'Estudiante') ?>
                            </span>
                            <span class="tiempo-comentario"><?= date('d/m h:i A', strtotime($coment['created_at'])) ?></span>
                          </div>
                          <p class="texto-comentario-p"><?= esc($coment['contenido']) ?></p>
                          
                          <button type="button" class="btn-responder-comentario" onclick="abrirCajaRespuestaInline(<?= $idN ?>, <?= $coment['id'] ?>)">
                            <i class="bx bx-reply"></i> Responder
                          </button>

                          <!-- Caja de respuesta inline oculta -->
                          <div class="caja-respuesta-inline" id="caja-reply-<?= $coment['id'] ?>" style="display: none;">
                            <input type="text" class="input-respuesta-inline" id="input-reply-<?= $coment['id'] ?>" placeholder="Escribe tu respuesta a <?= esc($coment['primer_nombre']) ?>...">
                            <button type="button" class="btn-enviar-respuesta-inline" onclick="enviarRespuestaInline(<?= $idN ?>, <?= $coment['id'] ?>)">Responder</button>
                          </div>
                        </div>

                        <!-- Respuestas Anidadas (Hilo de Reddit) -->
                        <div class="hilo-respuestas-anidadas" id="respuestas-padre-<?= $coment['id'] ?>">
                          <?php if (!empty($coment['respuestas'])): ?>
                            <?php foreach ($coment['respuestas'] as $resp): ?>
                              <div class="comentario-burbuja" id="comentario-item-<?= $resp['id'] ?>">
                                <div class="comentario-header-row">
                                  <img src="<?= !empty($resp['foto_perfil']) ? base_url($resp['foto_perfil']) : base_url('img/usuario.png') ?>" class="avatar-comentario" alt="Avatar">
                                  <span class="nombre-comentarista"><?= esc($resp['primer_nombre'] . ' ' . $resp['apellidos']) ?></span>
                                  <span class="rol-badge-comentario <?= ($resp['rol'] === 'profesor') ? 'badge-docente' : (in_array($resp['rol'], ['admin','administrador']) ? 'badge-admin' : 'badge-estudiante') ?>">
                                    <?= ($resp['rol'] === 'profesor') ? 'Docente' : (in_array($resp['rol'], ['admin','administrador']) ? 'Admin' : 'Estudiante') ?>
                                  </span>
                                  <span class="tiempo-comentario"><?= date('d/m h:i A', strtotime($resp['created_at'])) ?></span>
                                </div>
                                <p class="texto-comentario-p"><?= esc($resp['contenido']) ?></p>
                              </div>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p style="font-size: 0.84rem; color: #94a3b8; text-align: center; margin: 10px 0;" class="msg-sin-comentarios">
                      Sé el primero en dejar un comentario o pregunta sobre este comunicado.
                    </p>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

    </div>

    <!-- ======================================================================= -->
    <!-- COLUMNA LATERAL (DERECHA): WIDGETS BIBLIOTECA + ACCESOS RÁPIDOS         -->
    <!-- ======================================================================= -->
    <aside class="columna-lateral-feed">

      <!-- WIDGET 1: APORTES RECIENTES DE LA BIBLIOTECA (CUADRO PEQUEÑO) -->
      <div class="widget-lateral-box">
        <h3>
          <i class="bx bx-book-reader"></i>
          Recién en Biblioteca
        </h3>

        <?php if (!empty($aportesRecientes)): ?>
          <div class="lista-mini-aportes">
            <?php foreach ($aportesRecientes as $ap): ?>
              <?php
                $ext = strtolower($ap['extension'] ?? pathinfo($ap['nombre_original'], PATHINFO_EXTENSION));
                $iconClass = 'mini-icon-otro';
                $iconBx = 'bx-file';
                if ($ext === 'pdf') {
                    $iconClass = 'mini-icon-pdf';
                    $iconBx = 'bxs-file-pdf';
                } elseif (in_array($ext, ['doc', 'docx'])) {
                    $iconClass = 'mini-icon-doc';
                    $iconBx = 'bxs-file-doc';
                }
              ?>
              <div class="mini-aporte-item">
                <div class="mini-aporte-icono <?= $iconClass ?>">
                  <i class="bx <?= $iconBx ?>"></i>
                </div>
                <div class="mini-aporte-detalles">
                  <span class="mini-aporte-titulo" title="<?= esc($ap['titulo']) ?>"><?= esc($ap['titulo']) ?></span>
                  <div class="mini-aporte-sub">
                    <span><?= esc($ap['primer_nombre'] ?? 'Compañero') ?></span> &bull; 
                    <span style="text-transform: capitalize;"><?= esc($ap['categoria']) ?></span>
                  </div>
                </div>
                <a href="<?= base_url('biblioteca/descargar/' . $ap['id']) ?>" class="btn-mini-descargar" title="Descargar recurso">
                  <i class="bx bx-download"></i>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
          <a href="<?= base_url('biblioteca') ?>" class="link-ver-todo-biblioteca">
            Ver los materiales de la biblioteca &rarr;
          </a>
        <?php else: ?>
          <p style="font-size: 0.84rem; color: #94a3b8; text-align: center; margin: 10px 0;">
            Aún no se han compartido archivos en la biblioteca. ¡Sé el primero en subir un aporte!
          </p>
          <a href="<?= base_url('biblioteca/subir') ?>" class="link-ver-todo-biblioteca">
            + Subir material académico
          </a>
        <?php endif; ?>
      </div>

      <!-- WIDGET 2: ACCESOS RÁPIDOS UNIVERSITARIOS -->
      <div class="widget-lateral-box">
        <h3>
          <i class="bx bx-bolt-circle"></i>
          Accesos Rápidos
        </h3>
        <div class="grid-accesos-rapidos">
          <a href="<?= base_url('mapa') ?>" class="btn-acceso-rapido">
            <div class="icono-acceso-rapido" style="background: #dbeafe; color: #2563eb;">
              <i class="bx bx-map-pin"></i>
            </div>
            <span>Mapa UMC</span>
          </a>

          <a href="<?= base_url('aulas') ?>" class="btn-acceso-rapido">
            <div class="icono-acceso-rapido" style="background: #f3e8ff; color: #7c3aed;">
              <i class="bx bx-time"></i>
            </div>
            <span>Mi Horario</span>
          </a>

          <a href="<?= base_url('biblioteca/subir') ?>" class="btn-acceso-rapido">
            <div class="icono-acceso-rapido" style="background: #dcfce7; color: #166534;">
              <i class="bx bx-cloud-upload"></i>
            </div>
            <span>Subir Aporte</span>
          </a>

          <a href="<?= base_url('aulas') ?>" class="btn-acceso-rapido">
            <div class="icono-acceso-rapido" style="background: #ffedd5; color: #c2410c;">
              <i class="bx bx-door-open"></i>
            </div>
            <span>Aulas UMC</span>
          </a>
        </div>
      </div>

      <!-- WIDGET 3: IDENTIDAD / RESUMEN DEL CAMPUS -->
      <div class="widget-lateral-box" style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);">
        <h3>
          <i class="bx bx-anchor"></i>
          Campus Inteligente
        </h3>
        <div style="font-size: 0.85rem; color: #475569; line-height: 1.6;">
          <p style="margin: 0 0 10px 0;">
            <i class="bx bxs-check-circle" style="color: #10b981;"></i> <strong>Sistemas activos:</strong> Monitoreo de disponibilidad de aulas y biblioteca colaborativa sincronizados.
          </p>
          <div style="padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 0.78rem; color: #64748b;">
            Universidad Nacional Experimental Marítima del Caribe &bull; LAY-8
          </div>
        </div>
      </div>

    </aside>

  </div>
</div>

<!-- ========================================================================= -->
<!-- 3. INTERACCIONES AJAX EN TIEMPO REAL (LIKES, COMUNICADOS, HILOS REDDIT)    -->
<!-- ========================================================================= -->
<script>
  const BASE_URL_FEED = '<?= base_url() ?>/';

  // A. Publicar Comunicado Oficial (Docentes y Admins)
  function publicarNoticiaFeed(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-comunicado');
    const form = document.getElementById('formPublicarNoticia');
    if (!form || !btn) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Publicando...';

    const formData = new FormData(form);

    fetch(BASE_URL_FEED + 'api/feed/publicar', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bx bx-paper-plane"></i> Publicar';

      if (res && res.success) {
        window.location.reload();
      } else {
        alert(res.mensaje || 'Error al publicar el comunicado.');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bx bx-paper-plane"></i> Publicar';
      console.error(err);
      alert('Error de conexión al emitir el comunicado.');
    });
  }

  // B. Alternar Me Gusta (Like/Unlike) con micro-animación
  function toggleLikeNoticia(noticiaId) {
    const btn = document.getElementById('btn-like-' + noticiaId);
    const conteoEl = document.getElementById('conteo-likes-' + noticiaId);
    if (!btn) return;

    fetch(BASE_URL_FEED + 'api/feed/toggle-like/' + noticiaId, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
        if (res && res.success) {
          if (res.liked) {
            btn.classList.add('dio-like');
            btn.querySelector('i').className = 'bx bxs-heart';
          } else {
            btn.classList.remove('dio-like');
            btn.querySelector('i').className = 'bx bx-heart';
          }
          if (conteoEl) {
            conteoEl.innerText = res.total_likes;
          }
        }
      })
      .catch(err => console.error(err));
  }

  // C. Toggle Desplegable de Comentarios
  function toggleHilosComentarios(noticiaId) {
    const seccion = document.getElementById('hilos-comentarios-' + noticiaId);
    const btn = document.getElementById('btn-toggle-comentarios-' + noticiaId);
    if (!seccion) return;

    const estaOculto = (seccion.style.display === 'none' || seccion.style.display === '');
    seccion.style.display = estaOculto ? 'flex' : 'none';
    if (btn) {
      if (estaOculto) btn.classList.add('abierto');
      else btn.classList.remove('abierto');
    }
  }

  // D. Enviar Comentario Principal
  function enviarComentarioPrincipal(e, noticiaId) {
    e.preventDefault();
    const input = document.getElementById('input-comentario-' + noticiaId);
    if (!input || !input.value.trim()) return;

    const texto = input.value.trim();
    input.value = '';

    const formData = new FormData();
    formData.append('contenido', texto);

    fetch(BASE_URL_FEED + 'api/feed/comentar/' + noticiaId, {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res && res.success && res.comentario) {
        const contenedor = document.getElementById('contenedor-hilos-' + noticiaId);
        const conteoEl = document.getElementById('conteo-comentarios-' + noticiaId);
        if (conteoEl) conteoEl.innerText = res.total_comentarios;

        if (contenedor) {
          const msgVacio = contenedor.querySelector('.msg-sin-comentarios');
          if (msgVacio) msgVacio.remove();

          const c = res.comentario;
          const avatarUrl = c.foto_perfil ? (BASE_URL_FEED + c.foto_perfil) : (BASE_URL_FEED + 'img/usuario.png');
          const badgeRolClass = (c.rol === 'profesor') ? 'badge-docente' : (['admin','administrador'].includes(c.rol) ? 'badge-admin' : 'badge-estudiante');
          const badgeRolTexto = (c.rol === 'profesor') ? 'Docente' : (['admin','administrador'].includes(c.rol) ? 'Admin' : 'Estudiante');

          const nuevoItemHtml = `
            <div class="comentario-item-wrapper" id="comentario-item-${c.id}">
              <div class="comentario-burbuja">
                <div class="comentario-header-row">
                  <img src="${avatarUrl}" class="avatar-comentario" alt="Avatar">
                  <span class="nombre-comentarista">${c.primer_nombre} ${c.apellidos}</span>
                  <span class="rol-badge-comentario ${badgeRolClass}">${badgeRolTexto}</span>
                  <span class="tiempo-comentario">Justo ahora</span>
                </div>
                <p class="texto-comentario-p">${c.contenido}</p>
                <button type="button" class="btn-responder-comentario" onclick="abrirCajaRespuestaInline(${noticiaId}, ${c.id})">
                  <i class="bx bx-reply"></i> Responder
                </button>
                <div class="caja-respuesta-inline" id="caja-reply-${c.id}" style="display: none;">
                  <input type="text" class="input-respuesta-inline" id="input-reply-${c.id}" placeholder="Escribe tu respuesta...">
                  <button type="button" class="btn-enviar-respuesta-inline" onclick="enviarRespuestaInline(${noticiaId}, ${c.id})">Responder</button>
                </div>
              </div>
              <div class="hilo-respuestas-anidadas" id="respuestas-padre-${c.id}"></div>
            </div>`;

          contenedor.insertAdjacentHTML('beforeend', nuevoItemHtml);
        }
      } else {
        alert(res.mensaje || 'Error al enviar comentario.');
      }
    })
    .catch(err => console.error(err));
  }

  // E. Abrir Caja de Respuesta Inline (Reddit style)
  function abrirCajaRespuestaInline(noticiaId, comentarioId) {
    const caja = document.getElementById('caja-reply-' + comentarioId);
    if (!caja) return;
    const isHidden = (caja.style.display === 'none' || caja.style.display === '');
    caja.style.display = isHidden ? 'flex' : 'none';
    if (isHidden) {
      const input = document.getElementById('input-reply-' + comentarioId);
      if (input) input.focus();
    }
  }

  // F. Enviar Respuesta Anidada
  function enviarRespuestaInline(noticiaId, parentId) {
    const input = document.getElementById('input-reply-' + parentId);
    if (!input || !input.value.trim()) return;

    const texto = input.value.trim();
    input.value = '';

    const caja = document.getElementById('caja-reply-' + parentId);
    if (caja) caja.style.display = 'none';

    const formData = new FormData();
    formData.append('contenido', texto);
    formData.append('parent_id', parentId);

    fetch(BASE_URL_FEED + 'api/feed/comentar/' + noticiaId, {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res && res.success && res.comentario) {
        const contenedorRespuestas = document.getElementById('respuestas-padre-' + parentId);
        const conteoEl = document.getElementById('conteo-comentarios-' + noticiaId);
        if (conteoEl) conteoEl.innerText = res.total_comentarios;

        if (contenedorRespuestas) {
          const c = res.comentario;
          const avatarUrl = c.foto_perfil ? (BASE_URL_FEED + c.foto_perfil) : (BASE_URL_FEED + 'img/usuario.png');
          const badgeRolClass = (c.rol === 'profesor') ? 'badge-docente' : (['admin','administrador'].includes(c.rol) ? 'badge-admin' : 'badge-estudiante');
          const badgeRolTexto = (c.rol === 'profesor') ? 'Docente' : (['admin','administrador'].includes(c.rol) ? 'Admin' : 'Estudiante');

          const respuestaHtml = `
            <div class="comentario-burbuja" id="comentario-item-${c.id}">
              <div class="comentario-header-row">
                <img src="${avatarUrl}" class="avatar-comentario" alt="Avatar">
                <span class="nombre-comentarista">${c.primer_nombre} ${c.apellidos}</span>
                <span class="rol-badge-comentario ${badgeRolClass}">${badgeRolTexto}</span>
                <span class="tiempo-comentario">Justo ahora</span>
              </div>
              <p class="texto-comentario-p">${c.contenido}</p>
            </div>`;

          contenedorRespuestas.insertAdjacentHTML('beforeend', respuestaHtml);
        }
      } else {
        alert(res.mensaje || 'Error al responder.');
      }
    })
    .catch(err => console.error(err));
  }

  // G. Eliminar Comunicado
  function eliminarNoticiaPost(noticiaId) {
    if (!confirm('¿Estás seguro de que deseas eliminar este comunicado del tablón universitario?')) {
      return;
    }

    fetch(BASE_URL_FEED + 'api/feed/eliminar/' + noticiaId, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
        if (res && res.success) {
          const card = document.getElementById('noticia-' + noticiaId);
          if (card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => card.remove(), 300);
          }
        } else {
          alert(res.mensaje || 'No se pudo eliminar.');
        }
      })
      .catch(err => console.error(err));
  }
</script>

<?= $this->endSection() ?>
