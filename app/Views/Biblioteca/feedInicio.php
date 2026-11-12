<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="biblioteca-dashboard-contenedor">

    <!-- Header de la Biblioteca y botón de subir -->
    <div class="biblio-header-flex">
        <div>
            <h2><i class="bx bx-book-reader"></i> Repositorio de Materiales Académicos</h2>
        </div>
        <a href="<?= base_url('biblioteca/subir') ?>" class="btn-subir-cta">
            <i class="bx bx-cloud-upload"></i>Subir Aporte
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
            <?php if ($carreraActual && $carreraActual !== 'todas'): ?>
                <input type="hidden" name="carrera" value="<?= esc($carreraActual) ?>">
            <?php endif; ?>
            <input type="text" id="buscador-vivo" name="q" value="<?= esc($busquedaActual) ?>" placeholder="Buscar por título, materia o nombre del autor..." autocomplete="off">
            <button type="submit">
                <i class="bx bx-search"></i> Buscar
            </button>
        </form>
    </div>

    <!-- Barra de Filtros por Carrera (Estudiantes y Docentes) -->
    <div class="filtros-carrera-wrapper" style="margin-bottom: 16px;">
        <div class="filtros-carrera-label" style="font-size: 0.82rem; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
            <i class="bx bx-filter-alt"></i> Filtrar por Carrera:
        </div>
        <div class="filtros-carreras-pills">
            <?php foreach ($carrerasDefinidas as $cod => $info): ?>
                <?php
                    $activo = ($carreraActual === $cod);
                    $urlFiltro = base_url('biblioteca?carrera=' . urlencode($cod));
                    if ($categoriaActual && $categoriaActual !== 'todas') {
                        $urlFiltro .= '&categoria=' . urlencode($categoriaActual);
                    }
                    if (!empty($busquedaActual)) {
                        $urlFiltro .= '&q=' . urlencode($busquedaActual);
                    }
                    $conteoCar = $conteosCarreras[$cod] ?? 0;
                ?>
                <a href="<?= $urlFiltro ?>" class="btn-filtro-carrera <?= $activo ? 'activo' : '' ?>" data-carrera="<?= esc($cod) ?>">
                    <i class="bx <?= esc($info['icono']) ?>"></i> <?= esc($info['nombre']) ?>
                    <span class="badge-conteo-carrera"><?= (int)$conteoCar ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pestañas de Filtros por Categoría (Chips) -->
    <?php
        $baseParams = [];
        if ($carreraActual && $carreraActual !== 'todas') {
            $baseParams['carrera'] = $carreraActual;
        }
        if (!empty($busquedaActual)) {
            $baseParams['q'] = $busquedaActual;
        }

        $generarUrlCat = function($cat = null) use ($baseParams) {
            $p = $baseParams;
            if ($cat && $cat !== 'todas') {
                $p['categoria'] = $cat;
            }
            return base_url('biblioteca' . (!empty($p) ? '?' . http_build_query($p) : ''));
        };
    ?>
    <nav class="filtros-categorias">
        <a href="<?= $generarUrlCat('todas') ?>" 
           class="btn-filtro <?= ($categoriaActual === 'todas') ? 'activo' : '' ?>">
            <i class="bx bx-grid-alt"></i> Todos los Aportes
            <span class="badge-conteo"><?= (int) ($conteos['todos'] ?? 0) ?></span>
        </a>

        <a href="<?= $generarUrlCat('guias') ?>" 
           class="btn-filtro <?= ($categoriaActual === 'guias') ? 'activo' : '' ?>">
            <i class="bx bx-book-bookmark"></i> Guías
            <span class="badge-conteo"><?= (int) ($conteos['guias'] ?? 0) ?></span>
        </a>

        <a href="<?= $generarUrlCat('ejercicios') ?>" 
           class="btn-filtro <?= ($categoriaActual === 'ejercicios') ? 'activo' : '' ?>">
            <i class="bx bx-edit-alt"></i> Ejercicios
            <span class="badge-conteo"><?= (int) ($conteos['ejercicios'] ?? 0) ?></span>
        </a>

        <a href="<?= $generarUrlCat('resumenes') ?>" 
           class="btn-filtro <?= ($categoriaActual === 'resumenes') ? 'activo' : '' ?>">
            <i class="bx bx-file-blank"></i> Resúmenes
            <span class="badge-conteo"><?= (int) ($conteos['resumenes'] ?? 0) ?></span>
        </a>

        <a href="<?= $generarUrlCat('otros') ?>" 
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
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-bottom: 4px;">
                                    <span class="badge-categoria <?= $catBadgeClass ?>"><?= $catTexto ?></span>
                                    <?php 
                                        $carrCod = $aporte['carrera'] ?? ($aporte['autor_carrera'] ?? 'GENERAL');
                                        if (empty($carrCod)) $carrCod = 'GENERAL';
                                        $infoCarrera = $carrerasDefinidas[$carrCod] ?? null;
                                    ?>
                                    <span class="badge-carrera-biblioteca badge-carrera-<?= strtolower(esc($carrCod)) ?>">
                                        <?= esc($infoCarrera['nombre'] ?? $carrCod) ?>
                                    </span>
                                </div>
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

                    <div class="tarjeta-footer" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <span class="peso-badge"><?= strtoupper($ext) ?> • <?= $pesoTexto ?></span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <a href="<?= base_url('biblioteca/descargar/' . $aporte['id']) ?>" class="btn-descargar btn-descarga-item" data-aporte-id="<?= $aporte['id'] ?>" onclick="return marcarAporteDescargado(event, <?= $aporte['id'] ?>)">
                                <i class="bx bx-download"></i> <span class="txt-btn-descarga">Descargar</span>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($totalPaginas) && $totalPaginas > 1): ?>
            <?php
                $pagParams = $baseParams;
                if ($categoriaActual && $categoriaActual !== 'todas') {
                    $pagParams['categoria'] = $categoriaActual;
                }
                $urlPag = static function (int $p) use ($pagParams): string {
                    $q = array_merge($pagParams, ['page' => $p]);
                    return base_url('biblioteca?' . http_build_query($q));
                };
            ?>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-top:22px; padding:12px 16px; border-radius:12px; background:var(--lay8-bg-subtle); border:1px solid var(--lay8-border-subtle);">
                <span style="font-size:0.85rem; color:var(--lay8-text-muted); font-weight:600;">
                    Página <?= (int) $paginaActual ?> de <?= (int) $totalPaginas ?> (<?= (int) $totalAportes ?> aportes)
                </span>
                <div style="display:flex; gap:8px;">
                    <?php if ($paginaActual > 1): ?>
                        <a href="<?= $urlPag($paginaActual - 1) ?>" class="btn-filtro" style="text-decoration:none;"><i class="bx bx-chevron-left"></i> Anterior</a>
                    <?php endif; ?>
                    <?php if ($paginaActual < $totalPaginas): ?>
                        <a href="<?= $urlPag($paginaActual + 1) ?>" class="btn-filtro" style="text-decoration:none;">Siguiente <i class="bx bx-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset_url('js/biblioteca.js') ?>"></script>
<?= $this->endSection() ?>
