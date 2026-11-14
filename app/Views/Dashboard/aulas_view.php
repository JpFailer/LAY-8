<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/aulas.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="biblioteca-dashboard-contenedor aulas-contenedor">

    <!-- header aulas -->
    <div class="biblio-header-flex">
        <div>
            <h2 style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <i class="bx bx-door-open"></i> Aulas y Cronograma Académico
            </h2>
        </div>
        <div class="aulas-header-acciones">
            <?php if (!in_array(session('rol'), ['profesor'], true)): ?>
                <button type="button" class="btn-subir-cta" onclick="abrirModalExtemporanea()" title="Agregar materia extemporánea a tu registro">
                    <i class="bx bx-plus-circle"></i> Agregar Materia Extemporánea
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensajes Flash de Retroalimentación -->
    <?php if (session()->getFlashdata('mensaje')): ?>
        <div class="alerta-notif alerta-exito" style="margin-bottom: 20px;">
            <i class="bx bx-check-circle" style="font-size: 1.3rem;"></i>
            <span><?= esc(session()->getFlashdata('mensaje')) ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alerta-notif alerta-error" style="margin-bottom: 20px;">
            <i class="bx bx-error-circle" style="font-size: 1.3rem;"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- Pestañas de Navegación del Módulo de Aulas (Chips estilo Biblioteca) -->
    <nav class="filtros-categorias">
        <button type="button" class="btn-filtro activo" id="tabbtn-materias" onclick="cambiarTabAulas('materias')">
            <i class="bx bx-grid-alt"></i> Mis Materias (Aulas)
            <span class="badge-conteo"><?= (int) $totalMaterias ?></span>
        </button>

        <button type="button" class="btn-filtro" id="tabbtn-horario" onclick="cambiarTabAulas('horario')">
            <i class="bx bx-calendar"></i> Mi Horario Semanal
            <span class="badge-conteo"><?= count($horarioSemanal) ?></span>
        </button>

        <button type="button" class="btn-filtro" id="tabbtn-actividades" onclick="cambiarTabAulas('actividades')">
            <i class="bx bx-check-square"></i> Cronograma de Actividades
            <span class="badge-conteo" id="conteo-actividades"><?= count($actividades) ?></span>
        </button>
    </nav>

    <!-- PESTAÑA 1: MIS MATERIAS / AULAS VIRTUALES (Grid tipo Classroom)           -->
    <div id="tab-seccion-materias" class="tab-contenido-aulas">
        <?php if (!empty($secciones) && count($secciones) > 0): ?>
            <!-- Barra de Búsqueda y Filtros de Materias (Antidayanas & Case/Accent-Insensitive) -->
            <div class="aulas-search-toolbar">
                <div class="aulas-search-input-wrapper">
                    <i class="bx bx-search icono-search-aulas"></i>
                    <input type="text" 
                           id="buscador-materias-aulas" 
                           class="input-search-aulas" 
                           placeholder="Buscar materia por nombre (ej: Cálculo, Física), código (LOG-103), sección, aula o profesor..." 
                           autocomplete="off"
                           oninput="filtrarGridMaterias(this.value)">
                    <button type="button" id="btn-limpiar-search-materias" class="btn-clear-search-aulas" style="display: none;" onclick="limpiarBuscadorMaterias()" title="Limpiar búsqueda">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="aulas-search-meta">
                    <span id="conteo-materias-badge" class="badge-aulas-conteo">
                        <i class="bx bx-book-open"></i> Mostrando <b id="conteo-materias-num"><?= count($secciones) ?></b> de <?= count($secciones) ?> materias
                    </span>
                </div>
            </div>

            <!-- Estado vacío cuando la búsqueda no arroja resultados -->
            <div id="sin-resultados-busqueda-materias" class="panel-vacio-aulas panel-vacio-busqueda" style="display: none;">
                <div class="icono-vacio"><i class="bx bx-search-alt"></i></div>
                <h3>No se encontraron materias</h3>
                <p id="texto-sin-resultados">No hay materias que coincidan con la búsqueda. Puedes probar buscando sin acentos o por el código oficial.</p>
                <button type="button" class="btn-subir-cta" onclick="limpiarBuscadorMaterias()">
                    <i class="bx bx-refresh"></i> Ver todas las materias
                </button>
            </div>

            <div class="grid-aportes grid-aulas-classroom" id="grid-materias-aulas">
                <?php foreach ($secciones as $sec): ?>
                    <?php
                        $estadoHoy = $sec['estado_hoy'];
                        $esOnline = ($estadoHoy && $estadoHoy['modalidad'] === 'online');
                        $enlaceVirtual = ($estadoHoy && !empty($estadoHoy['enlace_virtual'])) ? $estadoHoy['enlace_virtual'] : null;
                        
                        // Extraer aulas asociadas a esta materia
                        $aulasAsignadas = [];
                        if (!empty($sec['horarios'])) {
                            foreach ($sec['horarios'] as $h) {
                                if (!in_array($h['aula'], $aulasAsignadas, true)) {
                                    $aulasAsignadas[] = $h['aula'];
                                }
                            }
                        }
                    ?>
                    <article class="tarjeta-aporte tarjeta-aula-classroom">
                        <!-- Cabecera de la Materia -->
                        <div class="cabecera-tarjeta-aula">
                            <div class="aula-header-info">
                                <span class="badge-codigo-materia"><?= esc($sec['codigo_materia']) ?></span>
                                <span class="badge-seccion-materia">Secc. <?= esc($sec['codigo_seccion']) ?></span>
                                <?php if (!empty($sec['carrera'])): ?>
                                    <span class="badge-carrera-biblioteca badge-carrera-<?= strtolower(esc($sec['carrera'])) ?>" style="font-size: 0.68rem; padding: 2px 6px;">
                                        <?= esc($sec['carrera']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($sec['semestre_trayecto'])): ?>
                                    <span class="badge-semestre">Semestre <?= esc($sec['semestre_trayecto']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="titulo-materia-aula"><?= esc($sec['materia']) ?></h3>
                            <p class="profesor-materia">
                                <i class="bx bx-user"></i> 
                                <?= (!empty($sec['prof_nombre']) || !empty($sec['prof_apellido'])) 
                                    ? esc(trim(($sec['prof_nombre'] ?? '') . ' ' . ($sec['prof_apellido'] ?? ''))) 
                                    : 'Docente UMC por asignar' ?>
                            </p>
                        </div>

                        <!-- Estado en Vivo del Docente -->
                        <div class="estado-docente-box <?= $esOnline ? 'box-online' : 'box-presencial' ?>">
                            <?php if ($estadoHoy): ?>
                                <div class="estado-docente-badge">
                                    <span class="punto-vivo <?= $esOnline ? 'vivo-azul' : 'vivo-verde' ?>"></span>
                                    <b>Estado de hoy:</b> <?= esc($estadoHoy['estado_rapido']) ?>
                                </div>
                                <?php if (!empty($estadoHoy['mensaje_adicional'])): ?>
                                    <p class="mensaje-docente-adicional"><i class="bx bx-comment-detail"></i> <?= esc($estadoHoy['mensaje_adicional']) ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="estado-docente-badge sin-estado">
                                    <span class="punto-vivo vivo-gris"></span>
                                    <span>Docente en espera de reporte para la sesión de hoy.</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Indicadores de Ubicación Física / Virtual -->
                        <div class="modalidad-ubicacion-box">
                            <?php if ($esOnline && !empty($enlaceVirtual)): ?>
                                <a href="<?= esc($enlaceVirtual) ?>" target="_blank" rel="noopener noreferrer" class="btn-cta-aula btn-aula-virtual">
                                    <i class="bx bx-video"></i> Ingresar a Sala Virtual (Meet / Zoom)
                                </a>
                            <?php elseif (!empty($aulasAsignadas)): ?>
                                <div class="aulas-badges-lista">
                                    <span class="etiqueta-aula-label"><i class="bx bx-buildings"></i> Aula física:</span>
                                    <?php foreach ($aulasAsignadas as $aulaItem): ?>
                                        <a href="<?= base_url('mapa?aula=' . urlencode($aulaItem)) ?>" class="btn-tag-mapa" title="Ubicar <?= esc($aulaItem) ?> en el mapa de la UMC">
                                            <i class="bx bx-map-pin"></i> <?= esc($aulaItem) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="sin-aula-asignada">
                                    <i class="bx bx-info-circle"></i> Aula pendiente de asignación en horario.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Horarios semanales de esta materia -->
                        <?php if (!empty($sec['horarios'])): ?>
                            <div class="resumen-horario-materia">
                                <span class="label-dias-clase"><i class="bx bx-time-five"></i> Sesiones programadas:</span>
                                <ul class="lista-mini-horarios">
                                    <?php foreach ($sec['horarios'] as $h): ?>
                                        <li>
                                            <b><?= esc($h['dia_semana']) ?></b>: 
                                            <?= date('H:i', strtotime($h['hora_inicio'])) ?> - <?= date('H:i', strtotime($h['hora_fin'])) ?> 
                                            <span class="aula-mini-tag">(<?= esc($h['aula']) ?>)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Pie de la Tarjeta con Actividades -->
                        <div class="meta-aula-footer">
                            <span class="total-actividades-badge" onclick="cambiarTabAulas('actividades')" title="Ver actividades en el cronograma">
                                <i class="bx bx-task"></i> <?= (int)$sec['total_actividades'] ?> actividad(es)
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="panel-vacio-aulas">
                <div class="icono-vacio"><i class="bx bx-calendar-x"></i></div>
                <h3>No tienes materias inscritas aún</h3>
                <p>Tus asignaturas y horarios se cargan automáticamente desde tu Registro de Inscripción oficial. Si cursas asignaturas adicionales o extemporáneas, puedes agregarlas desde aquí.</p>
                <?php if (!in_array(session('rol'), ['profesor'], true)): ?>
                    <button type="button" class="btn-subir-cta" onclick="abrirModalExtemporanea()">
                        <i class="bx bx-plus-circle"></i> Agregar Materia Extemporánea
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- PESTAÑA 2: MI HORARIO SEMANAL (Grilla oficial UMC)                         -->
    <div id="tab-seccion-horario" class="tab-contenido-aulas" style="display: none;">
        <div class="horario-toolbar">
            <div class="toolbar-info">
                <h3 class="aulas-toolbar-titulo">
                    <i class="bx bx-calendar-event"></i> Horario Académico Personal
                </h3>
            </div>
            <div class="toolbar-botones">
                <button type="button" class="btn-imprimir-horario" onclick="window.print()">
                    <i class="bx bx-printer"></i> Imprimir Horario
                </button>
                <button type="button" class="btn-imprimir-horario" onclick="confirmarRestaurarHorario()" title="Restaurar horario oficial asignado por la universidad">
                    <i class="bx bx-refresh aulas-ico-refresh"></i> Restaurar Horario Oficial
                </button>
                <button type="button" class="btn-imprimir-horario btn-limpiar-horario-action" onclick="confirmarLimpiarHorario()" title="Borrar todos los bloques de mi horario para empezar de nuevo">
                    <i class="bx bx-trash aulas-ico-trash"></i> Limpiar Mi Horario
                </button>
            </div>
        </div>

        <div class="contenedor-tabla-horario">
            <table class="tabla-horario-umc">
                <thead>
                    <tr>
                        <th class="aulas-col-bloque">Bloque / Turno</th>
                        <?php foreach ($diasSemana as $dia): ?>
                            <th><?= esc($dia) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bloquesOficiales as $turno => $bloques): ?>
                        <tr class="fila-separador-turno">
                            <td colspan="7">
                                <i class="bx <?= $turno === 'Mañana' ? 'bx-sun' : ($turno === 'Tarde' ? 'bx-brightness-half' : 'bx-moon') ?>"></i>
                                TURNO <?= strtoupper($turno) ?>
                            </td>
                        </tr>
                        <?php foreach ($bloques as $b): ?>
                            <tr>
                                <td class="celda-hora-bloque">
                                    <b><?= esc($b['etiqueta']) ?></b>
                                </td>
                                <?php foreach ($diasSemana as $dia): ?>
                                    <td class="celda-clase-horario">
                                        <?php
                                            // Buscar si hay clase programada en este día e intervalo
                                            $clasesEncontradas = [];
                                            if (!empty($grillaHorarios[$dia])) {
                                                foreach ($grillaHorarios[$dia] as $c) {
                                                    // Chequeo si la clase solapa o coincide con este bloque
                                                    if ($c['hora_inicio'] <= $b['inicio'] && $c['hora_fin'] >= $b['fin']) {
                                                        $clasesEncontradas[] = $c;
                                                    }
                                                }
                                            }
                                        ?>
                                        <?php if (!empty($clasesEncontradas)): ?>
                                            <?php foreach ($clasesEncontradas as $item): ?>
                                                <div class="bloque-clase-card" id="bloque-card-<?= (int)$item['id'] ?>">
                                                    <div class="bloque-codigo"><?= esc($item['codigo']) ?> (<?= esc($item['seccion']) ?>)</div>
                                                    <div class="bloque-materia"><?= esc($item['materia']) ?></div>
                                                    <div class="bloque-aula">
                                                        <a href="<?= base_url('mapa?aula=' . urlencode($item['aula'])) ?>" title="Ver aula en el plano del campus">
                                                            <i class="bx bx-map-pin"></i> <?= esc($item['aula']) ?>
                                                        </a>
                                                    </div>
                                                    <button type="button" class="btn-borrar-bloque" onclick="eliminarBloqueHorario(<?= (int)$item['id'] ?>, this)" title="Eliminar este bloque de mi horario">&times;</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PESTAÑA 3: CRONOGRAMA DE ACTIVIDADES (Classroom Tareas y Pendientes)       -->
    <div id="tab-seccion-actividades" class="tab-contenido-aulas" style="display: none;">
        <div class="actividades-toolbar">
            <div class="toolbar-info">
                <h3 class="aulas-toolbar-titulo">
                    <i class="bx bx-list-check"></i> Cronograma de Actividades y Tareas
                </h3>
            </div>
            <div class="filtros-actividades-chips">
                <button type="button" class="chip-actividad activo" onclick="filtrarActividades('todas')">Todas</button>
                <button type="button" class="chip-actividad" onclick="filtrarActividades('pendientes')">Pendientes</button>
                <button type="button" class="chip-actividad" onclick="filtrarActividades('completadas')">Completadas</button>
            </div>
        </div>

        <?php if (!empty($actividades) && count($actividades) > 0): ?>
            <div class="lista-actividades-classroom" id="lista-actividades-contenedor">
                <?php foreach ($actividades as $act): ?>
                    <?php
                        $tieneEntrega = !empty($act['entrega_id']);
                        $tieneCalificacion = isset($act['calificacion']) && $act['calificacion'] !== '' && $act['calificacion'] !== null;
                        $esCompletada = ((bool)$act['completada'] || $tieneEntrega || $tieneCalificacion);
                        $tipoLabels = [
                            'tarea'    => ['nombre' => 'Tarea', 'icono' => 'bx-task', 'color' => '#3b82f6'],
                            'parcial'  => ['nombre' => 'Parcial / Examen', 'icono' => 'bx-award', 'color' => '#ef4444'],
                            'taller'   => ['nombre' => 'Taller', 'icono' => 'bx-wrench', 'color' => '#f59e0b'],
                            'proyecto' => ['nombre' => 'Proyecto', 'icono' => 'bx-folder-open', 'color' => '#8b5cf6'],
                            'lectura'  => ['nombre' => 'Lectura', 'icono' => 'bx-book-open', 'color' => '#10b981'],
                        ];
                        $tipoConfig = $tipoLabels[$act['tipo']] ?? $tipoLabels['tarea'];
                        $timestampEntrega = strtotime($act['fecha_entrega']);
                        $hoyTimestamp = strtotime(date('Y-m-d'));
                        $estaVencida = ($timestampEntrega < time() && !$esCompletada);
                    ?>
                    <div class="item-actividad-card <?= $esCompletada ? 'actividad-completada' : ($estaVencida ? 'actividad-vencida' : '') ?>" 
                         data-estado="<?= $esCompletada ? 'completada' : 'pendiente' ?>">
                        <div class="check-columna">
                            <input type="checkbox" 
                                   id="chk_act_<?= $act['id'] ?>" 
                                   class="checkbox-actividad" 
                                   <?= $esCompletada ? 'checked' : '' ?> 
                                   onchange="toggleActividadCheckbox(<?= $act['id'] ?>, this)">
                        </div>
                        <div class="info-columna">
                            <div class="meta-tags-actividad">
                                <span class="badge-materia-tag"><?= esc($act['materia_nombre']) ?> (<?= esc($act['codigo_seccion']) ?>)</span>
                                <span class="badge-tipo-tag" style="background-color: <?= $tipoConfig['color'] ?>15; color: <?= $tipoConfig['color'] ?>; border-color: <?= $tipoConfig['color'] ?>40;">
                                    <i class="bx <?= $tipoConfig['icono'] ?>"></i> <?= $tipoConfig['nombre'] ?>
                                </span>
                                <?php if ((float)$act['ponderacion'] > 0): ?>
                                    <span class="badge-ponderacion-tag"><i class="bx bx-pie-chart-alt"></i> <?= number_format($act['ponderacion'], 1) ?>%</span>
                                <?php endif; ?>
                            </div>
                            <h4 class="titulo-actividad"><?= esc($act['titulo']) ?></h4>
                            <?php if (!empty($act['descripcion'])): ?>
                                <p class="descripcion-actividad"><?= esc($act['descripcion']) ?></p>
                            <?php endif; ?>

                            <!-- Estado de Entrega & Calificación Estudiante (Classroom) -->
                            <div class="aulas-actividad-estado-row">
                                <?php if ($tieneEntrega || $tieneCalificacion): ?>
                                    <span class="aulas-badge-entregada">
                                        <i class="bx bx-check-double"></i> Entregada <?= !empty($act['fecha_entrega_alumno']) ? date('d/m H:i', strtotime($act['fecha_entrega_alumno'])) : '' ?>
                                    </span>
                                    <?php if (!empty($act['entrega_archivo'])): ?>
                                        <a href="<?= base_url('aulas/descargar-mi-entrega/' . $act['id']) ?>" class="aulas-link-entrega">
                                            <i class="bx bx-paperclip"></i> <?= esc($act['entrega_archivo']) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($tieneCalificacion): ?>
                                        <span class="aulas-badge-calificacion">
                                            <i class="bx bxs-award aulas-ico-award"></i> Calificación: <?= rtrim(rtrim(number_format((float)$act['calificacion'], 2, '.', ''), '0'), '.') ?> pts
                                        </span>
                                        <span class="aulas-badge-evaluada">
                                            <i class="bx bx-lock-alt"></i> Evaluada
                                        </span>
                                        <?php if (!empty($act['feedback_profesor'])): ?>
                                            <span class="aulas-feedback-docente">
                                                Docente: "<?= esc($act['feedback_profesor']) ?>"
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="aulas-badge-pendiente">
                                            Pendiente por calificar
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!$tieneCalificacion): ?>
                                    <button type="button" class="btn-subir-cta aulas-btn-entregar" onclick="abrirModalEntregarTarea(<?= (int)$act['id'] ?>, '<?= esc($act['titulo'], 'js') ?>', '<?= esc($act['materia_nombre'], 'js') ?>')">
                                        <i class="bx bx-upload"></i> <?= $tieneEntrega ? 'Modificar Entrega' : 'Entregar Tarea' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="fecha-columna">
                            <span class="fecha-vencimiento <?= $estaVencida ? 'texto-vencido' : '' ?>">
                                <i class="bx bx-calendar-event"></i> <?= date('d/m/Y h:i A', $timestampEntrega) ?>
                            </span>
                            <?php if ($estaVencida): ?>
                                <span class="alerta-vencido-tag">Vencida</span>
                            <?php endif; ?>
                        </div>
                        <div class="acciones-columna">
                            <?php if ((int)$act['creado_por'] === (int)session('usuario_id') || in_array(session('rol'), ['admin', 'administrador', 'profesor'], true)): ?>
                                <form method="post" action="<?= base_url('aulas/eliminar-actividad/' . $act['id']) ?>" onsubmit="return Lay8UI.confirmSubmit(event, '¿Deseas eliminar esta actividad del cronograma?', { title: 'Eliminar actividad', confirmText: 'Eliminar', danger: true })">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-eliminar-actividad" title="Eliminar actividad">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="panel-vacio-aulas">
                <div class="icono-vacio"><i class="bx bx-task-x"></i></div>
                <h3>No hay actividades programadas</h3>
                <p>Aquí se reflejarán las tareas, talleres, proyectos y evaluaciones asignadas por tus profesores en cada una de tus materias. Actualmente te encuentras al día y no tienes actividades pendientes.</p>
            </div>
        <?php endif; ?>
    </div>

</div>




<!-- Modal: Entregar Tarea / Subir Archivo de Evaluación (Classroom) -->
<div id="modalEntregarTarea" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalEntregarTarea()">
    <div class="modal-contenido-aula modal-actividad-ancho aulas-modal-sm">
        <div class="modal-aula-header">
            <h3><i class="bx bx-upload"></i> Entregar Asignación</h3>
            <span onclick="cerrarModalEntregarTarea()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
        </div>
        <form id="formEntregarTareaAlumno" method="post" enctype="multipart/form-data" class="formulario-estilo-lay8 aulas-modal-form-pad">
            <?= csrf_field() ?>
            <div class="aulas-entrega-resumen-box">
                <strong id="entregar_modal_titulo" class="aulas-entrega-resumen-titulo"></strong>
                <span id="entregar_modal_materia" class="aulas-entrega-resumen-materia"></span>
            </div>

            <div class="campo-grupo">
                <label for="input_archivo_tarea"><i class="bx bx-file"></i> Archivo de la Tarea (PDF, Word, ZIP, JPG):</label>
                <input type="file" name="archivo_tarea" id="input_archivo_tarea" accept=".pdf,.doc,.docx,.zip,.rar,.png,.jpg,.jpeg" class="input-lay8 aulas-input-file-pad">
            </div>

            <div class="campo-grupo">
                <label for="comentario_tarea_alumno"><i class="bx bx-comment-detail"></i> Comentario / Observación para el Docente (Opcional):</label>
                <textarea name="comentario" id="comentario_tarea_alumno" rows="3" placeholder="Profesor, adjunto la solución de los ejercicios solicitados..." class="input-lay8 textarea-lay8"></textarea>
            </div>

            <button type="submit" class="btn-cta-submit" id="btnSubmitEntrega">
                <i class="bx bx-check-shield"></i> Confirmar y Enviar al Profesor
            </button>
        </form>
    </div>
</div>

<!-- Modal: Inscripción de Materia Extemporánea (Formal, sin emojis) -->
<div id="modalInscripcionExtemporanea" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalExtemporanea()">
    <div class="modal-contenido-aula modal-actividad-ancho aulas-modal-lg">
        <div class="modal-aula-header">
            <h3><i class="bx bx-plus-circle"></i> Inscripción de Asignatura Extemporánea</h3>
            <span onclick="cerrarModalExtemporanea()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
        </div>
        <p class="aulas-modal-subdesc">
            Envía una solicitud formal de inscripción al docente encargado de la asignatura. Una vez autorizada por el profesor, la materia se incorporará automáticamente a tus aulas y horario.
        </p>

        <!-- Filtros y buscador del modal -->
        <div class="aulas-ext-filtros-row">
            <div class="aulas-ext-buscador-col">
                <input type="text" id="buscador-modal-extemporanea" class="input-lay8" placeholder="Buscar por asignatura o código (ej: Programación, CAL604)..." oninput="buscarMateriasExtemporaneas(this.value)">
            </div>
            <select id="filtro-carrera-extemporanea" class="input-lay8 aulas-ext-carrera-select" onchange="buscarMateriasExtemporaneas(document.getElementById('buscador-modal-extemporanea').value)">
                <option value="">Todas las carreras</option>
                <option value="ING-INF" <?= ($usuarioCarrera === 'ING-INF') ? 'selected' : '' ?>>Informática</option>
                <option value="ING-MAR" <?= ($usuarioCarrera === 'ING-MAR') ? 'selected' : '' ?>>Marítima</option>
                <option value="ING-AMB" <?= ($usuarioCarrera === 'ING-AMB') ? 'selected' : '' ?>>Ambiental</option>
                <option value="ADM" <?= ($usuarioCarrera === 'ADM') ? 'selected' : '' ?>>Administración</option>
                <option value="TUR" <?= ($usuarioCarrera === 'TUR') ? 'selected' : '' ?>>Turismo</option>
            </select>
        </div>

        <!-- Contenedor de resultados -->
        <div id="lista-resultados-extemporanea" class="aulas-ext-lista-box">
            <div class="aulas-ext-empty-hint">
                <i class="bx bx-search-alt"></i>
                Ingresa el nombre o código de la asignatura para buscar secciones disponibles.
            </div>
        </div>

        <div class="aulas-modal-footer-end">
            <button type="button" class="btn-subir-cta btn-secundario-aulas" onclick="cerrarModalExtemporanea()">
                Cerrar
            </button>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('js/aulas.js') ?>"></script>
<?= $this->endSection() ?>



<?= $this->endSection() ?>
