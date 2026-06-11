<?php 
/**  @var float $coord_x 
 * @var float $coord_y 
 */ 
?>
...

<div id="modalAula" class="modal-info" style="display:none;">
    <div class="modal-contenido">
        <span onclick="cerrarModal()" style="cursor:pointer; float:right;">&times;</span>
        <h2 id="tituloAula">Horario</h2>
        <div id="cuerpoHorario"></div>
    </div>
</div>



<script>
    function abrirModal(nombreAula) {
        document.getElementById('tituloAula').innerText = 'Horario: ' + nombreAula;
        fetch('/mapa/info_aula/' + nombreAula)
            .then(res => res.json())
            .then(data => {
                let html = '<ul>';
                data.forEach(c => {
                    html += `<li>${c.dia_semana}: ${c.hora_inicio} - ${c.hora_fin} | <b>${c.materia}</b></li>`;
                });
                html += '</ul>';
                document.getElementById('cuerpoHorario').innerHTML = html;
                document.getElementById('modalAula').style.display = 'block';
            });
    }

    function cerrarModal() { document.getElementById('modalAula').style.display = 'none'; }
</script>

<style>
    .contenedor-mapa {
        position: relative; /* Clave para que los pines floten dentro del mapa */
        width: 100%;
        max-width: 800px; /* Ajusta al tamaño */
        margin: auto;
    }
    
    .imagen-mapa {
        width: 100%;
        height: auto;
        display: block;
    }

    .pin-ubicacion {
        position: absolute;
        width: 20px;
        height: 20px;
        background-color: red;
        border-radius: 50%;
        border: 2px solid white;
        transform: translate(-50%, -50%); /* Centra el punto exacto en la coordenada */
        box-shadow: 0px 0px 10px rgba(0,0,0,0.5);
        animation: latido 1.5s infinite;
        
    }

    @keyframes latido {
        0% { transform: translate(-50%, -50%) scale(1); }
        50% { transform: translate(-50%, -50%) scale(1.3); }
        100% { transform: translate(-50%, -50%) scale(1); }
    }
    .punto-rojo {
        width: 10px; height: 10px; background: red;
        border-radius: 50%; position: absolute; top: -5px; right: -5px;
        display: none; /* Oculto por defecto */
    }
    .ocupado .punto-rojo { display: block; } /* Solo se ve si tiene la clase 'ocupado' */

    /* Estilo del modal */
    .modal-info { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; }
    .modal-contenido { background:white; margin:10% auto; padding:20px; width:50%; border-radius:5px; }
</style>

    <div class="contenedor-mapa">
        <img src="/img/mapa_umc.jpg" alt="Mapa Universidad" class="imagen-mapa">

        <?php if (!empty($aulas)): ?>
            <?php foreach ($aulas as $aula): ?>
                <div class="pin-ubicacion <?= $aula['esta_ocupado'] ? 'ocupado' : '' ?>" 
                    style="left: <?= $aula['coord_x'] ?>%; top: <?= $aula['coord_y'] ?>%;"
                    onclick="abrirModal('<?= $aula['alias_pdf'] ?>')">
                    
                    <?php if ($aula['esta_ocupado']): ?>
                        <div class="punto-rojo"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>