<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>

<div class="feed-contenedor-principal">
  <div class="feed-csrf-hidden"><?= csrf_field() ?></div>

  <?php
    $estadoVerificacion = $estadoVerificacion ?? (session()->get('estado_verificacion') ?? 'no_aplica');
    $esAdminSistema     = $esAdminSistema ?? in_array($userRol, ['admin', 'administrador'], true);
    $esProfesorAprobado = $esProfesorAprobado ?? ($userRol === 'profesor' && $estadoVerificacion === 'aprobado');
  ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alerta-notif alerta-error feed-alerta-mb">
      <i class="bx bx-error-circle feed-alerta-ico"></i>
      <span><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('mensaje')): ?>
    <div class="alerta-notif alerta-exito feed-alerta-mb">
      <i class="bx bx-check-circle feed-alerta-ico"></i>
      <span><?= esc(session()->getFlashdata('mensaje')) ?></span>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('info')): ?>
    <div class="alerta-notif feed-alerta-mb feed-alerta-info">
      <i class="bx bx-info-circle feed-alerta-ico"></i>
      <span><?= esc(session()->getFlashdata('info')) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($userRol === 'profesor' && !$esProfesorAprobado): ?>
    <div class="alerta-notif feed-alerta-mb feed-alerta-aviso">
      <i class="bx bx-time-five feed-alerta-ico-lg"></i>
      <span><strong>Verificación docente pendiente:</strong> Tu carnet o constancia institucional está en revisión por un administrador. El acceso al Panel Docente se habilitará tras su aprobación.</span>
    </div>
  <?php endif; ?>

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
      <h2>
        <?= $saludoMomento ?>, <?= esc($primerNombre) ?>!
        <?php if ($esAdminSistema): ?>
          <i class="bx bxs-check-circle feed-verif-ico feed-verif-admin" title="Administrador Verificado"></i>
        <?php elseif ($esProfesorAprobado): ?>
          <i class="bx bxs-check-circle feed-verif-ico feed-verif-prof" title="Profesor Verificado"></i>
        <?php endif; ?>
      </h2>
      
      <div class="welcome-meta-chips">
        <span class="welcome-chip">
          <i class="bx bx-calendar"></i> <?= esc($fechaTexto) ?>
        </span>
        <span class="welcome-chip">
          <i class="bx bx-shield-quarter"></i>
          <?= ($userRol === 'profesor') ? ($esProfesorAprobado ? 'Cuerpo Docente UMC' : 'Docente (Pendiente de Verificación)') : ($esAdminSistema ? 'Panel de Administración' : 'Estudiante Pregrado') ?>
        </span>
      </div>
    </div>

    <div class="welcome-badge-icon">
      <i class="bx bx-anchor"></i>
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
                      <i class="bx bx-map-pin feed-ico-pin"></i>
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
              <?php if ($esProfesorAprobado): ?>
                <a href="<?= base_url('docente') ?>" class="btn-empty-jornada-secondary">
                  <i class="bx bx-chalkboard"></i> Ir a Panel Docente
                </a>
              <?php elseif ($userRol !== 'profesor'): ?>
                <a href="<?= base_url('aulas?tab=horario') ?>" class="btn-empty-jornada-secondary">
                  <i class="bx bx-time"></i> Ver Horario Semanal
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <!-- ===================================================================== -->
      <!-- B. COMPOSER DE COMUNICADOS (SOLO DOCENTES VERIFICADOS Y ADMINS)       -->
      <!-- ===================================================================== -->
      <?php if ($esProfesorAprobado || $esAdminSistema): ?>
        <section class="composer-noticia-card">
          <div class="composer-header-row">
            <i class="bx bx-edit-alt"></i>
            <h3>Emitir Comunicado Oficial</h3>
          </div>

          <form id="formPublicarNoticia" class="form-publicar-noticia" onsubmit="publicarNoticiaFeed(event)">
            <?= csrf_field() ?>
            <input type="text" name="titulo" id="composer-titulo" class="composer-input-titulo" placeholder="Título del comunicado o anuncio universitario..." required>
            <textarea name="contenido" id="composer-contenido" class="composer-textarea-cuerpo" placeholder="Escribe aquí los detalles del aviso, directrices de clase o novedades para la comunidad..." required></textarea>

            <div class="composer-bottom-controls">
              <div class="feed-composer-left">
                <select name="categoria" id="composer-categoria" class="composer-select-categoria">
                  <option value="Institucional">Institucional</option>
                  <option value="Académico">Académico</option>
                  <option value="Urgente">Urgente / Importante</option>
                  <option value="Evento">Evento Náutico / Cultural</option>
                  <option value="Comunidad">Comunidad Estudiantil</option>
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
        <div class="card-header-feed feed-header-mb">
          <h3>
            <i class="bx bx-news"></i>
            Tablón de Noticias y Comunicados UMC
          </h3>
          <span class="feed-count-muted">
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
                      <?php if (in_array($rolAutor, ['admin', 'administrador'])): ?>
                        <span class="check-verificado-admin" title="Administrador Verificado"><i class="bx bxs-check-circle"></i></span>
                      <?php elseif ($rolAutor === 'profesor'): ?>
                        <span class="check-verificado-profesor" title="Profesor Verificado"><i class="bx bxs-check-circle"></i></span>
                      <?php endif; ?>
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
                            <?php if (in_array($coment['rol'], ['admin', 'administrador'])): ?>
                              <span class="check-verificado-admin" title="Administrador Verificado" style="color: #2563eb; font-size: 1.05rem; display: inline-flex; align-items: center; margin-left: 2px;"><i class="bx bxs-check-circle"></i></span>
                            <?php elseif ($coment['rol'] === 'profesor'): ?>
                              <span class="check-verificado-profesor" title="Profesor Verificado" style="color: #10b981; font-size: 1.05rem; display: inline-flex; align-items: center; margin-left: 2px;"><i class="bx bxs-check-circle"></i></span>
                            <?php endif; ?>
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
                                  <?php if (in_array($resp['rol'], ['admin', 'administrador'])): ?>
                                    <span class="check-verificado-admin" title="Administrador Verificado" style="color: #2563eb; font-size: 1.05rem; display: inline-flex; align-items: center; margin-left: 2px;"><i class="bx bxs-check-circle"></i></span>
                                  <?php elseif ($resp['rol'] === 'profesor'): ?>
                                    <span class="check-verificado-profesor" title="Profesor Verificado" style="color: #10b981; font-size: 1.05rem; display: inline-flex; align-items: center; margin-left: 2px;"><i class="bx bxs-check-circle"></i></span>
                                  <?php endif; ?>
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

          <?php if ($userRol === 'profesor'): ?>
            <!-- Accesos rápidos exclusivos para Profesores (Sin aulas ni horario de estudiantes) -->
            <?php if ($esProfesorAprobado): ?>
              <a href="<?= base_url('docente') ?>" class="btn-acceso-rapido">
                <div class="icono-acceso-rapido" style="background: #dcfce7; color: #059669;">
                  <i class="bx bx-chalkboard"></i>
                </div>
                <span>Panel Docente</span>
              </a>
            <?php endif; ?>

            <a href="<?= base_url('biblioteca/subir') ?>" class="btn-acceso-rapido">
              <div class="icono-acceso-rapido" style="background: #e0f2fe; color: #0284c7;">
                <i class="bx bx-cloud-upload"></i>
              </div>
              <span>Subir Aporte</span>
            </a>

            <a href="<?= base_url('biblioteca') ?>" class="btn-acceso-rapido">
              <div class="icono-acceso-rapido" style="background: #fef3c7; color: #d97706;">
                <i class="bx bx-book-reader"></i>
              </div>
              <span>Biblioteca</span>
            </a>
          <?php else: ?>
            <!-- Accesos rápidos para Estudiantes y Administradores -->
            <a href="<?= base_url('aulas?tab=horario') ?>" class="btn-acceso-rapido" title="Ver mi horario de clases semanal">
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

            <a href="<?= base_url('aulas') ?>" class="btn-acceso-rapido" title="Explorar aulas y materias de mi carrera">
              <div class="icono-acceso-rapido" style="background: #ffedd5; color: #c2410c;">
                <i class="bx bx-door-open"></i>
              </div>
              <span>Aulas UMC</span>
            </a>
          <?php endif; ?>
        </div>
      </div>



    </aside>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('js/feed.js') ?>"></script>
<?= $this->endSection() ?>
