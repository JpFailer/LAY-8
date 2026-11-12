<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="mapa-dashboard-contenedor">
    <div class="mapa-header-box">
        <div>
            <h2><i class="bx bx-map-pin"></i> Mapa Interactivo del Campus UMC</h2>
        </div>
    </div>

    <!-- Barra de Filtros Rápidos por Carrera y Disponibilidad -->
    <div class="mapa-filtros-carrera" id="mapa-filtros-carrera">
        <button type="button" class="btn-filtro-carrera activo" data-filtro="todos">
            <i class="bx bx-layer"></i> Todas las Aulas
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="libres">
            <i class="bx bx-check-circle" style="color: #10b981;"></i> Solo Libres
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ocupadas">
            <i class="bx bx-radio-circle-marked" style="color: #ef4444;"></i> Solo Ocupadas
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ING-INF">
            <i class="bx bx-code-alt" style="color: #2563eb;"></i> Informática
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ING-MAR">
            <i class="bx bx-anchor" style="color: #0284c7;"></i> Marítima
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ING-AMB">
            <i class="bx bx-leaf" style="color: #10b981;"></i> Ambiental
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="ADM">
            <i class="bx bx-briefcase-alt-2" style="color: #f59e0b;"></i> Administración
        </button>
        <button type="button" class="btn-filtro-carrera" data-filtro="TUR">
            <i class="bx bx-compass" style="color: #ec4899;"></i> Turismo
        </button>
    </div>

    <!-- Leyenda Moderna de Estados y Carreras -->
    <div class="leyenda-mapa-moderna">
        <div class="leyenda-fila">
            <span class="leyenda-titulo-seccion"><i class="bx bx-info-circle"></i> Estados del Aula:</span>
            <div class="item-leyenda-badge" title="Aula libre sin clases en este momento">
                <span class="mini-badge mini-verde"><i class="bx bx-check"></i></span>
                <span>Aula Libre (Verde)</span>
            </div>
            <div class="item-leyenda-badge" title="Hay una clase académica en curso">
                <span class="mini-badge mini-roja"><i class="bx bx-radio-circle-marked"></i></span>
                <span>En clase / Ocupada (Rojo)</span>
            </div>
            <div class="item-leyenda-badge" title="Una clase comenzará en los próximos 15 minutos">
                <span class="mini-badge mini-amarilla"><i class="bx bx-time-five"></i></span>
                <span>Próxima clase (15 min)</span>
            </div>
            <div class="item-leyenda-badge" title="Aula temporalmente inhabilitada">
                <span class="mini-badge mini-azul"><i class="bx bx-wrench"></i></span>
                <span>Fuera de servicio</span>
            </div>
        </div>
        
        <div class="leyenda-fila" style="border-top: 1px dashed #e2e8f0; padding-top: 8px; margin-top: 4px;">
            <span class="leyenda-titulo-seccion"><i class="bx bx-bookmark-alt"></i> Logos en Aulas Ocupadas:</span>
            <div class="item-leyenda-badge" title="Ingeniería en Informática">
                <span class="mini-badge mini-roja"><i class="bx bx-code-alt"></i></span>
                <span>Informática</span>
            </div>
            <div class="item-leyenda-badge" title="Ingeniería Marítima">
                <span class="mini-badge mini-roja"><i class="bx bx-anchor"></i></span>
                <span>Marítima</span>
            </div>
            <div class="item-leyenda-badge" title="Ingeniería Ambiental">
                <span class="mini-badge mini-roja"><i class="bx bx-leaf"></i></span>
                <span>Ambiental</span>
            </div>
            <div class="item-leyenda-badge" title="Licenciatura en Administración">
                <span class="mini-badge mini-roja"><i class="bx bx-briefcase-alt-2"></i></span>
                <span>Administración</span>
            </div>
            <div class="item-leyenda-badge" title="Licenciatura en Turismo">
                <span class="mini-badge mini-roja"><i class="bx bx-compass"></i></span>
                <span>Turismo</span>
            </div>
        </div>
    </div>

    <!-- Contenedor del Mapa Interactivo con Zoom y Pan -->
    <div class="contenedor-mapa" id="contenedor-mapa">
        <!-- Viewport interactivo con fondo institucional oscuro -->
        <div class="mapa-viewport" id="mapa-viewport">
            <!-- Lienzo transformable por Zoom / Pan -->
            <div class="mapa-canvas modo-vista-general" id="mapa-canvas">
                <img src="<?= base_url('img/mapa_umc.jpg') ?>" 
                     alt="Mapa Universidad Marítima del Caribe" 
                     class="imagen-mapa" 
                     draggable="false">

                <!-- Pines de Aulas con Logos de Carrera y Estado -->
                <?php if (!empty($aulas)): ?>
                    <?php foreach ($aulas as $aula): ?>
                        <?php 
                            $idSeguro = preg_replace('/[^\w\-]/', '_', $aula['alias_pdf']);
                            $colorPin = $aula['color'] ?? ($aula['esta_ocupado'] ? 'roja' : 'verde');
                            $iconoPin = $aula['icono'] ?? ($colorPin === 'verde' ? 'bx-check' : 'bx-code-alt');
                            $carreraCod = $aula['carrera'] ?? '';
                            $tituloPin = 'Aula ' . $aula['alias_pdf'] . ' (' . ($aula['texto_estado'] ?? 'Disponible') . ')';

                            $claseEstado = 'pin-estado-libre';
                            if ($colorPin === 'roja') {
                                $claseEstado = 'pin-estado-ocupada';
                            } elseif ($colorPin === 'amarilla') {
                                $claseEstado = 'pin-estado-proxima';
                            } elseif ($colorPin === 'azul') {
                                $claseEstado = 'pin-estado-mantenimiento';
                            }
                        ?>
                        <div class="pin-contenedor <?= $claseEstado ?>" 
                             id="pin_aula_<?= $idSeguro ?>"
                             style="left: <?= $aula['coord_x'] ?>%; top: <?= $aula['coord_y'] ?>%;"
                             data-aula="<?= esc($aula['alias_pdf']) ?>"
                             data-estado="<?= esc($colorPin) ?>"
                             data-carrera="<?= esc($carreraCod) ?>"
                             title="<?= esc($tituloPin) ?>">
                             
                             <div class="pin-badge pin-<?= $colorPin ?>" id="badge_aula_<?= $idSeguro ?>">
                                 <i id="icono_aula_<?= $idSeguro ?>" class="bx <?= esc($iconoPin) ?>"></i>
                             </div>

                             <!-- Tooltip flotante al pasar el cursor -->
                             <div class="pin-tooltip" id="tooltip_aula_<?= $idSeguro ?>">
                                 <span class="pin-tooltip-title">
                                     <i class="bx bx-map-pin"></i> <?= esc($aula['alias_pdf']) ?>
                                 </span>
                                 <span class="pin-tooltip-badge tag-<?= $colorPin ?>" id="tooltip_tag_<?= $idSeguro ?>">
                                     <i class="bx <?= esc($iconoPin) ?>"></i> <?= esc($aula['texto_estado'] ?? 'Disponible') ?>
                                 </span>
                                 <?php if (!empty($aula['materia'])): ?>
                                     <span class="pin-tooltip-detail" id="tooltip_mat_<?= $idSeguro ?>">
                                         <?= esc($aula['materia']) ?> <?= !empty($aula['intervalo']) ? '(' . esc($aula['intervalo']) . ')' : '' ?>
                                     </span>
                                 <?php else: ?>
                                     <span class="pin-tooltip-detail" id="tooltip_mat_<?= $idSeguro ?>" style="display:none;"></span>
                                 <?php endif; ?>
                             </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Controles Flotantes de Zoom y Visualización -->
        <div class="mapa-zoom-controls">
            <button type="button" class="btn-mapa-zoom" id="btn-zoom-in" title="Acercar mapa (+)">
                <i class="bx bx-plus"></i>
            </button>
            <button type="button" class="btn-mapa-zoom" id="btn-zoom-reset" title="Ver todo el campus (Vista completa)">
                <span class="zoom-indicator-text" id="zoom-porcentaje">100%</span>
            </button>
            <button type="button" class="btn-mapa-zoom" id="btn-zoom-out" title="Alejar mapa (-)">
                <i class="bx bx-minus"></i>
            </button>
            <button type="button" class="btn-mapa-zoom" id="btn-toggle-pines" title="Modo de pines (Auto / Ver todos / Ocultar)">
                <i class="bx bx-show" id="icono-toggle-pines"></i>
            </button>
            <button type="button" class="btn-mapa-zoom" id="btn-fullscreen-mapa" title="Pantalla completa">
                <i class="bx bx-fullscreen" id="icono-fullscreen"></i>
            </button>
        </div>

        <!-- Indicador sutil de navegación -->
        <div class="mapa-hint-bar" id="mapa-hint">
            <i class="bx bx-mouse"></i> Usa la rueda o botones para hacer zoom • Arrastra para explorar el campus
        </div>
    </div>

    <!-- Modal de Información y Horario Detallado del Aula -->
    <div id="modalAula" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalAula()">
        <div class="modal-contenido-aula">
            <div class="modal-aula-header">
                <h3 id="tituloAula"><i class="bx bx-calendar"></i> Horario del Aula</h3>
                <span onclick="cerrarModalAula()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
            </div>
            <div id="cuerpoHorario" class="modal-aula-body">
                <p>Cargando información...</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('js/mapa.js') ?>"></script>
<?= $this->endSection() ?>
