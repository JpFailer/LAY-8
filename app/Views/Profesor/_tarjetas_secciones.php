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
    <article class="tarjeta-aporte tarjeta-aula-classroom" data-carrera="<?= esc($codCarreraSec) ?>" style="display: <?= $mostrarInicial ? 'flex' : 'none' ?>; flex-direction: column; justify-content: space-between;">
        <div>
            <div class="cabecera-tarjeta-aula">
                <div class="aula-header-info">
                    <span class="badge-codigo-materia"><?= esc($sec['codigo_materia']) ?></span>
                    <span class="badge-seccion-materia">Secc. <?= esc($sec['codigo_seccion']) ?></span>
                    <span class="badge-conteo" style="background: #eff6ff; color: #2563eb;">
                        <i class="bx bx-user"></i> <?= (int) $sec['total_estudiantes'] ?> alumnos
                    </span>
                </div>
                <h3 class="titulo-materia-aula"><?= esc($sec['materia']) ?></h3>
                <p class="profesor-materia" style="color: #64748b;">
                    <i class="bx bx-map-pin"></i> Aula: <strong><?= !empty($aulasSec) ? esc(implode(', ', $aulasSec)) : 'Por definir' ?></strong>
                </p>
            </div>

            <div class="estado-docente-box <?= $esOnline ? 'box-online' : 'box-presencial' ?>" style="margin-top: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong><i class="bx <?= $esOnline ? 'bx-video' : 'bx-walk' ?>"></i> Estado Hoy:</strong>
                    <span style="font-size: 0.76rem; text-transform: uppercase; font-weight: 700;"><?= $esOnline ? 'Clase Virtual' : 'Presencial' ?></span>
                </div>
                <div style="font-size: 0.88rem; margin-top: 4px; font-weight: 600;">
                    <?= $est ? esc($est['estado_rapido']) : 'Sin estado reportado hoy' ?>
                </div>
                <?php if ($est && !empty($est['enlace_virtual'])): ?>
                    <div style="margin-top: 6px;">
                        <a href="<?= esc($est['enlace_virtual']) ?>" target="_blank" style="color: #2563eb; font-size: 0.8rem; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="bx bx-link-external"></i> Ir a sala virtual
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn-subir-cta" style="flex: 1; padding: 8px 12px; font-size: 0.82rem;" onclick="abrirModalEstadoSeccion(<?= (int) $sec['id'] ?>, '<?= esc($sec['materia'], 'js') ?>', '<?= esc($sec['codigo_seccion'], 'js') ?>')">
                <i class="bx bx-broadcast"></i> Cambiar Estado
            </button>
            <button type="button" class="btn-subir-cta btn-secundario-aulas" style="flex: 1; padding: 8px 12px; font-size: 0.82rem;" onclick="preseleccionarSeccionTarea(<?= (int) $sec['id'] ?>)">
                <i class="bx bx-plus"></i> Asignar Tarea
            </button>
        </div>
    </article>
<?php endforeach; ?>
