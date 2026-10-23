<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa UMC - LAY-8 MAP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/global.css') ?>">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .barra-mapa {
            background-color: #1e3a8a;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .barra-mapa a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .barra-mapa a:hover {
            opacity: 0.85;
        }
        .contenedor-mapa {
            position: relative; 
            width: 100%;
            max-width: 900px; 
            margin: 30px auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            background: white;
        }
        .imagen-mapa {
            width: 100%;
            height: auto;
            display: block;
        }
        .pin-contenedor {
            position: absolute;
            width: 32px;
            height: 32px;
            transform: translate(-50%, -50%); 
            cursor: pointer;
            z-index: 10;
        }
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
        .modal-info { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.6); z-index: 1000; 
        }
        .modal-contenido { 
            background: white; margin: 10% auto; padding: 25px; 
            width: 90%; max-width: 500px; border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .leyenda-mapa {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            font-size: 13px;
            flex-wrap: wrap;
        }
        .item-leyenda {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .punto-luz {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="barra-mapa">
    <a href="<?= base_url('dashboard') ?>">
        <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
    </a>
    <span style="font-weight: 700; font-size: 18px;">MAPA INTERACTIVO UMC</span>
    <a href="<?= base_url('logout') ?>">
        <i class="fa-solid fa-right-from-bracket"></i> Salir
    </a>
</div>

<div class="leyenda-mapa">
    <div class="item-leyenda"><span class="punto-luz" style="background: #22c55e;"></span> Disponible</div>
    <div class="item-leyenda"><span class="punto-luz" style="background: #eab308;"></span> Clase pr√≥xima (15 min)</div>
    <div class="item-leyenda"><span class="punto-luz" style="background: #ef4444;"></span> En clase (Ocupada)</div>
    <div class="item-leyenda"><span class="punto-luz" style="background: #3b82f6;"></span> Fuera de servicio</div>
</div>

<div id="modalAula" class="modal-info" style="display:none;">
    <div class="modal-contenido">
        <span onclick="cerrarModal()" style="cursor:pointer; float:right; font-size: 24px;">&times;</span>
        <h2 id="tituloAula" style="margin-top: 0; color: #1e3a8a;">Horario</h2>
        <div id="cuerpoHorario" style="margin-top: 15px;"></div>
    </div>
</div>

<div class="contenedor-mapa">
    <img src="<?= base_url('img/mapa_umc.jpg') ?>" alt="Mapa Universidad" class="imagen-mapa">

    <?php if (!empty($aulas)): ?>
        <?php foreach ($aulas as $aula): ?>
            
            <div class="pin-contenedor" 
                 style="left: <?= $aula['coord_x'] ?>%; top: <?= $aula['coord_y'] ?>%;"
                 onclick="abrirModal('<?= esc($aula['alias_pdf']) ?>')">
                 
                 <img id="luz_aula_<?= str_replace(' ', '_', $aula['alias_pdf']) ?>" 
                      class="luz-estado" 
                      src="<?= base_url('img/' . ($aula['esta_ocupado'] ? 'luz_roja.png' : 'luz_verde.png')) ?>" 
                      alt="Estado del Aula">
            </div>

        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    const BASE_URL = '<?= base_url() ?>';

    function abrirModal(nombreAula) {
        document.getElementById('tituloAula').innerText = 'Horario: ' + nombreAula;
        document.getElementById('cuerpoHorario').innerHTML = '<p>Cargando informaci√≥n...</p>';
        document.getElementById('modalAula').style.display = 'block';

        fetch(BASE_URL + "mapa/info_aula/" + encodeURIComponent(nombreAula))
            .then(res => res.json())
            .then(data => {
                let html = "";

                // 1. Banner de clase activa en este momento
                if (data.hay_clase_activa && data.clase_activa) {
                    const c = data.clase_activa;
                    html += `<div style="background:#fee2e2; border-left:4px solid #ef4444; padding:12px; border-radius:6px; margin-bottom:15px;">
                                <strong style="color:#991b1b; display:block; font-size:14px;">?? CLASE ACTIVA EN ESTE MOMENTO</strong>
                                <span style="font-weight:700; color:#1e293b; font-size:15px;">${c.materia} (Secc. ${c.seccion})</span><br>
                                <span style="color:#475569; font-size:13px;">Intervalo: <b>${c.intervalo}</b> (${c.duracion}) &bull; Quedan ~${c.minutos_restantes} min</span>
                             </div>`;
                } else if (data.proxima_clase_hoy) {
                    const p = data.proxima_clase_hoy;
                    html += `<div style="background:#fef9c3; border-left:4px solid #eab308; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#854d0e;">
                                ?? <b>PrÛxima clase hoy:</b> ${p.materia} (${p.intervalo}) &bull; Inicia en ~${p.inicia_en_minutos} min
                             </div>`;
                }

                // 2. Horarios agrupados por dÌa en intervalos
                const dias = data.horarios_por_dia || {};
                let tieneHorarios = false;

                for (const [dia, sesiones] of Object.entries(dias)) {
                    if (sesiones && sesiones.length > 0) {
                        tieneHorarios = true;
                        html += `<div style="margin-bottom:12px;">
                                    <h4 style="margin:0 0 5px 0; color:#1e3a8a; font-size:14px; border-bottom:1px solid #e2e8f0; padding-bottom:3px;">${dia}</h4>
                                    <ul style="list-style:none; padding:0; margin:0;">`;
                        sesiones.forEach(s => {
                            const badge = s.es_activa_ahora ? ' <span style="background:#ef4444; color:white; font-size:10px; padding:2px 6px; border-radius:10px;">EN CURSO</span>' : "";
                            html += `<li style="padding:6px 0; font-size:13px; display:flex; justify-content:space-between; align-items:center;">
                                        <span><b>${s.intervalo}</b> (${s.duracion_texto}) &bull; ${s.materia} <span style="color:#64748b;">[${s.seccion}]</span></span>
                                        ${badge}
                                     </li>`;
                        });
                        html += `   </ul>
                                 </div>`;
                    }
                }

                if (!tieneHorarios) {
                    html += '<p style="color:#64748b; font-size:13px;">No hay clases programadas para esta aula.</p>';
                }

                document.getElementById("cuerpoHorario").innerHTML = html;
            })
            .catch(err => {
                document.getElementById('cuerpoHorario').innerHTML = '<p style="color:red;">Error al cargar el horario.</p>';
            });
    }

    function cerrarModal() { 
        document.getElementById('modalAula').style.display = 'none'; 
    }

    window.onclick = function(event) {
        const modal = document.getElementById('modalAula');
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }

    function actualizarLucesEnVivo() {
        fetch(BASE_URL + 'mapa/estado_en_vivo')
            .then(response => response.json())
            .then(estados_aulas => {
                for (const [alias, color] of Object.entries(estados_aulas)) {
                    let idSeguro = alias.replace(/\s+/g, '_');
                    let imagenLuz = document.getElementById(`luz_aula_${idSeguro}`);
                    if (imagenLuz) {
                        imagenLuz.src = `${BASE_URL}img/luz_${color}.png`;
                    }
                }
            })
            .catch(error => console.error('Error sincronizando las luces:', error));
    }

    document.addEventListener("DOMContentLoaded", function() {
        actualizarLucesEnVivo();
        setInterval(actualizarLucesEnVivo, 60000);
    });
</script>

</body>
</html>

