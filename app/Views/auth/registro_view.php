<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - LAY-8 MAP</title>
    <style>
        /* Mismos estilos base del Login */
        body {
            margin: 0;
            padding: 20px 0; /* Padding extra para que se pueda hacer scroll */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fdfdfd;
            background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
            background-size: 20px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .layout-container {
            width: 100%;
            max-width: 900px;
            padding: 20px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 40px;
        }
        
        .title-section h1 { margin: 0; font-size: 24px; color: #000; }
        .title-section span.subtitle { font-size: 16px; color: #666; font-weight: normal; margin-left: 10px; }
        .title-line { height: 2px; background-color: #1a56db; width: 70%; margin-top: 8px; }
        .logo { font-weight: bold; font-size: 18px; color: #001240; display: flex; align-items: center; gap: 8px; }

        /* Tarjeta más ancha para el registro */
        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            width: 100%;
            max-width: 500px; /* Más ancha que el login */
            margin: 0 auto;
        }

        /* Sistema de 2 columnas para el formulario */
        .row {
            display: flex;
            gap: 15px;
        }
        .col {
            flex: 1;
        }

        .form-group { margin-bottom: 15px; }
        
        label {
            display: block;
            font-size: 12px;
            color: #444;
            margin-bottom: 6px;
            margin-left: 15px;
            font-weight: 500;
        }

        input[type="text"], input[type="password"], input[type="email"], input[type="file"] {
            width: 100%;
            padding: 12px 20px;
            border: none;
            background-color: #e8e8e8;
            border-radius: 30px;
            box-sizing: border-box;
            font-size: 13px;
            outline: none;
            transition: background-color 0.3s;
        }

        input[type="text"]:focus, input[type="password"]:focus, input[type="email"]:focus {
            background-color: #dedede;
        }

        /* Estilo para los Radio Buttons (Es docente?) */
        .radio-group {
            display: flex;
            gap: 20px;
            margin-left: 15px;
            font-size: 13px;
            color: #444;
            align-items: center;
        }

        .btn-primary {
            background-color: #1a56db;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 30px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
            margin-top: 10px;
        }
        .btn-primary:hover { opacity: 0.9; }

        .alert-danger { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; }
        
        .register-link { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        .register-link a { color: #1a56db; text-decoration: none; font-weight: bold; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="layout-container">
        
        <div class="header-bar">
            <div class="title-section">
                <h1>Registro <span class="subtitle">Crea tu usuario</span></h1>
                <div class="title-line"></div>
            </div>
            <div class="logo">⚙️ LAY-8 MAP</div>
        </div>

        <div class="login-card">
            
            <?php if(isset($errores)): ?>
                <div class="alert-danger">
                    <?= $errores->listErrors() ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('registro/procesar') ?>" method="POST" enctype="multipart/form-data">
                
                <div class="row">
                    <div class="col form-group">
                        <label>Primer Nombre</label>
                        <input type="text" name="primer_nombre" required>
                    </div>
                    <div class="col form-group">
                        <label>Segundo Nombre (Opc.)</label>
                        <input type="text" name="segundo_nombre">
                    </div>
                </div>

                <div class="form-group">
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" required>
                </div>

                <div class="row">
                    <div class="col form-group">
                        <label>Usuario (Único)</label>
                        <input type="text" name="nombre_usuario" required>
                    </div>
                    <div class="col form-group">
                        <label>Teléfono (Opcional)</label>
                        <input type="text" name="telefono">
                    </div>
                </div>

                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" required>
                </div>

                <div class="row">
                    <div class="col form-group">
                        <label>Contraseña</label>
                        <input type="password" name="contrasena" required>
                    </div>
                    <div class="col form-group">
                        <label>Confirmar Contraseña</label>
                        <input type="password" name="repetir_contrasena" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label>¿Es docente?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="es_profesor" value="no" checked onchange="mostrarCarnet(false)"> No</label>
                        <label><input type="radio" name="es_profesor" value="si" onchange="mostrarCarnet(true)"> Sí</label>
                    </div>
                </div>

                <!-- Esta sección está oculta y se muestra solo si marca "Sí" -->
                <div class="form-group" id="div_carnet" style="display: none; background: #f0f4ff; padding: 15px; border-radius: 20px;">
                    <label style="color: #1a56db;">Verifique su rol: Subir Carnet (PDF, JPG, PNG)</label>
                    <input type="file" name="carnet" accept=".png, .jpg, .jpeg, .pdf">
                </div>

                <button type="submit" class="btn-primary">Finalizar Registro</button>
            </form>

            <div class="register-link">
                ¿Ya tienes cuenta? <a href="<?= base_url('login') ?>">Inicia Sesión</a>
            </div>

        </div>
    </div>

    <!-- Script para ocultar/mostrar el input del carnet de forma suave -->
    <script>
        function mostrarCarnet(mostrar) {
            const div = document.getElementById('div_carnet');
            div.style.display = mostrar ? 'block' : 'none';
        }
    </script>
</body>
</html>