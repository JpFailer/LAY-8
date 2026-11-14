<?php
    $esModoServidor = $esModoServidor ?? false;
    $idxSec = 0;
?>
<?php foreach ($secciones as $sec): ?>
    <?php
        $est = $sec['estado_hoy'] ?? null;
        $esOnline = ($est && ($est['modalidad'] ?? '') === 'online');
        $aulasSec = [];
        if (!empty($sec['horarios'])) {
            foreach ($sec['horarios'] as $h) {
                if (!in_array($h['aula'], $aulasSec, true)) {
                    $aulasSec[] = $h['aula'];
                }
            }
        }
        $codCarreraSec = strtoupper(trim((string) ($sec['carrera'] ?? '')));
        $mostrarInicial = $esModoServidor || ($idxSec < 12);
        $idxSec++;
    ?>
    <article class="tarjeta-aporte tarjeta-aula-classroom docente-card-flex" data-carrera="<?= esc($codCarreraSec) ?>" style="display: <?= $mostrarInicial ? 'flex' : 'none' ?>;">
        <div>
            <div class="cabecera-tarjeta-aula">
                <div class="aula-header-info">
                    <span class="badge-codigo-materia"><?= esc($sec['codigo_materia']) ?></span>
                    <span class="badge-seccion-materia">Secc. <?= esc($sec['codigo_seccion']) ?></span>
                    <span class="badge-conteo docente-badge-conteo-azul">
                        <i class="bx bx-user"></i> <?= (int) $sec['total_estudiantes'] ?> alumnos
                    </span>
                </div>
                <h3 class="titulo-materia-aula"><?= esc($sec['materia']) ?></h3>
                <p class="profesor-materia docente-prof-muted">
                    <i class="bx bx-map-pin"></i> Aula: <strong><?= !empty($aulasSec) ? esc(implode(', ', $aulasSec)) : 'Por definir' ?></strong>
                </p>
            </div>

            <div class="estado-docente-box docente-estado-mt <?= $esOnline ? 'box-online' : 'box-presencial' ?>">
                <div class="docente-estado-header">
                    <strong><i class="bx <?= $esOnline ? 'bx-video' : 'bx-walk' ?>"></i> Estado Hoy:</strong>
                    <span class="docente-estado-mod"><?= $esOnline ? 'Clase Virtual' : 'Presencial' ?></span>
                </div>
                <div class="docente-estado-txt">
                    <?= $est ? esc($est['estado_rapido']) : 'Sin estado reportado hoy' ?>
                </div>
                <?php
                    $enlaceSala = ($est && !empty($est['enlace_virtual'])) ? trim((string) $est['enlace_virtual']) : null;
                    if ($enlaceSala && preg_match('#^https?://#i', $enlaceSala)):
                ?>
                    <div class="docente-enlace-wrap">
                        <a href="<?= esc($enlaceSala) ?>" target="_blank" rel="noopener noreferrer" class="docente-enlace-sala">
                            <i class="bx bx-link-external"></i> Ir a sala virtual
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="docente-card-acciones">
            <button type="button" class="btn-subir-cta docente-btn-accion" onclick="abrirModalEstadoSeccion(<?= (int) $sec['id'] ?>, '<?= esc($sec['materia'], 'js') ?>', '<?= esc($sec['codigo_seccion'], 'js') ?>')">
                <i class="bx bx-broadcast"></i> Cambiar Estado
            </button>
            <button type="button" class="btn-subir-cta btn-secundario-aulas docente-btn-accion" onclick="preseleccionarSeccionTarea(<?= (int) $sec['id'] ?>)">
                <i class="bx bx-plus"></i> Asignar Tarea
            </button>
        </div>
    </article>
<?php endforeach; ?>
