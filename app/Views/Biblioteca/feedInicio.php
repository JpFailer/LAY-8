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

                    <div class="tarjeta-footer" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <span class="peso-badge"><?= strtoupper($ext) ?> • <?= $pesoTexto ?></span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php if ($ext === 'pdf'): ?>
                                <button type="button" class="btn-leer-aporte" data-id="<?= $aporte['id'] ?>" data-titulo="<?= esc($aporte['titulo']) ?>" data-url="<?= base_url('biblioteca/ver/' . $aporte['id']) ?>" onclick="abrirLectorAporte(this.dataset.id, this.dataset.titulo, this.dataset.url)" style="background: #eff6ff; color: #1e40af; border: 1.5px solid #bfdbfe; padding: 6px 12px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="bx bx-book-open"></i> Leer
                                </button>
                            <?php endif; ?>
                            <a href="<?= base_url('biblioteca/descargar/' . $aporte['id']) ?>" class="btn-descargar btn-descarga-item" data-aporte-id="<?= $aporte['id'] ?>" onclick="marcarAporteDescargado(<?= $aporte['id'] ?>)">
                                <i class="bx bx-download"></i> <span class="txt-btn-descarga">Descargar</span>
                            </a>
                        </div>
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
        actualizarBotonesDescargados();
    });

    function abrirLectorAporte(id, titulo, url) {
        document.getElementById('titulo-lector-doc').textContent = titulo;
        document.getElementById('iframe-visor-doc').src = url;
        document.getElementById('btn-descargar-desde-visor').href = '<?= rtrim(base_url('biblioteca/descargar'), '/') ?>/' + id;
        document.getElementById('modal-lector-biblioteca').style.display = 'flex';
    }

    function cerrarLectorAporte() {
        document.getElementById('modal-lector-biblioteca').style.display = 'none';
        document.getElementById('iframe-visor-doc').src = '';
    }

    function marcarAporteDescargado(id) {
        try {
            let desc = JSON.parse(localStorage.getItem('lay8_descargados') || '[]');
            if (!desc.includes(id)) {
                desc.push(id);
                localStorage.setItem('lay8_descargados', JSON.stringify(desc));
            }
            actualizarBotonesDescargados();
        } catch(e) {}
    }

    function actualizarBotonesDescargados() {
        try {
            let desc = JSON.parse(localStorage.getItem('lay8_descargados') || '[]');
            document.querySelectorAll('.btn-descarga-item').forEach(btn => {
                const id = parseInt(btn.getAttribute('data-aporte-id'));
                if (desc.includes(id)) {
                    btn.style.background = '#10b981';
                    btn.style.color = '#ffffff';
                    btn.style.borderColor = '#059669';
                    btn.innerHTML = '<i class="bx bx-check-double"></i> Guardado';
                    btn.title = 'Ya tienes este archivo descargado en tu dispositivo';
                }
            });
        } catch(e) {}
    }
</script>

<!-- Modal Lector Nativo de la Biblioteca (Revisión Haryerit) -->
<div id="modal-lector-biblioteca" class="modal-carreras-overlay" style="display: none; padding: 15px; box-sizing: border-box; z-index: 99999;" onclick="if(event.target === this) cerrarLectorAporte()">
  <div class="modal-editar-perfil-content" style="max-width: 950px; width: 100%; height: 90vh; display: flex; flex-direction: column; overflow: hidden; border-radius: 18px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);">
    <div class="modal-editar-header" style="padding: 14px 20px; background: #1e3a8a; color: white; display: flex; justify-content: space-between; align-items: center;">
      <h3 style="margin: 0; color: white; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
        <i class="bx bx-book-reader" style="color: #60a5fa; font-size: 1.3rem;"></i>
        <span id="titulo-lector-doc" style="max-width: 580px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Visor de Documento</span>
      </h3>
      <div style="display: flex; align-items: center; gap: 10px;">
        <a id="btn-descargar-desde-visor" href="#" class="btn-descargar" style="padding: 6px 14px; font-size: 0.82rem; background: #10b981; border: none; border-radius: 6px; color: white; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
          <i class="bx bx-download"></i> Descargar
        </a>
        <button type="button" class="btn-cerrar-carreras" onclick="cerrarLectorAporte()" style="color: white; font-size: 1.6rem; background: none; border: none; cursor: pointer;">&times;</button>
      </div>
    </div>
    <div style="flex: 1; background: #1e293b; position: relative;">
      <iframe id="iframe-visor-doc" src="" style="width: 100%; height: 100%; border: none;"></iframe>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
