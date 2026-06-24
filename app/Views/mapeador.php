<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapeador de Coordenadas UMC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f9; }
        .mapa-container { position: relative; display: inline-block; border: 2px solid #ccc; cursor: crosshair; }
        .mapa-img { width: 100%; max-width: 1000px; height: auto; display: block; }
        .panel-info { position: fixed; top: 20px; right: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 2px solid #007bff; z-index: 100; }
        .marcador-temp { position: absolute; width: 10px; height: 10px; background-color: red; border-radius: 50%; transform: translate(-50%, -50%); pointer-events: none; }
    </style>
</head>
<body>

    <div class="panel-info">
        <h3>Coordenadas (Porcentaje)</h3>
        <p>Haz clic en cualquier aula del mapa.</p>
        <p><strong>X (Horizontal):</strong> <span id="coord_x">-</span>%</p>
        <p><strong>Y (Vertical):</strong> <span id="coord_y">-</span>%</p>
    </div>

    <div class="mapa-container" id="contenedorMapa">
        <img src="/assets/img/mapa_umc.jpg" id="imagenMapa" class="mapa-img" alt="Mapa de la UMC">
    </div>

    <script>
        const contenedorMapa = document.getElementById('contenedorMapa');
        const imagenMapa = document.getElementById('imagenMapa');
        const spanX = document.getElementById('coord_x');
        const spanY = document.getElementById('coord_y');

        imagenMapa.addEventListener('click', function(event) {
            const rect = imagenMapa.getBoundingClientRect();
            const xPx = event.clientX - rect.left;
            const yPx = event.clientY - rect.top;

            const xPorcentaje = (xPx / rect.width) * 100;
            const yPorcentaje = (yPx / rect.height) * 100;

            const xFinal = xPorcentaje.toFixed(2);
            const yFinal = yPorcentaje.toFixed(2);

            spanX.innerText = xFinal;
            spanY.innerText = yFinal;
            
            const marcadorViejo = document.getElementById('marcador');
            if (marcadorViejo) marcadorViejo.remove();

            const nuevoMarcador = document.createElement('div');
            nuevoMarcador.id = 'marcador';
            nuevoMarcador.className = 'marcador-temp';
            nuevoMarcador.style.left = xFinal + '%';
            nuevoMarcador.style.top = yFinal + '%';
            
            contenedorMapa.appendChild(nuevoMarcador);
        });
    </script>
</body>
</html>