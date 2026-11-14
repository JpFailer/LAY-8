<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - LAY-8 UMC</title>
    <script>
      (function() {
        try {
          if (localStorage.getItem('lay8_theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
            document.addEventListener('DOMContentLoaded', function() { document.body.classList.add('dark-mode'); });
          }
        } catch (e) {}
      })();
    </script>
    <link rel="icon" type="image/png" href="<?= base_url('img/lay8_logo.png') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/global.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/registro.css') ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="fondo-login">
    <div class="login-wrapper">
        <div class="card-global" style="max-width: 460px; margin: 40px auto; padding: 32px 28px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <a href="<?= base_url('/') ?>" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                    <img src="<?= base_url('img/lay8_logo.png') ?>" alt="Logo LAY-8" style="height: 48px; width: auto;">
                    <span style="font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 1.6rem; color: #1e3a8a;">LAY-8</span>
                </a>
                <h2 class="form-titulo" style="font-size: 1.35rem; margin-top: 14px;">Restablecer Contraseña</h2>
                <p class="form-subtitulo" style="font-size: 0.88rem; color: #64748b;">
                    <?= $tokenValido ? 'Define una nueva clave de acceso para tu cuenta universitaria.' : 'Enlace de recuperación caducado o inválido.' ?>
                </p>
            </div>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alerta-notif alerta-error" style="margin-bottom: 16px;">
                    <i class="bx bx-error-circle"></i>
                    <span><?= esc(session()->getFlashdata('error')) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($tokenValido && !empty($usuario)): ?>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; font-size: 0.86rem; color: #1e40af; display: flex; align-items: center; gap: 8px;">
                    <i class="bx bxs-user-check" style="font-size: 1.25rem;"></i>
                    <span>Cuenta: <strong><?= esc($usuario['nombre_usuario']) ?></strong> (<?= esc(trim($usuario['primer_nombre'] . ' ' . $usuario['apellidos'])) ?>)</span>
                </div>

                <form action="<?= base_url('restablecer-contrasena') ?>" method="post" class="formulario-estilo-lay8">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= esc($token) ?>">

                    <div class="campo-grupo" style="margin-bottom: 14px;">
                        <label for="nueva_contrasena"><i class="bx bx-lock-alt"></i> Nueva Contraseña:</label>
                        <input type="password" name="nueva_contrasena" id="nueva_contrasena" required minlength="8" placeholder="Mínimo 8 caracteres" class="input-global">
                    </div>

                    <div class="campo-grupo" style="margin-bottom: 20px;">
                        <label for="repetir_contrasena"><i class="bx bx-check-shield"></i> Confirmar Contraseña:</label>
                        <input type="password" name="repetir_contrasena" id="repetir_contrasena" required minlength="8" placeholder="Repite tu nueva contraseña" class="input-global">
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; font-size: 0.95rem; font-weight: 700; border-radius: 8px;">
                        <i class="bx bx-save"></i> Guardar Nueva Contraseña
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 16px 0;">
                    <div style="width: 60px; height: 60px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 12px;">
                        <i class="bx bx-time-five"></i>
                    </div>
                    <p style="font-size: 0.92rem; color: #64748b; line-height: 1.5; margin-bottom: 20px;">
                        Este enlace de recuperación ya fue utilizado o ha superado el tiempo máximo de validez (2 horas).
                    </p>
                    <a href="<?= base_url('login') ?>" class="btn-primary" style="display: inline-block; width: 100%; text-decoration: none; padding: 12px; border-radius: 8px; font-weight: 600; text-align: center; box-sizing: border-box;">
                        Solicitar Nuevo Enlace
                    </a>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 20px; font-size: 0.85rem;">
                <a href="<?= base_url('login') ?>" style="color: #2563eb; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bx bx-arrow-back"></i> Volver al Inicio de Sesión
                </a>
            </div>
        </div>
    </div>
    <script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>
