<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/biblioteca-subir.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="subir-aporte-wrap">
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

        <?php if (session()->getFlashdata('errores')): ?>
            <div class="alerta-notif alerta-error">
                <i class="bx bx-error-circle"></i>
                <ul>
                <?php foreach (session()->getFlashdata('errores') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alerta-notif alerta-error">
                <i class="bx bx-error-circle"></i>
                <span><?= esc(session()->getFlashdata('error')) ?></span>
            </div>
        <?php endif; ?>

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
                <label for="carrera">Carrera o Área de Estudio <span style="color:#ef4444;">*</span></label>
                <select name="carrera" id="carrera" required>
                    <?php $carrSeleccionada = old('carrera', $carreraUsuario ?? 'GENERAL'); ?>
                    <?php foreach ($carrerasDefinidas as $cod => $nombreCar): ?>
                        <option value="<?= esc($cod) ?>" <?= ($carrSeleccionada === $cod) ? 'selected' : '' ?>>
                            <?= esc($nombreCar) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grupo-campo">
                <label>Selecciona tu documento <span style="color:#ef4444;">*</span></label>
                <div class="dropzone-file">
                    <input type="file" name="aporte" id="input-aporte" accept=".pdf,.doc,.docx,.ppt,.pptx" required onchange="mostrarNombreArchivoAporte(this)">
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
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function mostrarNombreArchivoAporte(input) {
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
