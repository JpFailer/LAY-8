<div class="perfil-container" style="max-width: 800px; margin: 0 auto; font-family: 'Montserrat', sans-serif; padding: 20px;">

    <!-- Alertas de éxito o error -->
    <?php if(session()->getFlashdata('mensaje')): ?>
        <div style="background: #dcfce3; color: #166534; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <?= session()->getFlashdata('mensaje') ?>
        </div>
    <?php endif; ?>
    
    <?php if(session()->getFlashdata('error')): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <!-- Encabezado del Perfil -->
    <div style="display: flex; align-items: center; gap: 20px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div class="foto-wrapper" style="position: relative; text-align: center;">
            <img src="<?= base_url($usuario['foto_perfil'] ?? 'img/default-avatar.png') ?>" 
                 alt="Foto de perfil" 
                 style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #1e40af;">
            
            <form action="<?= base_url('perfil/subirFoto') ?>" method="post" enctype="multipart/form-data" style="margin-top: 10px;">
                <label for="foto" style="cursor: pointer; font-size: 13px; color: #1e40af; text-decoration: underline; font-weight: 500;">Cambiar foto</label>
                <input type="file" id="foto" name="foto" accept="image/png, image/jpeg, image/jpg" style="display: none;" onchange="this.form.submit()">
            </form>
        </div>
        
        <div>
            <h2 style="color: #1e40af; margin: 0;"><?= esc($usuario['primer_nombre'] . ' ' . $usuario['apellidos']) ?></h2>
            <p style="color: #666; margin: 5px 0 10px 0;">Rol: <?= esc(ucfirst($usuario['rol'])) ?></p>
            <a href="<?= base_url('logout') ?>" style="color: #ef4444; font-size: 14px; text-decoration: none; font-weight: 600;">Cerrar Sesión</a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div style="display: flex; gap: 20px; margin-top: 20px;">
        <div style="flex: 1; background: #fff; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; color: #1e40af; font-size: 24px;"><?= esc($total_aportes ?? 0) ?></h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Aportes</p>
        </div>
        <div style="flex: 1; background: #fff; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; color: #1e40af; font-size: 24px;"><?= esc($total_likes ?? 0) ?></h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Likes</p>
        </div>
    </div>
</div>