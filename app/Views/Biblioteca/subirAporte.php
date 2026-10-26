<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="subir-dashboard-contenedor">

    <div class="header-subida-dashboard">
        <div>
            <h2><i class="bx bx-cloud-upload"></i> Subir Material a la Biblioteca</h2>
            <p>Comparte tus guías, resúmenes o ejercicios con la comunidad estudiantil de la UMC.</p>
        </div>
        <a href="<?= base_url('biblioteca') ?>" class="btn-volver-dashboard-link">
            <i class="bx bx-arrow-back"></i> Volver a la Biblioteca
        </a>
    </div>

    <!-- Alertas Flash -->
    <?php if (session()->getFlashdata('errores')): ?>
        <div class="alerta-notif alerta-error">
            <i class="bx bx-error-circle" style="font-size: 1.3rem;"></i>
            <ul>
            <?php foreach (session()->getFlashdata('errores') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alerta-notif alerta-error">
            <i class="bx bx-error-circle" style="font-size: 1.3rem;"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- Formulario en Tarjetica Centrada -->
    <div class="card-formulario-subida">
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
            </ul>
        </div>
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
<?= $this->endSection() ?>
