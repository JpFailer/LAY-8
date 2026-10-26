<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Estudiantil - LAY-8 MAP</title>
    <!-- Fuentes oficiales del proyecto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Iconografía Boxicons & FontAwesome -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/global.css') ?>">
    <style>
        :root {
            --color-primary: #1e3a8a;
            --color-primary-hover: #1d4ed8;
            --color-secundary: #3b82f6;
            --color-bg-card: #ffffff;
            --color-text-main: #1f2937;
            --color-text-muted: #6b7280;
            --color-border: #e5e7eb;
            --color-pdf: #ef4444;
            --color-doc: #2563eb;
            --color-ppt: #ea580c;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f3f6fa;
            color: var(--color-text-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Barra de Navegación Superior */
        .barra-superior-biblio {
            background-color: var(--color-primary);
            color: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .barra-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }

        .barra-brand img {
            height: 38px;
            object-fit: contain;
        }

        .barra-brand .titulo-brand {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .barra-brand .subtitulo-brand {
            font-size: 0.85rem;
            opacity: 0.85;
            font-weight: 400;
        }

        .barra-acciones {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-volver-dashboard {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.12);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-volver-dashboard:hover {
            background: rgba(255, 255, 255, 0.22);
            transform: translateX(-2px);
        }

        .btn-subir-cta {
            background-color: #10b981;
            color: white;
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 9px 18px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
            transition: all 0.2s ease;
        }

        .btn-subir-cta:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(16, 185, 129, 0.35);
        }

        /* Contenedor Principal */
        .contenedor-biblio {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 30px 20px 60px 20px;
            flex: 1;
            box-sizing: border-box;
        }

        /* Alertas */
        .alerta-notif {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .alerta-exito {
            background-color: #ecfdf5;
            color: #065f46;
            border-left: 5px solid #10b981;
        }

        .alerta-error {
            background-color: #fef2f2;
            color: #991b1b;
            border-left: 5px solid #ef4444;
        }

        /* Cabecera del Feed & Búsqueda */
        .hero-biblio {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            padding: 36px 30px;
            border-radius: 14px;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(30, 58, 138, 0.15);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .hero-titulos h1 {
            font-size: 1.9rem;
            margin: 0 0 6px 0;
            font-weight: 700;
        }

        .hero-titulos p {
            margin: 0;
            font-size: 1rem;
            opacity: 0.9;
            font-family: 'Hind', sans-serif;
        }

        /* Buscador integrado */
        .caja-busqueda {
            display: flex;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            max-width: 700px;
            width: 100%;
        }

        .caja-busqueda input {
            flex: 1;
            border: none;
            padding: 14px 18px;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            color: #333;
        }

        .caja-busqueda button {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 0 24px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .caja-busqueda button:hover {
            background-color: var(--color-primary-hover);
        }

        /* Barra de Filtros / Categorías */
        .filtros-categorias {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            overflow-x: auto;
            padding-bottom: 6px;
        }

        .btn-filtro {
            text-decoration: none;
            padding: 9px 16px;
            border-radius: 20px;
            font-size: 0.88rem;
            font-weight: 600;
            color: #4b5563;
            background: #ffffff;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-filtro:hover {
            border-color: var(--color-secundary);
            color: var(--color-primary);
            background: #f0f7ff;
        }

        .btn-filtro.activo {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
            box-shadow: 0 3px 8px rgba(30, 58, 138, 0.2);
        }

        .badge-conteo {
            background: rgba(0, 0, 0, 0.08);
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .btn-filtro.activo .badge-conteo {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }

        /* Grid de Aportes */
        .grid-aportes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 22px;
        }

        /* Tarjeta de Aporte */
        .tarjeta-aporte {
            background: var(--color-bg-card);
            border-radius: 12px;
            border: 1px solid var(--color-border);
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
        }

        .tarjeta-aporte:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .tarjeta-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .icono-archivo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .icono-pdf {
            background-color: #fee2e2;
            color: var(--color-pdf);
        }

        .icono-doc {
            background-color: #dbeafe;
            color: var(--color-doc);
        }

        .icono-ppt {
            background-color: #ffedd5;
            color: var(--color-ppt);
        }

        .icono-otro {
            background-color: #f3e8ff;
            color: #9333ea;
        }

        .tarjeta-info-top {
            flex: 1;
            overflow: hidden;
        }

        .badge-categoria {
            display: inline-block;
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 5px;
            margin-bottom: 4px;
            letter-spacing: 0.4px;
        }

        .cat-guias {
            background: #e0f2fe;
            color: #0369a1;
        }

        .cat-ejercicios {
            background: #dcfce7;
            color: #15803d;
        }

        .cat-resumenes {
            background: #fef3c7;
            color: #b45309;
        }

        .cat-otros {
            background: #f3f4f6;
            color: #4b5563;
        }

        .titulo-archivo {
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
            margin: 4px 0 2px 0;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .nombre-archivo-original {
            font-size: 0.78rem;
            color: var(--color-text-muted);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: 'Hind', sans-serif;
        }

        .tarjeta-meta {
            padding: 12px 0;
            border-top: 1px dashed var(--color-border);
            border-bottom: 1px dashed var(--color-border);
            margin: 12px 0;
            font-size: 0.82rem;
            color: var(--color-text-muted);
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-family: 'Hind', sans-serif;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item i {
            font-size: 0.95rem;
            color: #9ca3af;
        }

        .meta-autor strong {
            color: #374151;
        }

        .tarjeta-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .peso-badge {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .btn-descargar {
            background-color: var(--color-primary);
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-descargar:hover {
            background-color: var(--color-primary-hover);
            transform: scale(1.02);
        }

        /* Estado Vacío (Empty State) */
        .estado-vacio {
            background: white;
            border-radius: 14px;
            padding: 60px 20px;
            text-align: center;
            border: 2px dashed #cbd5e1;
            margin: 30px 0;
        }

        .icono-vacio {
            font-size: 4rem;
            color: #94a3b8;
            margin-bottom: 15px;
        }

        .estado-vacio h3 {
            font-size: 1.4rem;
            color: #1e293b;
            margin: 0 0 8px 0;
        }

        .estado-vacio p {
            color: #64748b;
            font-size: 0.95rem;
            max-width: 480px;
            margin: 0 auto 24px auto;
            font-family: 'Hind', sans-serif;
        }

        /* Footer */
        .footer-biblio {
            background: #111827;
            color: #9ca3af;
            text-align: center;
            padding: 20px;
            font-size: 0.85rem;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .barra-superior-biblio {
                padding: 12px 16px;
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            .barra-acciones {
                justify-content: space-between;
            }
            .hero-biblio {
                padding: 24px 18px;
            }
            .hero-titulos h1 {
                font-size: 1.5rem;
            }
            .grid-aportes {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- 1. Barra de Navegación Superior -->
    <header class="barra-superior-biblio">
        <a href="<?= base_url('dashboard') ?>" class="barra-brand">
            <img src="<?= base_url('img/lay8_logo.png') ?>" alt="LAY-8 MAP">
            <div>
                <div class="titulo-brand">LAY-8 MAP</div>
                <div class="subtitulo-brand">Biblioteca Estudiantil UMC</div>
            </div>
        </a>

        <div class="barra-acciones">
            <a href="<?= base_url('dashboard') ?>" class="btn-volver-dashboard">
                <i class="bx bx-arrow-back"></i> Dashboard
            </a>
            <a href="<?= base_url('biblioteca/subir') ?>" class="btn-subir-cta">
                <i class="bx bx-cloud-upload"></i> + Subir Aporte
            </a>
        </div>
    </header>

    <!-- 2. Contenido Central -->
    <main class="contenedor-biblio">

        <!-- Mensajes Flash de Éxito o Error -->
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

        <!-- Hero Section con Buscador Integrado -->
        <section class="hero-biblio">
            <div class="hero-titulos">
                <h1>Repositorio de Materiales Académicos</h1>
                <p>Encuentra guías, resúmenes y ejercicios compartidos por compañeros y profesores de la Universidad Marítima del Caribe.</p>
            </div>

            <form class="caja-busqueda" method="get" action="<?= base_url('biblioteca') ?>">
                <?php if ($categoriaActual && $categoriaActual !== 'todas'): ?>
                    <input type="hidden" name="categoria" value="<?= esc($categoriaActual) ?>">
                <?php endif; ?>
                <input type="text" id="buscador-vivo" name="q" value="<?= esc($busquedaActual) ?>" placeholder="Buscar por título, materia o nombre del autor..." autocomplete="off">
                <button type="submit">
                    <i class="bx bx-search"></i> Buscar
                </button>
            </form>
        </section>

        <!-- Filtros por Categoría (Tabs) -->
        <nav class="filtros-categorias" aria-label="Filtro de categorías">
            <a href="<?= base_url('biblioteca' . (!empty($busquedaActual) ? '?q=' . urlencode($busquedaActual) : '')) ?>" 
               class="btn-filtro <?= ($categoriaActual === 'todas') ? 'activo' : '' ?>">
                <i class="bx bx-grid-alt"></i> Todos los Aportes
                <span class="badge-conteo"><?= (int) ($conteos['todos'] ?? 0) ?></span>
            </a>

            <a href="<?= base_url('biblioteca?categoria=guias' . (!empty($busquedaActual) ? '&q=' . urlencode($busquedaActual) : '')) ?>" 
               class="btn-filtro <?= ($categoriaActual === 'guias') ? 'activo' : '' ?>">
                <i class="bx bx-book-open"></i> Guías de Estudio
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
                <i class="bx bx-folder"></i> Otros Recursos
                <span class="badge-conteo"><?= (int) ($conteos['otros'] ?? 0) ?></span>
            </a>
        </nav>

        <!-- Listado de Recursos (Grid) -->
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
                            'guias'      => 'Guía de Estudio',
                            'ejercicios' => 'Ejercicios',
                            'resumenes'  => 'Resumen',
                            'otros'      => 'Material General'
                        ];
                        $catBadgeClass = 'cat-' . $cat;
                        $catTexto = $catLabels[$cat] ?? 'Aporte';

                        // Formato de peso en KB o MB
                        $bytes = (int) ($aporte['tamano_bytes'] ?? 0);
                        if ($bytes >= 1048576) {
                            $pesoTexto = number_format($bytes / 1048576, 1) . ' MB';
                        } elseif ($bytes > 0) {
                            $pesoTexto = number_format($bytes / 1024, 0) . ' KB';
                        } else {
                            $pesoTexto = 'N/D';
                        }

                        // Nombre de autor amigable
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
                                    <span><?= !empty($aporte['fecha_subida']) ? date('d/m/Y', strtotime($aporte['fecha_subida'])) : 'Fecha reciente' ?></span>
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
            <!-- Estado Vacío -->
            <div class="estado-vacio">
                <i class="bx bx-folder-open icono-vacio"></i>
                <h3>No se encontraron materiales</h3>
                <p>Aún no hay aportes registrados en esta categoría<?= !empty($busquedaActual) ? ' o con los términos buscados' : '' ?>. ¡Anímate a compartir el primer material con tus compañeros!</p>
                <a href="<?= base_url('biblioteca/subir') ?>" class="btn-subir-cta" style="display: inline-flex;">
                    <i class="bx bx-cloud-upload"></i> Compartir Material Ahora
                </a>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="footer-biblio">
        <p>LAY-8 MAP &copy; <?= date('Y') ?> • Universidad Marítima del Caribe (UMC)</p>
    </footer>

    <!-- Script de Búsqueda Reactiva Instantánea -->
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
</body>
</html>
