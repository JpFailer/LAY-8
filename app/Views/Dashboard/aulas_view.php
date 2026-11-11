<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/aulas.css?v=' . time()) ?>">
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
                <button type="button" class="btn-subir-cta" onclick="cambiarTabAulas('cargar')">
                    <i class="bx bx-calendar-plus"></i>  Armar / Cargar Horario
                </button>
            <?php endif; ?>
            <?php if (in_array(session('rol'), ['profesor', 'administrador', 'admin'], true)): ?>
                <button type="button" class="btn-subir-cta btn-secundario-aulas" onclick="abrirModalNuevaActividad()">
                    <i class="bx bx-task"></i>  Nueva Tarea
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

        <?php if (!in_array(session('rol'), ['profesor'], true)): ?>
            <button type="button" class="btn-filtro" id="tabbtn-cargar" onclick="cambiarTabAulas('cargar')">
                <i class="bx bx-calendar-plus"></i> Armar / Cargar Horario
            </button>
        <?php endif; ?>
    </nav>

    <!-- PESTAÑA 1: MIS MATERIAS / AULAS VIRTUALES (Grid tipo Classroom)           -->
    <div id="tab-seccion-materias" class="tab-contenido-aulas">
        <?php if (!empty($secciones) && count($secciones) > 0): ?>
            <!-- Filtros Rápidos por Carrera (Filtro por Carrera del Estudiante) -->
            <div class="filtros-carrera-wrapper" style="margin-bottom: 14px;">
                <div class="filtros-carrera-label" style="font-size: 0.82rem; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                    <i class="bx bx-filter-alt"></i> Filtrar Materias por Carrera:
                </div>
                <div class="filtros-carreras-pills">
                    <?php foreach ($carrerasDefinidas as $cod => $info): ?>
                        <?php
                            $activo = ($carreraActual === $cod);
                            $conteoSec = $conteosSeccionesCarreras[$cod] ?? 0;
                        ?>
                        <a href="<?= base_url('aulas?carrera=' . urlencode($cod)) ?>" class="btn-filtro-carrera <?= $activo ? 'activo' : '' ?>">
                            <i class="bx <?= esc($info['icono']) ?>"></i> <?= esc($info['nombre']) ?>
                            <span class="badge-conteo-carrera"><?= (int)$conteoSec ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

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
                        <div class="tarjeta-meta meta-aula-footer">
                            <span class="total-actividades-badge">
                                <i class="bx bx-task"></i> <?= (int)$sec['total_actividades'] ?> actividad(es)
                            </span>
                            <button type="button" class="btn-agregar-tarea-mini" onclick="abrirModalNuevaActividad(<?= (int)$sec['id'] ?>, '<?= esc($sec['materia'], 'js') ?>')">
                                <i class="bx bx-plus"></i> Tarea
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="panel-vacio-aulas">
                <div class="icono-vacio"><i class="bx bx-calendar-x"></i></div>
                <h3>No tienes materias cargadas aún</h3>
                <p>Para activar tu Classroom y ver las aulas en el mapa, escanea tu <b>Registro de Inscripción UMC (PDF)</b> o arma tu horario seleccionando las materias inscritas.</p>
                <button type="button" class="btn-subir-cta" onclick="cambiarTabAulas('cargar')">
                    <i class="bx bx-cloud-upload"></i> Cargar Registro de Inscripción o Armar Horario
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- PESTAÑA 2: MI HORARIO SEMANAL (Grilla oficial UMC)                         -->
    <div id="tab-seccion-horario" class="tab-contenido-aulas" style="display: none;">
        <div class="horario-toolbar">
            <div class="toolbar-info">
                <h3 style="display: flex; align-items: center; gap: 8px; margin: 0;">
                    <i class="bx bx-calendar-event"></i> Horario Académico Personal
                </h3>
            </div>
            <div class="toolbar-botones">
                <button type="button" class="btn-imprimir-horario" onclick="window.print()">
                    <i class="bx bx-printer"></i> Imprimir Horario
                </button>
                <button type="button" class="btn-imprimir-horario" onclick="confirmarRestaurarHorario()" title="Restaurar horario oficial asignado por la universidad">
                    <i class="bx bx-refresh" style="color: #2563eb;"></i> Restaurar Horario Oficial
                </button>
                <button type="button" class="btn-imprimir-horario btn-limpiar-horario-action" onclick="confirmarLimpiarHorario()" title="Borrar todos los bloques de mi horario para empezar de nuevo">
                    <i class="bx bx-trash" style="color: #ef4444;"></i> Limpiar Mi Horario
                </button>
            </div>
        </div>

        <div class="contenedor-tabla-horario">
            <table class="tabla-horario-umc">
                <thead>
                    <tr>
                        <th style="width: 130px;">Bloque / Turno</th>
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
                <h3 style="display: flex; align-items: center; gap: 8px; margin: 0;">
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
                        $esCompletada = (bool)$act['completada'];
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
                            <div style="margin-top: 8px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                <?php if (!empty($act['entrega_id'])): ?>
                                    <span style="background: #dcfce7; color: #166534; font-size: 0.78rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="bx bx-check-double"></i> Entregada <?= !empty($act['fecha_entrega_alumno']) ? date('d/m H:i', strtotime($act['fecha_entrega_alumno'])) : '' ?>
                                    </span>
                                    <?php if (!empty($act['entrega_archivo'])): ?>
                                        <a href="<?= base_url('aulas/descargar-mi-entrega/' . $act['id']) ?>" style="font-size: 0.78rem; color: #2563eb; text-decoration: underline; display: inline-flex; align-items: center; gap: 3px;">
                                            <i class="bx bx-paperclip"></i> <?= esc($act['entrega_archivo']) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($act['calificacion'] !== null): ?>
                                        <span style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; font-size: 0.8rem; font-weight: 800; padding: 3px 9px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="bx bxs-award" style="color: #f59e0b;"></i> Calificación: <?= (float)$act['calificacion'] ?> pts
                                        </span>
                                        <?php if (!empty($act['feedback_profesor'])): ?>
                                            <span style="font-size: 0.78rem; color: #475569; font-style: italic;">
                                                Docente: "<?= esc($act['feedback_profesor']) ?>"
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="background: #fef3c7; color: #92400e; font-size: 0.78rem; font-weight: 600; padding: 3px 8px; border-radius: 6px;">
                                            Pendiente por calificar
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <button type="button" class="btn-subir-cta" style="padding: 4px 10px; font-size: 0.78rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;" onclick="abrirModalEntregarTarea(<?= (int)$act['id'] ?>, '<?= esc($act['titulo'], 'js') ?>', '<?= esc($act['materia_nombre'], 'js') ?>')">
                                    <i class="bx bx-upload"></i> <?= !empty($act['entrega_id']) ? 'Modificar Entrega' : 'Entregar Tarea' ?>
                                </button>
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
                                <form method="post" action="<?= base_url('aulas/eliminar-actividad/' . $act['id']) ?>" onsubmit="return confirm('¿Deseas eliminar esta actividad del cronograma?')">
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
                <p>Agrega recordatorios de tus tareas, exámenes, talleres y proyectos para mantener tu cronograma al día.</p>
                <button type="button" class="btn-subir-cta" onclick="abrirModalNuevaActividad()">
                    <i class="bx bx-plus-circle"></i> Crear Primera Tarea o Evaluación
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- PESTAÑA 4: CARGAR / ARMAR HORARIO                                         -->
    <div id="tab-seccion-cargar" class="tab-contenido-aulas" style="display: none;">
        
        <!-- Sub-navegación para alternar vista y simplificar la pestaña (Pestaña limpia y organizada) -->
        <div class="cargar-horario-subnav">
            <button type="button" class="btn-subnav-cargar activo" id="subnav-btn-ambos" onclick="cambiarVistaCarga('ambos')">
                <i class="bx bx-columns"></i> Ver Ambas Opciones
            </button>
            <button type="button" class="btn-subnav-cargar" id="subnav-btn-manual" onclick="cambiarVistaCarga('manual')">
                <i class="bx bx-calendar-edit"></i> Armar Manualmente
            </button>
            <button type="button" class="btn-subnav-cargar" id="subnav-btn-pdf" onclick="cambiarVistaCarga('pdf')">
                <i class="bx bxs-file-pdf"></i> Carga Inteligente (PDF)
            </button>
        </div>

        <div class="grid-cargar-horario" id="contenedor-grid-cargar">
            <!-- Opción A: Escaneo de Comprobante Oficial UMC (PDF) -->
            <div class="card-formulario-aulas" id="card-carga-pdf">
                <div class="card-form-header">
                    <div class="icono-header-card"><i class="bx bxs-file-pdf"></i></div>
                    <div>
                        <h3>Opción 1: Carga Inteligente de Documento UMC</h3>
                        <p>Escaneo automatizado de tu <b>Registro de Inscripción (UPE-002)</b> o <b>Horario Académico (CGD-001)</b>.</p>
                    </div>
                </div>

                <form action="<?= base_url('aulas/subir-documento') ?>" method="post" enctype="multipart/form-data" class="formulario-estilo-lay8">
                    <?= csrf_field() ?>
                    <div class="caja-subida-dropzone">
                        <i class="bx bx-cloud-upload icono-dropzone"></i>
                        <label for="documento_pdf" class="label-archivo-custom">
                            <span id="texto-nombre-archivo">Selecciona tu archivo PDF oficial</span>
                            <input type="file" id="documento_pdf" name="documento_pdf" accept="application/pdf" required onchange="mostrarNombreArchivo(this)">
                        </label>
                        <span class="ayuda-dropzone">Formatos válidos: Solo PDF original emitido por la UMC (Máx 5MB)</span>
                    </div>

                    <button type="submit" class="btn-cta-submit">
                        <i class="bx bx-scan"></i> Escanear y Vincular Materias
                    </button>
                </form>
            </div>

            <!-- Opción B: Armar Horario Manual Asistido por Plantilla -->
            <div class="card-formulario-aulas" id="card-carga-manual">
                <div class="card-form-header">
                    <div class="icono-header-card"><i class="bx bx-calendar-edit"></i></div>
                    <div>
                        <h3>Opción 2: Armar Horario Manual (Por Plantilla)</h3>
                        <p>Programa tus clases en la grilla oficial <b>siempre y cuando la materia esté en tu registro</b>.</p>
                    </div>
                </div>

                <?php if (!empty($secciones) && count($secciones) > 0): ?>
                    <form action="<?= base_url('aulas/guardar-bloque-manual') ?>" method="post" id="formArmarHorarioManual" class="formulario-estilo-lay8">
                        <?= csrf_field() ?>

                        <!-- Materia (Buscable y con soporte anti-acentos / anti-mayúsculas) -->
                        <div class="campo-grupo campo-materia-searchable">
                            <label for="seccion_id"><i class="bx bx-book-open"></i> Materia Inscrita:</label>
                            
                            <!-- Input oculto para preservar compatibilidad con AulasController::guardarBloqueManual -->
                            <input type="hidden" name="seccion_id" id="seccion_id" required value="">

                            <!-- Componente Combobox Buscable Antidayanas -->
                            <div class="searchable-combobox" id="comboMateriaInscrita">
                                <div class="combobox-trigger" id="comboMateriaTrigger" onclick="toggleComboMateria()">
                                    <div class="combobox-selected-content" id="comboMateriaSelected">
                                        <i class="bx bx-search combo-icon-search"></i>
                                        <span class="combobox-placeholder">Selecciona o busca tu materia (ej: Cálculo, LOG-103)...</span>
                                    </div>
                                    <div class="combobox-actions">
                                        <button type="button" class="btn-clear-combo" id="btnClearComboMateria" style="display: none;" onclick="limpiarSeleccionComboMateria(event)" title="Quitar selección">
                                            <i class="bx bx-x"></i>
                                        </button>
                                        <i class="bx bx-chevron-down combo-arrow"></i>
                                    </div>
                                </div>

                                <div class="combobox-dropdown" id="comboMateriaDropdown" style="display: none;">
                                    <div class="combobox-search-box">
                                        <i class="bx bx-search"></i>
                                        <input type="text" 
                                               id="inputBuscarMateriaInscrita" 
                                               class="input-combo-search" 
                                               placeholder="Escribe para filtrar (ej: calculo, quimica, log-103)..." 
                                               autocomplete="off" 
                                               oninput="filtrarComboMaterias(this.value)">
                                    </div>
                                    <div class="combobox-options-list" id="listaOpcionesComboMateria">
                                        <?php foreach ($secciones as $sec): ?>
                                            <?php 
                                                $dispCode = esc($sec['codigo_materia']);
                                                $dispNombre = esc($sec['materia']);
                                                $dispSecc = esc($sec['codigo_seccion']);
                                                $fullLabel = "[$dispCode] $dispNombre (Secc. $dispSecc)";
                                            ?>
                                            <div class="combo-option-item" 
                                                 data-id="<?= (int)$sec['id'] ?>" 
                                                 data-code="<?= $dispCode ?>" 
                                                 data-nombre="<?= $dispNombre ?>" 
                                                 data-seccion="<?= $dispSecc ?>" 
                                                 data-label="<?= esc($fullLabel) ?>"
                                                 onclick="seleccionarComboMateria(<?= (int)$sec['id'] ?>, '<?= esc($fullLabel, 'js') ?>', '<?= $dispCode ?>', '<?= $dispNombre ?>', '<?= $dispSecc ?>')">
                                                <span class="combo-opt-code"><?= $dispCode ?></span>
                                                <span class="combo-opt-name"><?= $dispNombre ?></span>
                                                <span class="combo-opt-sec">Secc. <?= $dispSecc ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="combobox-empty" id="comboMateriaVacio" style="display: none;">
                                        <i class="bx bx-info-circle"></i> No se encontraron materias inscritas con ese nombre o código.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Día y Aula en fila doble compacta y ordenada -->
                        <div class="campo-fila-doble">
                            <!-- Día de la Semana -->
                            <div class="campo-grupo">
                                <label for="dia_semana"><i class="bx bx-calendar"></i> Día de la Semana:</label>
                                <select name="dia_semana" id="dia_semana" required class="select-lay8">
                                    <option value="" disabled selected>-- Selecciona el día --</option>
                                    <?php foreach ($diasSemana as $dia): ?>
                                        <option value="<?= esc($dia) ?>"><?= esc($dia) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Aula Física del Campus -->
                            <div class="campo-grupo">
                                <label for="aula"><i class="bx bx-map-pin"></i> Aula o Laboratorio:</label>
                                <select name="aula" id="aula" required class="select-lay8">
                                    <option value="" disabled selected>-- Selecciona el aula --</option>
                                    <?php foreach ($todasAulas as $a): ?>
                                        <option value="<?= esc($a['alias_pdf']) ?>"><?= esc($a['alias_pdf']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Bloques Oficiales UMC (Multi-selección continua) -->
                        <div class="campo-grupo">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label style="margin: 0;"><i class="bx bx-time"></i> Bloques Horarios UMC (Selección Múltiple):</label>
                                <span class="subtexto-bloques-info">Puedes marcar 2 o más bloques continuos</span>
                            </div>

                            <!-- Filtros rápidos por Turno -->
                            <div class="selector-turnos-bloques">
                                <button type="button" class="btn-turno-filtro activo" data-filtro-turno="todos" onclick="filtrarTurnosBloques('todos')">Todos</button>
                                <button type="button" class="btn-turno-filtro" data-filtro-turno="Mañana" onclick="filtrarTurnosBloques('Mañana')">☀️ Mañana</button>
                                <button type="button" class="btn-turno-filtro" data-filtro-turno="Tarde" onclick="filtrarTurnosBloques('Tarde')">⛅ Tarde</button>
                                <button type="button" class="btn-turno-filtro" data-filtro-turno="Noche" onclick="filtrarTurnosBloques('Noche')">🌙 Noche</button>
                            </div>

                            <!-- Cuadrícula interactiva de bloques horarios -->
                            <div class="grid-bloques-selector" id="gridBloquesSelector">
                                <?php $bIdx = 0; ?>
                                <?php foreach ($bloquesOficiales as $turno => $bloques): ?>
                                    <?php foreach ($bloques as $b): ?>
                                        <?php $bIdx++; ?>
                                        <label class="card-bloque-check" data-turno="<?= esc($turno) ?>">
                                            <input type="checkbox" name="bloques_horas[]" value="<?= $b['inicio'] ?>|<?= $b['fin'] ?>" class="chk-bloque-hora" onchange="actualizarResumenBloques()">
                                            <span class="bloque-hora-badge">B<?= $bIdx ?></span>
                                            <span class="bloque-hora-tiempo"><?= esc($b['etiqueta']) ?></span>
                                            <span class="bloque-hora-turno"><?= esc($turno) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>

                            <!-- Barra de Resumen dinámico -->
                            <div class="barra-resumen-bloques" id="resumenBloquesBarra" style="display: none;">
                                <div class="info-bloques-seleccionados">
                                    <i class="bx bx-check-circle" style="color: #16a34a; font-size: 1.25rem;"></i>
                                    <div>
                                        <strong id="lblTotalBloques">0 bloques</strong> seleccionados
                                        <span class="rango-horas-calculado" id="lblRangoHoras"></span>
                                    </div>
                                </div>
                                <button type="button" class="btn-limpiar-seleccion-bloques" onclick="deseleccionarTodosBloques()">
                                    <i class="bx bx-x"></i> Desmarcar
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-cta-submit" id="btnSubmitArmarHorario">
                            <i class="bx bx-plus-circle"></i> Agregar Bloques al Horario
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alerta-registro-requerido">
                        <i class="bx bx-lock-alt" style="font-size: 2rem; color: #f59e0b; display: block; margin-bottom: 10px;"></i>
                        <h4>Materias pendientes de validación</h4>
                        <p>Para garantizar que tu horario sea legítimo, primero debes escanear tu <b>Registro de Inscripción oficial</b> con el botón de la izquierda. Luego podrás armar y calibrar tus horas en este panel.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Modal para Crear Nueva Actividad o Tarea -->
<div id="modalNuevaActividad" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalNuevaActividad()">
    <div class="modal-contenido-aula modal-actividad-ancho">
        <div class="modal-aula-header">
            <h3><i class="bx bx-task"></i> Nueva Actividad / Evaluación</h3>
            <span onclick="cerrarModalNuevaActividad()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
        </div>
        <form action="<?= base_url('aulas/crear-actividad') ?>" method="post" class="formulario-estilo-lay8" style="padding: 20px;">
            <?= csrf_field() ?>
            <div class="campo-grupo">
                <label for="act_seccion_id"><i class="bx bx-book-open"></i> Materia:</label>
                <select name="seccion_id" id="act_seccion_id" required class="select-lay8">
                    <option value="" disabled selected>-- Selecciona la materia --</option>
                    <?php foreach ($secciones as $sec): ?>
                        <option value="<?= (int)$sec['id'] ?>"><?= esc($sec['materia']) ?> (Secc. <?= esc($sec['codigo_seccion']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo-grupo">
                <label for="act_titulo"><i class="bx bx-edit"></i> Título de la Actividad:</label>
                <input type="text" name="titulo" id="act_titulo" required placeholder="Ej: Taller 1: Ecuaciones Diferenciales" class="input-lay8">
            </div>

            <div class="campo-fila-doble">
                <div class="campo-grupo">
                    <label for="act_tipo"><i class="bx bx-tag"></i> Tipo:</label>
                    <select name="tipo" id="act_tipo" required class="select-lay8">
                        <option value="tarea">Tarea</option>
                        <option value="parcial">Parcial / Examen</option>
                        <option value="taller">Taller</option>
                        <option value="proyecto">Proyecto</option>
                        <option value="lectura">Lectura</option>
                    </select>
                </div>
                <div class="campo-grupo">
                    <label for="act_ponderacion"><i class="bx bx-pie-chart"></i> Ponderación (%):</label>
                    <input type="number" step="0.5" min="0" max="100" name="ponderacion" id="act_ponderacion" placeholder="Ej: 20" class="input-lay8">
                </div>
            </div>

            <div class="campo-grupo">
                <label for="act_fecha_entrega"><i class="bx bx-calendar-event"></i> Fecha y Hora de Entrega:</label>
                <input type="datetime-local" name="fecha_entrega" id="act_fecha_entrega" required class="input-lay8">
            </div>

            <div class="campo-grupo">
                <label for="act_descripcion"><i class="bx bx-align-left"></i> Instrucciones o Detalles:</label>
                <textarea name="descripcion" id="act_descripcion" rows="3" placeholder="Instrucciones, capítulos del libro o temas que cubre la actividad..." class="input-lay8 textarea-lay8"></textarea>
            </div>

            <button type="submit" class="btn-cta-submit">
                <i class="bx bx-save"></i> Guardar en Cronograma
            </button>
        </form>
    </div>
</div>


<!-- Modal: Entregar Tarea / Subir Archivo de Evaluación (Classroom) -->
<div id="modalEntregarTarea" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalEntregarTarea()">
    <div class="modal-contenido-aula modal-actividad-ancho" style="max-width: 500px;">
        <div class="modal-aula-header">
            <h3><i class="bx bx-upload"></i> Entregar Asignación</h3>
            <span onclick="cerrarModalEntregarTarea()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
        </div>
        <form id="formEntregarTareaAlumno" method="post" enctype="multipart/form-data" class="formulario-estilo-lay8" style="padding: 20px;">
            <?= csrf_field() ?>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
                <strong id="entregar_modal_titulo" style="display: block; color: #1e40af; font-size: 0.96rem;"></strong>
                <span id="entregar_modal_materia" style="font-size: 0.8rem; color: #3b82f6;"></span>
            </div>

            <div class="campo-grupo">
                <label for="input_archivo_tarea"><i class="bx bx-file"></i> Archivo de la Tarea (PDF, Word, ZIP, JPG):</label>
                <input type="file" name="archivo_tarea" id="input_archivo_tarea" accept=".pdf,.doc,.docx,.zip,.rar,.png,.jpg,.jpeg" class="input-lay8" style="padding: 8px;">
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

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/aulas.js?v=' . time()) ?>"></script>
<?= $this->endSection() ?>



<?= $this->endSection() ?>
