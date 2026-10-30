<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="biblioteca-dashboard-contenedor aulas-contenedor">

    <!-- Encabezado del Módulo de Aulas -->
    <div class="biblio-header-flex">
        <div>
            <h2 style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <i class="bx bx-door-open"></i> Aulas y Cronograma Académico
            </h2>
        </div>
        <div class="aulas-header-acciones">
            <button type="button" class="btn-subir-cta" onclick="cambiarTabAulas('cargar')">
                <i class="bx bx-calendar-plus"></i> + Armar / Cargar Horario
            </button>
            <button type="button" class="btn-subir-cta btn-secundario-aulas" onclick="abrirModalNuevaActividad()">
                <i class="bx bx-task"></i> + Nueva Tarea
            </button>
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

        <button type="button" class="btn-filtro" id="tabbtn-cargar" onclick="cambiarTabAulas('cargar')">
            <i class="bx bx-upload"></i> Cargar / Armar Horario
        </button>
    </nav>

    <!-- ========================================================================= -->
    <!-- PESTAÑA 1: MIS MATERIAS / AULAS VIRTUALES (Grid tipo Classroom)           -->
    <!-- ========================================================================= -->
    <div id="tab-seccion-materias" class="tab-contenido-aulas">
        <?php if (!empty($secciones) && count($secciones) > 0): ?>
            <div class="grid-aportes grid-aulas-classroom">
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
                            <button type="button" class="btn-agregar-tarea-mini" onclick="abrirModalNuevaActividad(<?= (int)$sec['id'] ?>, '<?= esc($sec['materia']) ?>')">
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

    <!-- ========================================================================= -->
    <!-- PESTAÑA 2: MI HORARIO SEMANAL (Grilla oficial UMC)                         -->
    <!-- ========================================================================= -->
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

    <!-- ========================================================================= -->
    <!-- PESTAÑA 3: CRONOGRAMA DE ACTIVIDADES (Classroom Tareas y Pendientes)       -->
    <!-- ========================================================================= -->
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
                            <?php if ((int)$act['creado_por'] === (int)session('usuario_id') || session('rol') === 'profesor' || session('rol') === 'admin'): ?>
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

    <!-- ========================================================================= -->
    <!-- PESTAÑA 4: CARGAR / ARMAR HORARIO                                         -->
    <!-- ========================================================================= -->
    <div id="tab-seccion-cargar" class="tab-contenido-aulas" style="display: none;">
        <div class="grid-cargar-horario">
            <!-- Opción A: Escaneo de Comprobante Oficial UMC (PDF) -->
            <div class="card-formulario-aulas">
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
            <div class="card-formulario-aulas">
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

                        <!-- Materia (Filtrada a las inscritas por el estudiante) -->
                        <div class="campo-grupo">
                            <label for="seccion_id"><i class="bx bx-book-open"></i> Materia Inscrita:</label>
                            <select name="seccion_id" id="seccion_id" required class="select-lay8">
                                <option value="" disabled selected>-- Selecciona una de tus materias --</option>
                                <?php foreach ($secciones as $sec): ?>
                                    <option value="<?= (int)$sec['id'] ?>">
                                        [<?= esc($sec['codigo_materia']) ?>] <?= esc($sec['materia']) ?> (Secc. <?= esc($sec['codigo_seccion']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

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

<script>
    const BASE_URL = '<?= base_url() ?>';

    // 1. Cambio de Pestañas y Persistencia en URL (para que no te saque de la página)
    function cambiarTabAulas(tabId) {
        document.querySelectorAll('.tab-contenido-aulas').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.filtros-categorias .btn-filtro').forEach(b => b.classList.remove('activo'));

        const seccionTarget = document.getElementById('tab-seccion-' + tabId);
        const btnTarget     = document.getElementById('tabbtn-' + tabId);

        if (seccionTarget) seccionTarget.style.display = 'block';
        if (btnTarget) btnTarget.classList.add('activo');

        // Persistir la pestaña en los parámetros de la URL sin recargar la página
        try {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        } catch (e) {}
    }

    // Inicializar pestaña activa según la URL al cargar
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tabSolicitada = urlParams.get('tab');
        if (tabSolicitada && document.getElementById('tab-seccion-' + tabSolicitada)) {
            cambiarTabAulas(tabSolicitada);
        }
    });

    // Filtro rápido de turnos en el selector de bloques
    function filtrarTurnosBloques(turno) {
        document.querySelectorAll('.selector-turnos-bloques .btn-turno-filtro').forEach(b => {
            b.classList.toggle('activo', b.getAttribute('data-filtro-turno') === turno);
        });

        document.querySelectorAll('#gridBloquesSelector .card-bloque-check').forEach(card => {
            if (turno === 'todos' || card.getAttribute('data-turno') === turno) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Resumen dinámico de bloques seleccionados
    function actualizarResumenBloques() {
        const checked = Array.from(document.querySelectorAll('.chk-bloque-hora:checked'));
        const barra = document.getElementById('resumenBloquesBarra');
        const lblTotal = document.getElementById('lblTotalBloques');
        const lblRango = document.getElementById('lblRangoHoras');

        // Actualizar clase visual en cards
        document.querySelectorAll('.card-bloque-check').forEach(c => {
            const chk = c.querySelector('input[type="checkbox"]');
            c.classList.toggle('seleccionado', chk && chk.checked);
        });

        if (checked.length === 0) {
            if (barra) barra.style.display = 'none';
            return;
        }

        if (barra) barra.style.display = 'flex';
        if (lblTotal) lblTotal.textContent = checked.length + (checked.length === 1 ? ' bloque' : ' bloques');

        // Ordenar horas seleccionadas
        const horas = checked.map(c => {
            const parts = c.value.split('|');
            return { inicio: parts[0], fin: parts[1] };
        }).sort((a, b) => a.inicio.localeCompare(b.inicio));

        const minInicio = horas[0].inicio.substring(0, 5);
        const maxFin    = horas[horas.length - 1].fin.substring(0, 5);

        // Estimar minutos académicos (45m por bloque)
        const totalMins = checked.length * 45;
        const horasTotales = Math.floor(totalMins / 60);
        const minsRestantes = totalMins % 60;
        let duracionTxt = '';
        if (horasTotales > 0) duracionTxt += horasTotales + 'h ';
        if (minsRestantes > 0) duracionTxt += minsRestantes + 'm';

        if (lblRango) {
            lblRango.textContent = `(${minInicio} a ${maxFin} • ${duracionTxt.trim()})`;
        }
    }

    function deseleccionarTodosBloques() {
        document.querySelectorAll('.chk-bloque-hora').forEach(c => c.checked = false);
        actualizarResumenBloques();
    }

    // Enviar bloques manualmente por AJAX para que el proceso sea continuo
    const formArmar = document.getElementById('formArmarHorarioManual');
    if (formArmar) {
        formArmar.addEventListener('submit', async function(e) {
            e.preventDefault();
            const checked = this.querySelectorAll('.chk-bloque-hora:checked');
            if (checked.length === 0) {
                mostrarToastAulas('Por favor selecciona al menos un bloque de hora oficial.', 'error');
                return;
            }

            const btnSubmit = document.getElementById('btnSubmitArmarHorario');
            const origHtml = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Guardando horario...';

            try {
                const formData = new FormData(this);
                const resp = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const res = await resp.json();
                if (res.success) {
                    mostrarToastAulas(res.mensaje || '¡Bloques agregados con éxito!', 'exito');
                    deseleccionarTodosBloques();
                    const badge = document.querySelector('#tabbtn-horario .badge-conteo');
                    if (badge && res.insertados) {
                        badge.textContent = parseInt(badge.textContent || '0') + res.insertados;
                    }
                } else {
                    mostrarToastAulas(res.error || 'Ocurrió un error al guardar.', 'error');
                }
            } catch (err) {
                mostrarToastAulas('Error de red al guardar los bloques.', 'error');
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = origHtml;
            }
        });
    }

    // Eliminar bloque de horario individual (AJAX)
    async function eliminarBloqueHorario(id, btnElem) {
        if (!confirm('¿Deseas eliminar este bloque de clase de tu horario?')) return;

        const card = btnElem ? btnElem.closest('.bloque-clase-card') : document.getElementById('bloque-card-' + id);
        if (card) card.style.opacity = '0.35';

        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) headers['<?= csrf_header() ?>'] = csrfInput.value;

        try {
            const resp = await fetch(BASE_URL + 'aulas/eliminar-bloque/' + id, {
                method: 'POST',
                headers: headers
            });
            const data = await resp.json();
            if (data.success) {
                if (card) {
                    card.style.transition = 'all 0.25s ease';
                    card.style.transform = 'scale(0.8)';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 250);
                }
                const badge = document.querySelector('#tabbtn-horario .badge-conteo');
                if (badge) {
                    const act = parseInt(badge.textContent || '0');
                    if (act > 0) badge.textContent = act - 1;
                }
                mostrarToastAulas('Bloque eliminado correctamente.', 'exito');
            } else {
                if (card) card.style.opacity = '1';
                mostrarToastAulas(data.error || 'No se pudo eliminar el bloque.', 'error');
            }
        } catch (e) {
            if (card) card.style.opacity = '1';
            mostrarToastAulas('Error de conexión al eliminar el bloque.', 'error');
        }
    }

    // Limpiar por completo el horario del estudiante (AJAX)
    async function confirmarLimpiarHorario() {
        if (!confirm('¿Estás seguro de que deseas limpiar tu horario?\n\nSe eliminarán los bloques asignados para que puedas armar tus materias y horas desde cero sin clases conflictivas.')) {
            return;
        }

        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) headers['<?= csrf_header() ?>'] = csrfInput.value;

        try {
            const resp = await fetch(BASE_URL + 'aulas/limpiar-horario', {
                method: 'POST',
                headers: headers
            });
            const data = await resp.json();
            if (data.success) {
                mostrarToastAulas(data.mensaje || '¡Horario limpiado exitosamente!', 'exito');
                document.querySelectorAll('.bloque-clase-card').forEach(el => el.remove());
                const badge = document.querySelector('#tabbtn-horario .badge-conteo');
                if (badge) badge.textContent = '0';
            } else {
                mostrarToastAulas(data.error || 'No se pudo limpiar el horario.', 'error');
            }
        } catch (e) {
            window.location.href = BASE_URL + 'aulas?tab=horario';
        }
    }

    // Notificaciones flotantes tipo Toast
    function mostrarToastAulas(mensaje, tipo = 'exito') {
        const toast = document.createElement('div');
        toast.className = `toast-notif-aulas ${tipo}`;
        toast.innerHTML = `<i class="bx ${tipo === 'exito' ? 'bx-check-circle' : 'bx-error-circle'}" style="font-size: 1.3rem;"></i> <span>${mensaje}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // 2. Filtro de Actividades (Todas / Pendientes / Completadas)
    function filtrarActividades(filtro) {
        document.querySelectorAll('.filtros-actividades-chips .chip-actividad').forEach(c => c.classList.remove('activo'));
        event.target.classList.add('activo');

        document.querySelectorAll('.item-actividad-card').forEach(item => {
            const estado = item.getAttribute('data-estado');
            if (filtro === 'todas') {
                item.style.display = 'flex';
            } else if (filtro === 'pendientes' && estado === 'pendiente') {
                item.style.display = 'flex';
            } else if (filtro === 'completadas' && estado === 'completada') {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // 3. Toggle de Estado de Actividad (AJAX)
    function toggleActividadCheckbox(actividadId, checkboxElem) {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest'
        };
        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) {
            headers['<?= csrf_header() ?>'] = csrfInput.value;
        }

        fetch(BASE_URL + 'aulas/toggle-actividad/' + actividadId, {
            method: 'POST',
            headers: headers
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const card = checkboxElem.closest('.item-actividad-card');
                if (checkboxElem.checked) {
                    card.classList.add('actividad-completada');
                    card.setAttribute('data-estado', 'completada');
                } else {
                    card.classList.remove('actividad-completada');
                    card.setAttribute('data-estado', 'pendiente');
                }
            } else {
                checkboxElem.checked = !checkboxElem.checked;
                alert('No se pudo actualizar el estado de la tarea.');
            }
        })
        .catch(err => {
            checkboxElem.checked = !checkboxElem.checked;
            console.error('Error al actualizar tarea:', err);
        });
    }

    // 4. Modales de Tarea
    function abrirModalNuevaActividad(seccionId = null, nombreMateria = null) {
        const modal = document.getElementById('modalNuevaActividad');
        if (seccionId) {
            const selectSec = document.getElementById('act_seccion_id');
            if (selectSec) selectSec.value = seccionId;
        }
        modal.style.display = 'flex';
    }

    function cerrarModalNuevaActividad() {
        document.getElementById('modalNuevaActividad').style.display = 'none';
    }

    // 5. Nombre de archivo dinámico
    function mostrarNombreArchivo(input) {
        const label = document.getElementById('texto-nombre-archivo');
        if (input.files && input.files[0]) {
            label.innerText = 'Archivo seleccionado: ' + input.files[0].name;
            label.style.fontWeight = 'bold';
            label.style.color = '#1e3a8a';
        }
    }
</script>



<?= $this->endSection() ?>
