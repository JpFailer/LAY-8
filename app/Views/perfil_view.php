<!DOCTYPE html>
<html lang="es">
<head><title>Mi Perfil - LAY-8 MAP</title></head>
<body>
    <h2>Perfil de <?= session()->get('primer_nombre') ?> <?= session()->get('apellidos') ?></h2>
    <a href="<?= base_url('logout') ?>">Cerrar Sesión</a>
    <hr>

    <?php if(session()->getFlashdata('mensaje')): ?>
        <p style="color: green;"><?= session()->getFlashdata('mensaje') ?></p>
    <?php endif; ?>
    <?php if(session()->getFlashdata('error')): ?>
        <p style="color: red;"><?= session()->getFlashdata('error') ?></p>
    <?php endif; ?>

    <?php if (session()->get('estado_verificacion') === 'pendiente'): ?>
        <div style="background: #e9ecef; padding: 10px;">
            Tu solicitud de profesor está en revisión. Te avisaremos pronto.
        </div>
    <?php elseif (session()->get('estado_verificacion') === 'aprobado'): ?>
        <div style="background: #d4edda; padding: 10px;">
            ¡Eres un profesor verificado! Ya puedes <a href="<?= base_url('crear-sala') ?>">crear salas</a>.
        </div>
    <?php else: ?>
        
        <?php if (session()->get('estado_verificacion') === 'rechazado'): ?>
            <div style="background: #f8d7da; padding: 10px;">
                Tu solicitud anterior fue rechazada. Por favor, sube un carnet válido.
            </div>
        <?php endif; ?>

        <div style="border: 1px solid #ccc; padding: 15px; margin-top: 20px;">
            <h4>¿Eres profesor de la UMC?</h4>
            <p>Sube tu carnet para solicitar permisos de creación de salas.</p>
            
            <form action="<?= base_url('perfil/solicitar-profesor') ?>" method="POST" enctype="multipart/form-data">
                <input type="file" name="carnet" required accept=".pdf, .jpg, .jpeg, .png"><br><br>
                <button type="submit">Enviar Solicitud</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>