<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Aporte - Biblioteca LAY-8 MAP</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .container {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        h2 {
            margin-top: 0;
            color: #1e3a8a;
            font-size: 22px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        p.desc {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        select, input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
            color: #333;
        }
        input[type="file"] {
            border: 1px dashed #d1d5db;
            cursor: pointer;
        }
        select:hover, input[type="file"]:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        select:focus, input[type="file"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        button {
            background-color: #1e3a8a;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 6px;
            width: 100%;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background-color: #1d4ed8;
        }
        .info-box {
            margin-top: 20px;
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 10px 15px;
            font-size: 12px;
            color: #1e40af;
        }
        .info-box ul {
            margin: 5px 0 0 0;
            padding-left: 20px;
        }
        ul {
            margin: 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Subir Material a la Biblioteca</h2>
    <p class="desc">Comparte guías, ejercicios o resúmenes con la comunidad estudiantil.</p>

    <?php if (session()->getFlashdata('errores')): ?>
        <div class="alert alert-danger">
            <ul>
            <?php foreach (session()->getFlashdata('errores') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('mensaje')): ?>
        <div class="alert alert-success">
            <?= esc(session()->getFlashdata('mensaje')) ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('index.php/biblioteca/subirAporte') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="categoria">¿Qué tipo de material vas a compartir?</label>
            <select name="categoria" id="categoria" required>
                <option value="" disabled selected>Selecciona una categoría...</option>
                <option value="guias">Guía de estudio</option>
                <option value="ejercicios">Ejercicios propuestos / resueltos</option>
                <option value="resumenes">Resumen / Apuntes</option>
            </select>
        </div>

        <div class="form-group">
            <label for="aporte">Selecciona tu archivo:</label>
            <input type="file" name="aporte" id="aporte" required>
        </div>

        <button type="submit">Subir Aporte</button>
    </form>

    <div class="info-box">
        <strong>Restricciones del sistema:</strong>
        <ul>
            <li>Formatos admitidos: .pdf, .doc, .docx, .ppt</li>
            <li>Peso máximo permitido: 5 MB</li>
        </ul>
    </div>
</div>

</body>
</html>