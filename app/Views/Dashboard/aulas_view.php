<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="biblioteca-dashboard-contenedor aulas-contenedor">

    <!-- Encabezado del Módulo de Aulas -->
    <div class="biblio-header-flex">
        <div>
            <h2><i class="bx bx-door-open"></i> Aulas y Cronograma Académico</h2>
            <p>Espacios virtuales de aprendizaje, horario semanal UMC y cronograma de materias en un solo lugar.</p>
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
                <h3><i class="bx bx-calendar-event"></i> Horario Académico Personal</h3>
                <p>Malla organizada en los turnos oficiales de la UMC (Mañana, Tarde y Noche).</p>
            </div>
            <div class="toolbar-botones">
                <button type="button" class="btn-imprimir-horario" onclick="window.print()">
                    <i class="bx bx-printer"></i> Imprimir Horario
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
                                                <div class="bloque-clase-card">
                                                    <div class="bloque-codigo"><?= esc($item['codigo']) ?> (<?= esc($item['seccion']) ?>)</div>
                                                    <div class="bloque-materia"><?= esc($item['materia']) ?></div>
                                                    <div class="bloque-aula">
                                                        <a href="<?= base_url('mapa?aula=' . urlencode($item['aula'])) ?>" title="Ver aula en el plano del campus">
                                                            <i class="bx bx-map-pin"></i> <?= esc($item['aula']) ?>
                                                        </a>
                                                    </div>
                                                    <?php if (!empty($item['es_personal'])): ?>
                                                        <form method="post" action="<?= base_url('aulas/eliminar-bloque/' . $item['id']) ?>" onsubmit="return confirm('¿Eliminar este bloque personal de horario?')">
                                                            <button type="submit" class="btn-borrar-bloque" title="Eliminar bloque manual">&times;</button>
                                                        </form>
                                                    <?php endif; ?>
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
                <h3><i class="bx bx-list-check"></i> Cronograma de Actividades y Tareas</h3>
                <p>Monitorea fechas límite, parciales y entregas pendientes de tus materias.</p>
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
                    <form action="<?= base_url('aulas/guardar-bloque-manual') ?>" method="post" class="formulario-estilo-lay8">
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

                        <!-- Bloques Oficiales UMC (Mañana, Tarde, Noche) -->
                        <div class="campo-grupo">
                            <label for="bloque_horas"><i class="bx bx-time"></i> Bloque Horario Oficial UMC:</label>
                            <select name="bloque_horas" id="bloque_horas" required class="select-lay8">
                                <option value="" disabled selected>-- Selecciona el intervalo de horas --</option>
                                <?php foreach ($bloquesOficiales as $turno => $bloques): ?>
                                    <optgroup label="Turno <?= esc($turno) ?>">
                                        <?php foreach ($bloques as $b): ?>
                                            <option value="<?= $b['inicio'] ?>|<?= $b['fin'] ?>">
                                                <?= esc($b['etiqueta']) ?> (<?= esc($turno) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
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

                        <button type="submit" class="btn-cta-submit">
                            <i class="bx bx-plus-circle"></i> Agregar Bloque al Horario
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

    // 1. Cambio de Pestañas
    function cambiarTabAulas(tabId) {
        document.querySelectorAll('.tab-contenido-aulas').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.filtros-categorias .btn-filtro').forEach(b => b.classList.remove('activo'));

        const seccionTarget = document.getElementById('tab-seccion-' + tabId);
        const btnTarget     = document.getElementById('tabbtn-' + tabId);

        if (seccionTarget) seccionTarget.style.display = 'block';
        if (btnTarget) btnTarget.classList.add('activo');
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
        fetch(BASE_URL + 'aulas/toggle-actividad/' + actividadId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
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
