<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="biblioteca-dashboard-contenedor">

    <!-- Header de la Biblioteca y botón de subir -->
    <div class="biblio-header-flex">
        <div>
            <h2><i class="bx bx-book-reader"></i> Repositorio de Materiales Académicos</h2>
            <p>Guías, ejercicios y resúmenes compartidos por la comunidad estudiantil de la UMC.</p>
        </div>
        <a href="<?= base_url('biblioteca/subir') ?>" class="btn-subir-cta">
            <i class="bx bx-cloud-upload"></i> + Subir Aporte
        </a>
    </div>

    <!-- Alertas Flash -->
    <?php if (session()->getFlashdata('mensaje')): ?>
        <div class="alerta-notif alerta-exito">
            <i class="bx bx-check-circle" style="font-size: 1.3rem;"></i>
            <span><?= esc(session()->getFlashdata('mensaje')) ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alerta-notif alerta-error">
            <i class="bx bx-error-circle" style="font-size: 1.3rem;"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- Buscador Integrado -->
    <div class="caja-busqueda-wrapper">
        <form class="caja-busqueda" method="get" action="<?= base_url('biblioteca') ?>">
            <?php if ($categoriaActual && $categoriaActual !== 'todas'): ?>
                <input type="hidden" name="categoria" value="<?= esc($categoriaActual) ?>">
            <?php endif; ?>
            <input type="text" id="buscador-vivo" name="q" value="<?= esc($busquedaActual) ?>" placeholder="Buscar por título, materia o nombre del autor..." autocomplete="off">
            <button type="submit">
                <i class="bx bx-search"></i> Buscar
            </button>
        </form>
    </div>

    <!-- Pestañas de Filtros por Categoría (Chips) -->
    <nav class="filtros-categorias">
        <a href="<?= base_url('biblioteca' . (!empty($busquedaActual) ? '?q=' . urlencode($busquedaActual) : '')) ?>" 
           class="btn-filtro <?= ($categoriaActual === 'todas') ? 'activo' : '' ?>">
            <i class="bx bx-grid-alt"></i> Todos los Aportes
            <span class="badge-conteo"><?= (int) ($conteos['todos'] ?? 0) ?></span>
        </a>

        <a href="<?= base_url('biblioteca?categoria=guias' . (!empty($busquedaActual) ? '&q=' . urlencode($busquedaActual) : '')) ?>" 
           class="btn-filtro <?= ($categoriaActual === 'guias') ? 'activo' : '' ?>">
            <i class="bx bx-book-bookmark"></i> Guías
            <span class="badge-conteo"><?= (int) ($conteos['guias'] ?? 0) ?></span>
        </a>

        <a href="<?= base_url('biblioteca?categoria=ejercicios' . (!empty($busquedaActual) ? '&q=' . urlencode($busquedaActual) : '')) ?>" 
           class="btn-filtro <?= ($categoriaActual === 'ejercicios') ? 'activo' : '' ?>">
            <i class="bx bx-edit-alt"></i> Ejercicios
            <span class="badge-conteo"><?= (int) ($conteos['ejercicios'] ?? 0) ?></span>
        </a>

        <a href="<?= base_url('biblioteca?categoria=resumenes' . (!empty($busquedaActual) ? '&q=' . urlencode($busquedaActual) : '')) ?>" 
           class="btn-filtro <?= ($categoriaActual === 'resumenes') ? 'activo' : '' ?>">
            <i class="bx bx-file-blank"></i> Resúmenes
            <span class="badge-conteo"><?= (int) ($conteos['resumenes'] ?? 0) ?></span>
        </a>

        <a href="<?= base_url('biblioteca?categoria=otros' . (!empty($busquedaActual) ? '&q=' . urlencode($busquedaActual) : '')) ?>" 
           class="btn-filtro <?= ($categoriaActual === 'otros') ? 'activo' : '' ?>">
            <i class="bx bx-folder"></i> Otros
            <span class="badge-conteo"><?= (int) ($conteos['otros'] ?? 0) ?></span>
        </a>
    </nav>

    <!-- Grid de Tarjeticas de Aportes -->
    <?php if (!empty($aportes) && count($aportes) > 0): ?>
        <div class="grid-aportes" id="grid-aportes">
            <?php foreach ($aportes as $aporte): ?>
                <?php
                    $ext = strtolower($aporte['extension'] ?? pathinfo($aporte['nombre_original'], PATHINFO_EXTENSION));
                    $iconoClass = 'icono-otro';
                    $iconoTag   = 'bx bx-file';
                    if ($ext === 'pdf') {
                        $iconoClass = 'icono-pdf';
                        $iconoTag   = 'bx bxs-file-pdf';
                    } elseif (in_array($ext, ['doc', 'docx'])) {
                        $iconoClass = 'icono-doc';
                        $iconoTag   = 'bx bxs-file-doc';
                    } elseif (in_array($ext, ['ppt', 'pptx'])) {
                        $iconoClass = 'icono-ppt';
                        $iconoTag   = 'bx bxs-file';
                    }

                    $cat = strtolower($aporte['categoria'] ?? 'otros');
                    $catLabels = [
                        'guias'      => 'Guía',
                        'ejercicios' => 'Ejercicios',
                        'resumenes'  => 'Resumen',
                        'otros'      => 'General'
                    ];
                    $catBadgeClass = 'cat-' . $cat;
                    $catTexto = $catLabels[$cat] ?? 'Aporte';

                    $bytes = (int) ($aporte['tamano_bytes'] ?? 0);
                    if ($bytes >= 1048576) {
                        $pesoTexto = number_format($bytes / 1048576, 1) . ' MB';
                    } elseif ($bytes > 0) {
                        $pesoTexto = number_format($bytes / 1024, 0) . ' KB';
                    } else {
                        $pesoTexto = 'N/D';
                    }

                    $autor = 'Comunidad UMC';
                    if (!empty($aporte['primer_nombre']) || !empty($aporte['apellidos'])) {
                        $autor = trim(($aporte['primer_nombre'] ?? '') . ' ' . ($aporte['apellidos'] ?? ''));
                    } elseif (!empty($aporte['nombre_usuario'])) {
                        $autor = '@' . $aporte['nombre_usuario'];
                    }
                ?>
                <article class="tarjeta-aporte" data-titulo="<?= strtolower(esc($aporte['titulo'])) ?>" data-autor="<?= strtolower(esc($autor)) ?>" data-archivo="<?= strtolower(esc($aporte['nombre_original'])) ?>">
                    <div>
                        <div class="tarjeta-header">
                            <div class="icono-archivo <?= $iconoClass ?>">
                                <i class="<?= $iconoTag ?>"></i>
                            </div>
                            <div class="tarjeta-info-top">
                                <span class="badge-categoria <?= $catBadgeClass ?>"><?= $catTexto ?></span>
                                <h3 class="titulo-archivo" title="<?= esc($aporte['titulo']) ?>">
                                    <?= esc($aporte['titulo']) ?>
                                </h3>
                                <p class="nombre-archivo-original" title="<?= esc($aporte['nombre_original']) ?>">
                                    <?= esc($aporte['nombre_original']) ?>
                                </p>
                            </div>
                        </div>

                        <div class="tarjeta-meta">
                            <div class="meta-item meta-autor">
                                <i class="bx bx-user-circle"></i>
                                <span>Subido por: <strong><?= esc($autor) ?></strong></span>
                            </div>
                            <div class="meta-item">
                                <i class="bx bx-calendar"></i>
                                <span><?= !empty($aporte['fecha_subida']) ? date('d/m/Y', strtotime($aporte['fecha_subida'])) : 'Reciente' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="tarjeta-footer">
                        <span class="peso-badge"><?= strtoupper($ext) ?> • <?= $pesoTexto ?></span>
                        <a href="<?= base_url('biblioteca/descargar/' . $aporte['id']) ?>" class="btn-descargar">
                            <i class="bx bx-download"></i> Descargar
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="estado-vacio">
            <i class="bx bx-folder-open icono-vacio"></i>
            <h3>No se encontraron materiales</h3>
            <p>Aún no hay aportes registrados en esta categoría<?= !empty($busquedaActual) ? ' o con los términos buscados' : '' ?>. ¡Sé el primero en compartir con tus compañeros!</p>
            <a href="<?= base_url('biblioteca/subir') ?>" class="btn-subir-cta" style="display: inline-flex;">
                <i class="bx bx-cloud-upload"></i> Subir Primer Material
            </a>
        </div>
    <?php endif; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputVivo = document.getElementById('buscador-vivo');
        const tarjetas = document.querySelectorAll('.tarjeta-aporte');

        if (inputVivo && tarjetas.length > 0) {
            inputVivo.addEventListener('input', function() {
                const termino = this.value.toLowerCase().trim();

                tarjetas.forEach(function(card) {
                    const titulo  = card.getAttribute('data-titulo') || '';
                    const autor   = card.getAttribute('data-autor') || '';
                    const archivo = card.getAttribute('data-archivo') || '';

                    if (titulo.includes(termino) || autor.includes(termino) || archivo.includes(termino)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
<?= $this->endSection() ?>
