<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Material - Biblioteca LAY-8 MAP</title>
    <!-- Fuentes oficiales del proyecto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Iconografía Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/global.css') ?>">
    <style>
        :root {
            --color-primary: #1e3a8a;
            --color-primary-hover: #1d4ed8;
            --color-secundary: #3b82f6;
            --color-border: #e2e8f0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f0f4f8;
            background: linear-gradient(180deg, #f0f4f8 0%, #e2e8f0 100%);
            color: #1e293b;
            margin: 0;
            padding: 30px 15px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
        }

        .contenedor-subida {
            background: #ffffff;
            padding: 35px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 540px;
            border: 1px solid var(--color-border);
        }

        .header-subida {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .header-subida h2 {
            margin: 0;
            color: var(--color-primary);
            font-size: 1.35rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-volver-biblio {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            background: #f1f5f9;
            transition: all 0.2s ease;
        }

        .btn-volver-biblio:hover {
            color: var(--color-primary);
            background: #e2e8f0;
            transform: translateX(-2px);
        }

        .descripcion-subida {
            font-size: 0.92rem;
            color: #64748b;
            margin-bottom: 22px;
            line-height: 1.45;
            font-family: 'Hind', sans-serif;
        }

        /* Alertas */
        .alerta {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-family: 'Hind', sans-serif;
        }

        .alerta-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alerta-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alerta ul {
            margin: 0;
            padding-left: 18px;
        }

        /* Formularios */
        .grupo-campo {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.88rem;
            color: #334155;
        }

        label span.opcional {
            font-weight: 400;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        input[type="text"],
        select {
            display: block;
            width: 100%;
            padding: 11px 14px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 0.92rem;
            color: #1e293b;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="text"]:focus,
        select:focus {
            outline: none;
            border-color: var(--color-secundary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* Zona de Carga de Archivos */
        .dropzone-file {
            position: relative;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 24px 15px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dropzone-file:hover {
            border-color: var(--color-secundary);
            background: #f0f7ff;
        }

        .dropzone-file input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .dropzone-icono {
            font-size: 2.4rem;
            color: var(--color-secundary);
            margin-bottom: 6px;
        }

        .dropzone-texto {
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 3px;
        }

        .dropzone-subtexto {
            font-size: 0.78rem;
            color: #64748b;
            font-family: 'Hind', sans-serif;
        }

        .nombre-archivo-seleccionado {
            margin-top: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #10b981;
            display: none;
        }

        /* Botón de Enviar */
        .btn-enviar-aporte {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 13px 20px;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-enviar-aporte:hover {
            background-color: var(--color-primary-hover);
            transform: translateY(-1px);
        }

        /* Caja informativa */
        .info-restricciones {
            margin-top: 22px;
            background-color: #eff6ff;
            border-left: 4px solid var(--color-secundary);
            padding: 12px 14px;
            border-radius: 0 8px 8px 0;
            font-size: 0.8rem;
            color: #1e40af;
            font-family: 'Hind', sans-serif;
        }

        .info-restricciones ul {
            margin: 4px 0 0 0;
            padding-left: 18px;
        }
    </style>
</head>
<body>

<div class="contenedor-subida">
    
    <div class="header-subida">
        <h2><i class="bx bx-cloud-upload"></i> Subir Material</h2>
        <a href="<?= base_url('biblioteca') ?>" class="btn-volver-biblio">
            <i class="bx bx-arrow-back"></i> Volver
        </a>
    </div>

    <p class="descripcion-subida">
        Comparte tus guías de estudio, exámenes resueltos o resúmenes para apoyar a la comunidad de la UMC.
    </p>

    <!-- Alertas -->
    <?php if (session()->getFlashdata('errores')): ?>
        <div class="alerta alerta-danger">
            <i class="bx bx-error-circle" style="font-size: 1.2rem;"></i>
            <ul>
            <?php foreach (session()->getFlashdata('errores') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alerta alerta-danger">
            <i class="bx bx-error-circle" style="font-size: 1.2rem;"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form action="<?= base_url('biblioteca/subirAporte') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="grupo-campo">
            <label for="titulo">Título descriptivo del aporte <span class="opcional">(Opcional)</span></label>
            <input type="text" name="titulo" id="titulo" placeholder="Ej: Guía de Ejercicios - Cálculo Vectorial Tema 2" maxlength="150" value="<?= old('titulo') ?>">
        </div>

        <div class="grupo-campo">
            <label for="categoria">Categoría del recurso <span style="color:#ef4444;">*</span></label>
            <select name="categoria" id="categoria" required>
                <option value="" disabled selected>Selecciona una categoría...</option>
                <option value="guias" <?= (old('categoria') === 'guias') ? 'selected' : '' ?>>Guía de estudio</option>
                <option value="ejercicios" <?= (old('categoria') === 'ejercicios') ? 'selected' : '' ?>>Ejercicios propuestos / resueltos</option>
                <option value="resumenes" <?= (old('categoria') === 'resumenes') ? 'selected' : '' ?>>Resumen / Apuntes de clase</option>
                <option value="otros" <?= (old('categoria') === 'otros') ? 'selected' : '' ?>>Otro material académico</option>
            </select>
        </div>

        <div class="grupo-campo">
            <label>Selecciona tu documento <span style="color:#ef4444;">*</span></label>
            <div class="dropzone-file">
                <input type="file" name="aporte" id="input-aporte" accept=".pdf,.doc,.docx,.ppt,.pptx" required onchange="mostrarNombreArchivo(this)">
                <i class="bx bx-file-blank dropzone-icono"></i>
                <div class="dropzone-texto">Haz clic para buscar o arrastra tu archivo aquí</div>
                <div class="dropzone-subtexto">Admite PDF, Word (.doc, .docx) y PowerPoint (.ppt, .pptx)</div>
                <div class="nombre-archivo-seleccionado" id="nombre-archivo-seleccionado">
                    <i class="bx bx-check-circle"></i> Archivo seleccionado: <span id="texto-nombre"></span>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-enviar-aporte">
            <i class="bx bx-send"></i> Publicar Aporte en la Biblioteca
        </button>
    </form>

    <div class="info-restricciones">
        <strong>Requisitos de publicación:</strong>
        <ul>
            <li>Formatos permitidos: <strong>.pdf, .doc, .docx, .ppt, .pptx</strong></li>
            <li>Tamaño máximo permitido: <strong>5 MB</strong></li>
            <li>El material quedará accesible para todos los estudiantes de la comunidad UMC.</li>
        </ul>
    </div>
</div>

<script>
    function mostrarNombreArchivo(input) {
        const contenedor = document.getElementById('nombre-archivo-seleccionado');
        const texto = document.getElementById('texto-nombre');
        if (input.files && input.files[0]) {
            texto.textContent = input.files[0].name;
            contenedor.style.display = 'block';
        } else {
            contenedor.style.display = 'none';
        }
    }
</script>

</body>
</html>
