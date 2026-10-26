<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="mapa-dashboard-contenedor">
    <div class="mapa-header-box">
        <div>
            <h2><i class="bx bx-map-pin"></i> Mapa Interactivo UMC</h2>
            <p>Monitoreo en tiempo real de la disponibilidad y programación académica de cada aula y laboratorio del campus.</p>
        </div>
    </div>

    <!-- Leyenda de Estados del Semáforo -->
    <div class="leyenda-mapa">
        <div class="item-leyenda"><span class="punto-luz luz-verde"></span> Disponible</div>
        <div class="item-leyenda"><span class="punto-luz luz-amarilla"></span> Próxima clase (15 min)</div>
        <div class="item-leyenda"><span class="punto-luz luz-roja"></span> En clase (Ocupada)</div>
        <div class="item-leyenda"><span class="punto-luz luz-azul"></span> Fuera de servicio</div>
    </div>

    <!-- Contenedor del Mapa con Coordenadas Interactivas -->
    <div class="contenedor-mapa">
        <img src="<?= base_url('img/mapa_umc.jpg') ?>" alt="Mapa Universidad Marítima del Caribe" class="imagen-mapa">

        <?php if (!empty($aulas)): ?>
            <?php foreach ($aulas as $aula): ?>
                <div class="pin-contenedor" 
                     style="left: <?= $aula['coord_x'] ?>%; top: <?= $aula['coord_y'] ?>%;"
                     onclick="abrirModalAula('<?= esc($aula['alias_pdf']) ?>')"
                     title="Ver horario de <?= esc($aula['alias_pdf']) ?>">
                     
                     <img id="luz_aula_<?= str_replace(' ', '_', $aula['alias_pdf']) ?>" 
                          class="luz-estado" 
                          src="<?= base_url('img/' . ($aula['esta_ocupado'] ? 'luz_roja.png' : 'luz_verde.png')) ?>" 
                          alt="Estado del Aula">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de Información y Horario del Aula -->
    <div id="modalAula" class="modal-info-aula" style="display:none;" onclick="if(event.target === this) cerrarModalAula()">
        <div class="modal-contenido-aula">
            <div class="modal-aula-header">
                <h3 id="tituloAula"><i class="bx bx-calendar"></i> Horario del Aula</h3>
                <span onclick="cerrarModalAula()" class="btn-cerrar-modal-aula" title="Cerrar">&times;</span>
            </div>
            <div id="cuerpoHorario" class="modal-aula-body">
                <p>Cargando información...</p>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?= base_url() ?>';

    function abrirModalAula(nombreAula) {
        document.getElementById('tituloAula').innerHTML = '<i class="bx bx-calendar"></i> Aula: ' + nombreAula;
        document.getElementById('cuerpoHorario').innerHTML = '<p style="color:#64748b; padding:15px 0; text-align:center;"><i class="bx bx-loader-alt bx-spin" style="font-size:1.5rem;"></i><br>Cargando programación...</p>';
        document.getElementById('modalAula').style.display = 'flex';

        fetch(BASE_URL + "mapa/info_aula/" + encodeURIComponent(nombreAula))
            .then(res => res.json())
            .then(data => {
                let html = "";

                // 1. Banner de clase activa en este momento
                if (data.hay_clase_activa && data.clase_activa) {
                    const c = data.clase_activa;
                    html += `<div class="banner-clase-activa">
                                <div class="banner-estado-titulo"><i class="bx bx-radio-circle-marked"></i> CLASE EN CURSO AHORA</div>
                                <div class="banner-materia-nombre">${c.materia} <span class="seccion-tag">[Secc. ${c.seccion}]</span></div>
                                <div class="banner-tiempo-detalle">Intervalo: <b>${c.intervalo}</b> (${c.duracion}) &bull; Quedan ~${c.minutos_restantes} min</div>
                             </div>`;
                } else if (data.proxima_clase_hoy) {
                    const p = data.proxima_clase_hoy;
                    html += `<div class="banner-clase-proxima">
                                <div class="banner-estado-titulo"><i class="bx bx-time"></i> Próxima clase hoy:</div>
                                <div class="banner-materia-nombre">${p.materia} <span class="seccion-tag">[Secc. ${p.seccion}]</span></div>
                                <div class="banner-tiempo-detalle">Intervalo: <b>${p.intervalo}</b> &bull; Inicia en ~${p.inicia_en_minutos} min</div>
                             </div>`;
                }

                // 2. Horarios agrupados por día en intervalos
                const dias = data.horarios_por_dia || {};
                let tieneHorarios = false;

                for (const [dia, sesiones] of Object.entries(dias)) {
                    if (sesiones && sesiones.length > 0) {
                        tieneHorarios = true;
                        html += `<div class="dia-horario-bloque">
                                    <h4>${dia}</h4>
                                    <ul class="lista-sesiones">`;
                        sesiones.forEach(s => {
                            const badge = s.es_activa_ahora ? ' <span class="badge-en-curso">EN CURSO</span>' : "";
                            html += `<li>
                                        <span><b>${s.intervalo}</b> (${s.duracion_texto}) &bull; ${s.materia} <span class="sub-seccion">[${s.seccion}]</span></span>
                                        ${badge}
                                     </li>`;
                        });
                        html +=    `</ul>
                                 </div>`;
                    }
                }

                if (!tieneHorarios) {
                    html += '<div class="sin-horarios-msg"><i class="bx bx-calendar-x" style="font-size:1.8rem; display:block; margin-bottom:6px;"></i>No hay clases programadas para esta aula.</div>';
                }

                document.getElementById("cuerpoHorario").innerHTML = html;
            })
            .catch(err => {
                document.getElementById('cuerpoHorario').innerHTML = '<p style="color:#ef4444; padding:15px 0; text-align:center;">Error al consultar el horario del aula.</p>';
            });
    }

    function cerrarModalAula() { 
        document.getElementById('modalAula').style.display = 'none'; 
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
<?= $this->endSection() ?>
