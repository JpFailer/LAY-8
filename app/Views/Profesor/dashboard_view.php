<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/docente.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="biblioteca-dashboard-contenedor docente-dashboard-contenedor">

        <!-- Encabezado del Módulo Docente -->
    <?php if (in_array(session()->get('rol'), ['administrador', 'admin'], true)): ?>
        <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 14px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #dbeafe; color: #1e40af; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                    <i class="bx bx-shield-quarter"></i>
                </div>
                <div>
                    <strong style="color: #1e3a8a; font-size: 0.95rem; display: block;">Modo Supervisión de Administrador</strong>
                    <span style="font-size: 0.84rem; color: #475569;">Como administrador visualizas todas las secciones del campus. Los profesores solo ven las asignaturas que tú les asignes.</span>
                </div>
            </div>
            <a href="<?= base_url('admin?tab=docentes') ?>" class="btn-subir-cta" style="padding: 8px 18px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <i class="bx bx-slider-alt"></i> Asignar Secciones a Profesores
            </a>
        </div>
    <?php endif; ?>
    <div class="biblio-header-flex">
        <div>
            <h2 style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <i class="bx bx-chalkboard"></i> Panel Docente y Aulas Virtuales
            </h2>
            <p style="margin: 4px 0 0 0; font-size: 0.88rem; color: #64748b;">
                Profesor: <strong><?= esc($profesorNombre) ?></strong> &bull; Gestión de secciones, avisos en vivo y evaluaciones tipo Classroom.
            </p>
        </div>
        <div class="aulas-header-acciones">
            <button type="button" class="btn-subir-cta" onclick="abrirModalCrearTareaDocente()">
                <i class="bx bx-plus-circle"></i> Nueva Tarea / Evaluación
            </button>
        </div>
    </div>

    <!-- Mensajes Flash -->
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

    <!-- metricas arriba -->
    <div class="kpi-grid-admin" style="margin-bottom: 24px;">
        <div class="kpi-card">
            <div class="kpi-icon icon-blue"><i class="bx bx-door-open"></i></div>
            <div class="kpi-info">
                <span class="kpi-valor"><?= (int) $totalSecciones ?></span>
                <span class="kpi-label">Mis Secciones</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-green"><i class="bx bx-group"></i></div>
            <div class="kpi-info">
                <span class="kpi-valor"><?= (int) $totalAlumnos ?></span>
                <span class="kpi-label">Alumnos Matriculados</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-purple"><i class="bx bx-task"></i></div>
            <div class="kpi-info">
                <span class="kpi-valor"><?= (int) $totalActividades ?></span>
                <span class="kpi-label">Actividades Creadas</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-orange"><i class="bx bx-file-blank"></i></div>
            <div class="kpi-info">
                <span class="kpi-valor"><?= (int) $totalEntregasSinCalificar ?></span>
                <span class="kpi-label">Entregas por Calificar</span>
            </div>
        </div>
    </div>

    <!-- Pestañas de Navegación del Panel Docente y Buscador en Vivo -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        <nav class="filtros-categorias" style="margin-bottom: 0;">
            <button type="button" class="btn-filtro activo" id="tabbtn-doc-secciones" onclick="cambiarTabDocente('secciones')">
                <i class="bx bx-grid-alt"></i> Mis Secciones y Estado en Vivo
                <span class="badge-conteo" id="badge-conteo-secciones"><?= (int) $totalSecciones ?></span>
            </button>

            <button type="button" class="btn-filtro" id="tabbtn-doc-evaluaciones" onclick="cambiarTabDocente('evaluaciones')">
                <i class="bx bx-list-check"></i> Evaluaciones y Tareas (Classroom)
                <span class="badge-conteo"><?= (int) $totalActividades ?></span>
            </button>
        </nav>

        <div class="docente-search-container">
            <i class="bx bx-search docente-search-icon"></i>
            <input type="text" 
                   id="filtro-secciones-docente" 
                   class="input-lay8 docente-search-input" 
                   placeholder="Buscar materia, sección o aula..." 
                   autocomplete="off"
                   oninput="filtrarSeccionesDocente(this.value)">
            <button type="button" 
                    id="btn-limpiar-busqueda-docente" 
                    class="btn-clear-search-docente" 
                    style="display: none;" 
                    onclick="limpiarBuscadorDocente()" 
                    title="Limpiar búsqueda">
                <i class="bx bx-x"></i>
            </button>
        </div>
    </div>

    <!-- PESTAÑA 1: SECCIONES Y TRANSMISIÓN DE ESTADO EN VIVO                      -->
    <?php
        $esModoServidor = !empty($esModoServidor);
        $pag = $paginacion ?? [
            'totalFiltradas' => count($secciones),
            'paginaActual'   => 1,
            'totalPaginas'   => max(1, (int) ceil(count($secciones) / 12)),
            'porPagina'      => 12,
        ];
        $inicioPag = $pag['totalFiltradas'] > 0 ? (($pag['paginaActual'] - 1) * $pag['porPagina']) + 1 : 0;
        $finPag = min($pag['paginaActual'] * $pag['porPagina'], $pag['totalFiltradas']);
    ?>
    <div id="tab-doc-secciones" class="tab-contenido-docente" data-modo-servidor="<?= $esModoServidor ? '1' : '0' ?>" data-total-paginas="<?= (int) $pag['totalPaginas'] ?>">
        <!-- Estado vacío cuando la búsqueda no arroja resultados -->
        <div id="sin-resultados-docente" class="panel-vacio-docente" style="display: none;">
            <div class="icono-vacio"><i class="bx bx-search-alt docente-sin-res-ico"></i></div>
            <h3 class="docente-sin-res-tit">No se encontraron secciones</h3>
            <p id="texto-sin-resultados-docente" class="docente-sin-res-txt">No hay secciones que coincidan con la búsqueda.</p>
            <button type="button" class="btn-subir-cta" onclick="limpiarBuscadorDocente()">
                <i class="bx bx-refresh"></i> Ver todas las secciones
            </button>
        </div>

        <?php if (!empty($secciones)): ?>
            <?php if (!empty($carrerasPresentesDoc) && count($carrerasPresentesDoc) > 1): ?>
                <div class="docente-filtros-carrera-bar" id="docente-filtros-carrera">
                    <span class="docente-filtros-label"><i class="bx bx-filter-alt"></i> Carrera:</span>
                    <button type="button" class="btn-carrera-docente activo" data-carrera="" onclick="filtrarCarreraDocente('', this)">
                        Todas (<?= (int) $totalSecciones ?>)
                    </button>
                    <?php foreach ($carrerasPresentesDoc as $codCar => $metaCar): ?>
                        <button type="button" class="btn-carrera-docente" data-carrera="<?= esc($codCar) ?>" onclick="filtrarCarreraDocente('<?= esc($codCar, 'js') ?>', this)">
                            <?= esc($metaCar['etiqueta']) ?> (<?= (int) $metaCar['total'] ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div id="docente-paginacion-top" class="docente-paginacion-bar" style="<?= $pag['totalFiltradas'] <= 1 ? 'display: none;' : '' ?>">
                <div class="docente-pag-info" id="docente-pag-info-top">
                    Mostrando <?= $inicioPag ?> - <?= $finPag ?> de <?= (int) $pag['totalFiltradas'] ?> secciones
                </div>
                <div class="docente-paginacion-controles">
                    <label for="docente-tamano-pagina" class="docente-label-tamano">Por página:</label>
                    <select id="docente-tamano-pagina" class="docente-select-tamano" onchange="cambiarTamanoPaginaDocente(this.value)">
                        <option value="12" <?= $pag['porPagina'] === 12 ? 'selected' : '' ?>>12</option>
                        <option value="24" <?= $pag['porPagina'] === 24 ? 'selected' : '' ?>>24</option>
                        <option value="48" <?= $pag['porPagina'] === 48 ? 'selected' : '' ?>>48</option>
                    </select>
                    <button type="button" class="btn-pag-docente" id="btn-pag-prev-top" onclick="cambiarPaginaDocente(-1)" <?= $pag['paginaActual'] <= 1 ? 'disabled' : '' ?>>
                        <i class="bx bx-chevron-left"></i> Anterior
                    </button>
                    <span class="docente-pag-indicador" id="docente-pag-indicador-top">Pág. <?= (int) $pag['paginaActual'] ?> / <?= (int) $pag['totalPaginas'] ?></span>
                    <button type="button" class="btn-pag-docente" id="btn-pag-next-top" onclick="cambiarPaginaDocente(1)" <?= $pag['paginaActual'] >= $pag['totalPaginas'] ? 'disabled' : '' ?>>
                        Siguiente <i class="bx bx-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="grid-aportes grid-aulas-classroom">
                <?= view('Profesor/_tarjetas_secciones', ['secciones' => $secciones, 'esModoServidor' => $esModoServidor]) ?>
            </div>

            <div id="docente-paginacion-bottom" class="docente-paginacion-bar bottom" style="display: <?= $pag['totalFiltradas'] > $pag['porPagina'] ? 'flex' : 'none' ?>;">
                <div class="docente-pag-info" id="docente-pag-info-bottom">
                    Mostrando <?= $inicioPag ?> - <?= $finPag ?> de <?= (int) $pag['totalFiltradas'] ?> secciones
                </div>
                <div class="docente-paginacion-controles">
                    <button type="button" class="btn-pag-docente" id="btn-pag-prev-bottom" onclick="cambiarPaginaDocente(-1)" <?= $pag['paginaActual'] <= 1 ? 'disabled' : '' ?>>
                        <i class="bx bx-chevron-left"></i> Anterior
                    </button>
                    <span class="docente-pag-indicador" id="docente-pag-indicador-bottom">Pág. <?= (int) $pag['paginaActual'] ?> / <?= (int) $pag['totalPaginas'] ?></span>
                    <button type="button" class="btn-pag-docente" id="btn-pag-next-bottom" onclick="cambiarPaginaDocente(1)" <?= $pag['paginaActual'] >= $pag['totalPaginas'] ? 'disabled' : '' ?>>
                        Siguiente <i class="bx bx-chevron-right"></i>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="estado-vacio">
                <i class="bx bx-chalkboard icono-vacio"></i>
                <h3>No tienes secciones asignadas</h3>
                <p>Tu cuenta docente no tiene asignaturas asignadas en el período actual. Contacta con la coordinación académica o administración de LAY-8 para asociar tus secciones oficiales.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- PESTAÑA 2: EVALUACIONES Y REVISIÓN DE ENTREGAS (Classroom)                -->
    <div id="tab-doc-evaluaciones" class="tab-contenido-docente" style="display: none;">
        <?php if (!empty($actividades)): ?>
            <div class="contenedor-tabla-horario docente-tabla-mt">
                <table class="tabla-horario-umc">
                    <thead>
                        <tr>
                            <th>Materia & Sección</th>
                            <th>Título de la Actividad</th>
                            <th>Tipo</th>
                            <th>Fecha Límite</th>
                            <th>Ponderación</th>
                            <th>Entregas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividades as $act): ?>
                            <tr>
                                <td>
                                    <strong><?= esc($act['materia']) ?></strong>
                                    <div class="docente-tabla-sub">Secc. <?= esc($act['codigo_seccion']) ?></div>
                                </td>
                                <td>
                                    <strong><?= esc($act['titulo']) ?></strong>
                                    <?php if (!empty($act['descripcion'])): ?>
                                        <div class="docente-tabla-desc">
                                            <?= esc($act['descripcion']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-codigo-materia docente-badge-tipo-cap">
                                        <?= esc($act['tipo']) ?>
                                    </span>
                                </td>
                                <td class="docente-td-fecha">
                                    <i class="bx bx-calendar"></i> <?= date('d/m/Y H:i', strtotime($act['fecha_entrega'])) ?>
                                </td>
                                <td>
                                    <strong><?= (float)$act['ponderacion'] ?>%</strong>
                                </td>
                                <td>
                                    <span class="badge-conteo docente-badge-entregas <?= $act['sin_calificar'] > 0 ? 'docente-badge-entregas-pend' : 'docente-badge-entregas-ok' ?>">
                                        <i class="bx bx-check-circle"></i> <?= (int)$act['total_entregas'] ?> entregas
                                        <?php if ($act['sin_calificar'] > 0): ?>
                                            (<?= (int)$act['sin_calificar'] ?> pendientes)
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn-subir-cta docente-btn-calificar" onclick="abrirModalRevisarEntregas(<?= (int)$act['id'] ?>, '<?= esc($act['titulo'], 'js') ?>')">
                                        <i class="bx bx-edit"></i> Calificar Alumnos
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="estado-vacio">
                <i class="bx bx-task icono-vacio"></i>
                <h3>No has publicado tareas o evaluaciones</h3>
                <p>Publica asignaciones para tus alumnos con fecha de entrega, ponderación y comentarios para que aparezcan en su cronograma académico.</p>
                <button type="button" class="btn-subir-cta docente-btn-empty-cta" onclick="abrirModalCrearTareaDocente()">
                    <i class="bx bx-plus-circle"></i> Crear Primera Evaluación
                </button>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal: Cambiar Estado en Vivo de la Clase -->
<div id="modalEstadoSeccion" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalEstadoSeccion()">
    <div class="modal-contenido-aula modal-actividad-ancho docente-modal-ancho-sm">
        <div class="modal-aula-header">
            <h3><i class="bx bx-broadcast"></i> Actualizar Estado en Vivo</h3>
            <span onclick="cerrarModalEstadoSeccion()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
        </div>
        <form id="formEstadoSeccion" onsubmit="enviarEstadoClaseDocente(event)" class="formulario-estilo-lay8 docente-modal-form-pad">
            <?= csrf_field() ?>
            <input type="hidden" name="seccion_id" id="estado_seccion_id">

            <div class="docente-modal-banner card-recup-box-inputs">
                <strong id="estado_modal_materia" class="docente-modal-banner-tit"></strong>
                <span class="docente-modal-banner-sub">Al guardar, se enviará una notificación instantánea a todos los alumnos inscritos.</span>
            </div>

            <div class="campo-grupo">
                <label><i class="bx bx-devices"></i> Modalidad de Hoy:</label>
                <div class="docente-modalidad-row">
                    <label class="docente-modalidad-opt">
                        <input type="radio" name="modalidad" value="presencial" checked onchange="toggleEnlaceVirtual(false)">
                        <span><i class="bx bx-walk"></i> Presencial (En el Aula)</span>
                    </label>
                    <label class="docente-modalidad-opt">
                        <input type="radio" name="modalidad" value="online" onchange="toggleEnlaceVirtual(true)">
                        <span><i class="bx bx-video"></i> Online (Virtual)</span>
                    </label>
                </div>
            </div>

            <div class="campo-grupo" id="campo_enlace_virtual" style="display: none;">
                <label for="enlace_virtual"><i class="bx bx-link"></i> Enlace de Videollamada (Meet, Zoom, Teams):</label>
                <input type="url" name="enlace_virtual" id="enlace_virtual" placeholder="https://meet.google.com/..." class="input-lay8">
            </div>

            <div class="campo-grupo">
                <label for="estado_rapido"><i class="bx bx-comment-detail"></i> Mensaje de Estado Rápido:</label>
                <input type="text" name="estado_rapido" id="estado_rapido" required placeholder="Ej: En camino al aula, llego en 10 min..." class="input-lay8">
                <div class="docente-chips-rapidos">
                    <button type="button" class="btn-turno-filtro" onclick="setEstadoPredefinido('En el aula de clase')">En el aula</button>
                    <button type="button" class="btn-turno-filtro" onclick="setEstadoPredefinido('En camino / Tráfico')">En camino</button>
                    <button type="button" class="btn-turno-filtro" onclick="setEstadoPredefinido('Clase virtual activa')">Clase virtual</button>
                    <button type="button" class="btn-turno-filtro" onclick="setEstadoPredefinido('Clase suspendida por fuerza mayor')">Suspendida</button>
                </div>
            </div>

            <div class="campo-grupo">
                <label for="mensaje_adicional"><i class="bx bx-align-left"></i> Instrucciones / Aviso Adicional (Opcional):</label>
                <textarea name="mensaje_adicional" id="mensaje_adicional" rows="2" placeholder="Traer calculadora y guía práctica..." class="input-lay8 textarea-lay8"></textarea>
            </div>

            <button type="submit" class="btn-cta-submit" id="btnGuardarEstado">
                <i class="bx bx-broadcast"></i> Transmitir Estado a los Alumnos
            </button>
        </form>
    </div>
</div>

<!-- Modal: Crear Tarea / Evaluación Oficial de la Sección -->
<div id="modalTareaDocente" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalTareaDocente()">
    <div class="modal-contenido-aula modal-actividad-ancho docente-modal-ancho-md">
        <div class="modal-aula-header">
            <h3><i class="bx bx-task"></i> Publicar Nueva Evaluación Oficial</h3>
            <span onclick="cerrarModalTareaDocente()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
        </div>
        <form id="formTareaDocente" onsubmit="enviarTareaDocente(event)" class="formulario-estilo-lay8 docente-modal-form-pad">
            <?= csrf_field() ?>
            <div class="campo-grupo">
                <label for="tarea_seccion_id"><i class="bx bx-door-open"></i> Asignatura & Sección:</label>
                <select name="seccion_id" id="tarea_seccion_id" required class="select-lay8">
                    <option value="" disabled selected>-- Selecciona la sección --</option>
                    <?php foreach (($seccionesSelect ?? $secciones) as $sec): ?>
                        <option value="<?= (int)$sec['id'] ?>"><?= esc($sec['materia']) ?> (Secc. <?= esc($sec['codigo_seccion']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo-grupo">
                <label for="tarea_titulo"><i class="bx bx-edit"></i> Título de la Asignación:</label>
                <input type="text" name="titulo" id="tarea_titulo" required placeholder="Ej: Parcial 1: Métodos Numéricos" class="input-lay8">
            </div>

            <div class="campo-fila-doble">
                <div class="campo-grupo">
                    <label for="tarea_tipo"><i class="bx bx-tag"></i> Tipo:</label>
                    <select name="tipo" id="tarea_tipo" required class="select-lay8">
                        <option value="tarea">Tarea</option>
                        <option value="parcial">Parcial / Examen</option>
                        <option value="taller">Taller Práctico</option>
                        <option value="proyecto">Proyecto de Semestre</option>
                        <option value="lectura">Lectura / Investigación</option>
                    </select>
                </div>
                <div class="campo-grupo">
                    <label for="tarea_ponderacion"><i class="bx bx-pie-chart"></i> Ponderación (%):</label>
                    <input type="number" step="0.5" min="0" max="100" name="ponderacion" id="tarea_ponderacion" placeholder="Ej: 25" class="input-lay8">
                </div>
            </div>

            <div class="campo-grupo">
                <label for="tarea_fecha_entrega"><i class="bx bx-calendar-event"></i> Fecha y Hora Límite de Entrega:</label>
                <input type="datetime-local" name="fecha_entrega" id="tarea_fecha_entrega" required class="input-lay8">
            </div>

            <div class="campo-grupo">
                <label for="tarea_descripcion"><i class="bx bx-align-left"></i> Instrucciones / Pautas de Entrega:</label>
                <textarea name="descripcion" id="tarea_descripcion" rows="3" placeholder="Pautas, formato requerido (PDF/Word), rúbrica o criterios de evaluación..." class="input-lay8 textarea-lay8"></textarea>
            </div>

            <button type="submit" class="btn-cta-submit" id="btnGuardarTareaDocente">
                <i class="bx bx-save"></i> Publicar en el Cronograma de la Sección
            </button>
        </form>
    </div>
</div>

<!-- Modal: Revisar Entregas de Alumnos y Calificar -->
<div id="modalRevisarEntregas" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalRevisarEntregas()">
    <div class="modal-contenido-aula docente-modal-entregas-caja">
        <div class="modal-aula-header docente-modal-entregas-head">
            <h3><i class="bx bx-clipboard"></i> Entregas de Alumnos: <span id="titulo_evaluacion_modal"></span></h3>
            <span onclick="cerrarModalRevisarEntregas()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
        </div>

        <div class="docente-modal-entregas-body">
            <div id="cargando_entregas" class="docente-entregas-status">
                <i class="bx bx-loader-alt bx-spin"></i>
                <p>Cargando entregas de los estudiantes...</p>
            </div>

            <div id="contenedor_lista_entregas" style="display: none;">
                <div id="lista_entregas_items" class="docente-entregas-list"></div>
            </div>

            <div id="sin_entregas_msg" class="docente-entregas-status" style="display: none;">
                <i class="bx bx-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                <p>Aún ningún alumno ha subido entrega para esta evaluación.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('js/docente.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
