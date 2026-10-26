<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="ajustes-pagina-contenedor">
    <div class="ajustes-pagina-header">
        <h2><i class="bx bx-cog"></i> Configuración y Ajustes</h2>
        <p>Personaliza tu experiencia y preferencias visuales en LAY-8.</p>
    </div>

    <div class="ajustes-seccion-card">
        <h3>Apariencia y Visualización</h3>
        
        <div class="ajuste-item">
            <div class="ajuste-info">
                <div class="ajuste-titulo">
                    <i class="bx bx-moon" id="icono-tema-visual-page"></i> Modo Oscuro
                </div>
                <div class="ajuste-descripcion">
                    Reduce el brillo de la pantalla y descansa la vista con una paleta de colores oscuros optimizada para el campus.
                </div>
            </div>
            <div class="ajuste-control">
                <label class="switch-toggle" for="toggle-dark-mode-page">
                    <input type="checkbox" id="toggle-dark-mode-page" class="toggle-dark-mode-input">
                    <span class="slider-toggle"></span>
                </label>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
