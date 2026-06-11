<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Validar Constancia - LAY-8 MAP</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 50px; }
        .alerta-error { background: #ffdddd; color: #d8000c; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #d8000c; }
        .alerta-exito { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

    <h2>Paso 1: Sube tu Constancia de Estudios</h2>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alerta-error">
            <strong>¡Error!</strong> <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('upload') ?>" method="POST" enctype="multipart/form-data">
        
        <?= csrf_field() ?>

        <div style="margin-bottom: 15px;">
            <label for="constancia_pdf">Archivo PDF (Solo constancias de la UMC):</label><br><br>
            <input type="file" name="constancia_pdf" id="constancia_pdf" accept=".pdf" required>
        </div>

        <button type="submit" style="padding: 10px 20px; background-color: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Validar Constancia
        </button>
    </form>

</body>
</html>