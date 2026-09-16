<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - LAY-8 MAP</title>
    <style>
        /* Fondo con patrón de puntos */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fdfdfd;
            background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
            background-size: 20px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Contenedor principal para imitar el layout de la imagen */
        .layout-container {
            width: 100%;
            max-width: 900px;
            padding: 20px;
        }

        /* Cabecera: Título y Logo */
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 50px;
        }
        
        .title-section h1 {
            margin: 0;
            font-size: 24px;
            color: #000;
        }
        
        .title-section span.subtitle {
            font-size: 16px;
            color: #666;
            font-weight: normal;
            margin-left: 10px;
        }
        
        .title-line {
            height: 2px;
            background-color: #1a56db;
            width: 70%;
            margin-top: 8px;
        }

        .logo {
            font-weight: bold;
            font-size: 18px;
            color: #001240;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Tarjeta central del formulario */
        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }

        /* Campos de entrada */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 12px;
            color: #444;
            margin-bottom: 8px;
            margin-left: 15px;
            font-weight: 500;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 20px;
            border: none;
            background-color: #e8e8e8; /* Gris claro imitando la imagen */
            border-radius: 30px;       /* Bordes muy redondos */
            box-sizing: border-box;
            font-size: 14px;
            outline: none;
            transition: background-color 0.3s;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            background-color: #dedede;
        }

        /* Botón Principal */
        .btn-primary {
            background-color: #1a56db; /* Azul característico */
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 30px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
            margin-top: 15px;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        /* Alertas de error/éxito */
        .alert {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        
        /* Enlace para registrarse */
        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: #666;
        }

        .register-link a {
            color: #1a56db;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        .register-link a:hover {
            color: #103a99;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="layout-container">
        
        <!-- Cabecera Superior -->
        <div class="header-bar">
            <div class="title-section">
                <h1>Inicio <span class="subtitle">Accede a tu cuenta</span></h1>
                <div class="title-line"></div>
            </div>
            <div class="logo">
                ⚙️ LAY-8 MAP
            </div>
        </div>

        <!-- Tarjeta de Login -->
        <div class="login-card">
            
            <?php if(session()->getFlashdata('mensaje')): ?>
                <div class="alert alert-success"><?= session()->getFlashdata('mensaje') ?></div>
            <?php endif; ?>
            <?php if(session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= base_url('login/autenticar') ?>" method="POST">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="nombre_usuario" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="contrasena" required>
                </div>

                <button type="submit" class="btn-primary">Entrar</button>
            </form>
            
            <!-- Nuevo enlace de registro -->
            <div class="register-link">
                ¿No tienes cuenta? <a href="<?= base_url('registro') ?>">Crea tu usuario</a>
            </div>

        </div>
    </div>

</body>
</html>