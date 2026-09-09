<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | LAY-8 MAP</title>
</head>
<body>
    <main>
        <h2>Paso 2: Informacion Personal</h2>
        <p>Ingresa tus datos basicos para configurar tu perfil</p>
        <form action="" method="post">
            <label for="nombre">Nombre</label><br>
            <input type="text" id="nombre" name="nombre" required><br><br>
            
            <label for="apellido">Apellido</label><br>
            <input type="text" id="apellido" name="apellido" required><br><br>
            
            <label for="cedula">Cedula de Identidad</label><br>
            <input type="number" id="cedula" name="cedula" required><br><br>
            
            <label for="correo">Correo Electronico</label><br>
            <input type="email" id="correo" name="correo" required><br><br>
            
            <label for="pass">Crea tu contraseña <span><em>(criterios de la contraseña)</eme></span></label><br>
            <input type="password" id="pass" name="pass" required><br><br>
            <input type="submit" value="Continuar">
            <input type="submit" value="Cancelar">
        </form>
</body>
</html>