<?php 
/** @var float $coord_x 
 * @var float $coord_y 
 */ 
?>

<div id="modalAula" class="modal-info" style="display:none;">
    <div class="modal-contenido">
        <span onclick="cerrarModal()" style="cursor:pointer; float:right; font-size: 24px;">&times;</span>
        <h2 id="tituloAula" style="margin-top: 0; color: #333;">Horario</h2>
        <div id="cuerpoHorario" style="margin-top: 15px;"></div>
    </div>
</div>

<div class="contenedor-mapa">
    <img src="/img/mapa_umc.jpg" alt="Mapa Universidad" class="imagen-mapa">

    <?php if (!empty($aulas)): ?>
        <?php foreach ($aulas as $aula): ?>
            
            <div class="pin-contenedor" 
                 style="left: <?= $aula['coord_x'] ?>%; top: <?= $aula['coord_y'] ?>%;"
                 onclick="abrirModal('<?= $aula['alias_pdf'] ?>')">
                 
                 <img id="luz_aula_<?= str_replace(' ', '_', $aula['alias_pdf']) ?>" 
                      class="luz-estado" 
                      src="/img/luz_verde.png" 
                      alt="Estado del Aula">
            </div>

        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    // 1. Lógica del Modal (Lo que ya tenías)
    function abrirModal(nombreAula) {
        document.getElementById('tituloAula').innerText = 'Horario: ' + nombreAula;
        fetch('/mapa/info_aula/' + nombreAula)
            .then(res => res.json())
            .then(data => {
                let html = '<ul style="list-style: none; padding: 0;">';
                if(data.length > 0) {
                    data.forEach(c => {
                        html += `<li style="padding: 8px; border-bottom: 1px solid #eee;">
                                    <strong>${c.dia_semana}</strong>: ${c.hora_inicio} - ${c.hora_fin} | <b>${c.materia}</b>
                                 </li>`;
                    });
                } else {
                    html += '<li>No hay clases programadas.</li>';
                }
                html += '</ul>';
                document.getElementById('cuerpoHorario').innerHTML = html;
                document.getElementById('modalAula').style.display = 'block';
            });
    }

    function cerrarModal() { 
        document.getElementById('modalAula').style.display = 'none'; 
    }

    // 2. NUEVO: Lógica de actualización de luces en tiempo real
    function actualizarLucesEnVivo() {
        // Consultamos la API que creamos en el controlador
        fetch('/mapa/estado_en_vivo')
            .then(response => response.json())
            .then(estados_aulas => {
                
                // estados_aulas es un objeto como: { "Aula 33": "roja", "Lab F": "verde" }
                for (const [alias, color] of Object.entries(estados_aulas)) {
                    
                    // Reconstruimos el ID reemplazando espacios por guiones bajos (igual que en PHP)
                    let idSeguro = alias.replace(/\s+/g, '_');
                    let imagenLuz = document.getElementById(`luz_aula_${idSeguro}`);
                    
                    // Si encontramos la imagen en el mapa, le cambiamos la ruta a la foto correspondiente
                    if (imagenLuz) {
                        imagenLuz.src = `/img/luz_${color}.png`;
                    }
                }
            })
            .catch(error => console.error('Error sincronizando las luces:', error));
    }

    // Ejecutamos la función inmediatamente al cargar la página para pintar las luces correctas
    document.addEventListener("DOMContentLoaded", function() {
        actualizarLucesEnVivo();
        // Configuramos para que se repita automáticamente cada 60 segundos (60000 ms)
        setInterval(actualizarLucesEnVivo, 60000);
    });
</script>

<style>
    .contenedor-mapa {
        position: relative; 
        width: 100%;
        max-width: 800px; 
        margin: auto;
    }
    
    .imagen-mapa {
        width: 100%;
        height: auto;
        display: block;
    }

    /* Contenedor invisible que se posiciona en las coordenadas exactas */
    .pin-contenedor {
        position: absolute;
        width: 30px; /* Ajusta este tamaño dependiendo de qué tan grande quieras la luz */
        height: 30px;
        transform: translate(-50%, -50%); 
        cursor: pointer;
        z-index: 10;
    }

    /* La imagen PNG del brillo */
    .luz-estado {
        width: 100%;
        height: 100%;
        object-fit: contain;
        animation: latido 1.5s infinite ease-in-out;
    }

    @keyframes latido {
        0% { transform: scale(1); opacity: 0.8; }
        50% { transform: scale(1.2); opacity: 1; }
        100% { transform: scale(1); opacity: 0.8; }
    }

    /* Estilo del modal mejorado */
    .modal-info { 
        position:fixed; top:0; left:0; width:100%; height:100%; 
        background:rgba(0,0,0,0.6); z-index:1000; 
    }
    .modal-contenido { 
        background:white; margin:10% auto; padding:25px; 
        width:90%; max-width: 500px; border-radius:8px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
</style>