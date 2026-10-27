<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones de Uso - LAY-8</title>

    <!-- Script anti-flicker de Modo Oscuro -->
    <script>
        (function() {
            try {
                var tema = localStorage.getItem('lay8_theme');
                if (tema === 'dark') {
                    document.documentElement.classList.add('dark-mode');
                }
            } catch(e) {}
        })();
    </script>

    <!-- Fuentes oficiales del proyecto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600&family=Jersey+25&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Iconografía Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/global.css') ?>">

    <style>
        :root {
            --primary: #1e3a8a;
            --primary-hover: #172554;
            --accent: #3b82f6;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
        }

        body {
            font-family: 'Hind', sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            line-height: 1.65;
        }

        h1, h2, h3, h4 {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary);
        }

        /* Barra de navegación superior */
        .terminos-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .terminos-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--primary);
            font-family: var(--fuente-logo);
            font-size: 1.8rem;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .terminos-logo span {
            font-family: var(--fuente-logo);
            font-size: 1.8rem;
            font-weight: 400;
        }

        .terminos-logo img {
            width: 38px;
            height: auto;
        }

        .terminos-nav-acciones {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-nav-terminos {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-regresar-home {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .btn-regresar-home:hover {
            background: #e2e8f0;
            color: var(--primary);
        }

        .btn-crear-cuenta-cta {
            background: var(--primary);
            color: #ffffff;
            border: 1px solid var(--primary);
        }

        .btn-crear-cuenta-cta:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-imprimir {
            background: transparent;
            color: #64748b;
            border: 1px solid #cbd5e1;
            cursor: pointer;
        }

        .btn-imprimir:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        /* Contenedor Principal */
        .terminos-layout {
            max-width: 900px;
            margin: 40px auto 60px;
            padding: 0 20px;
        }

        .terminos-documento {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 45px 50px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        .terminos-header {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 25px;
            margin-bottom: 35px;
            text-align: center;
        }

        .insignia-legal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 15px;
            border: 1px solid #bfdbfe;
        }

        .terminos-header h1 {
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.3;
            margin: 0 0 10px 0;
            color: #0f172a;
        }

        .terminos-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0;
        }

        .fecha-actualizacion {
            font-size: 0.82rem;
            color: #94a3b8;
            margin-top: 8px;
            display: block;
        }

        /* Índice rápido */
        .indice-rapido {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 35px;
        }

        .indice-rapido h4 {
            margin: 0 0 12px 0;
            font-size: 0.92rem;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .lista-indice {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 8px 16px;
        }

        .lista-indice a {
            color: #2563eb;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: color 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .lista-indice a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        /* Secciones del documento */
        .seccion-termino {
            margin-bottom: 35px;
        }

        .seccion-termino h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 14px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
        }

        .numero-seccion {
            background: var(--primary);
            color: #ffffff;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .seccion-termino p {
            margin: 0 0 12px 0;
            font-size: 0.95rem;
            color: #334155;
            text-align: justify;
        }

        .seccion-termino ul {
            margin: 8px 0 16px 20px;
            padding: 0;
            color: #334155;
            font-size: 0.93rem;
        }

        .seccion-termino li {
            margin-bottom: 8px;
        }

        /* Cajas de alerta y advertencias legales */
        .caja-alerta-legal {
            border-radius: 10px;
            padding: 16px 18px;
            margin: 18px 0;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .caja-alerta-legal i {
            font-size: 1.4rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alerta-disclaimer {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .alerta-disclaimer i {
            color: #d97706;
        }

        .alerta-fraude {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alerta-fraude i {
            color: #dc2626;
        }

        .alerta-privacidad {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alerta-privacidad i {
            color: #059669;
        }

        /* Pie de página institucional */
        .terminos-footer {
            border-top: 1px solid var(--border-color);
            margin-top: 40px;
            padding-top: 25px;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .terminos-footer p {
            margin: 4px 0;
        }

        /* Adaptaciones Modo Oscuro */
        body.dark-mode {
            --bg-page: #0b1120;
            --bg-card: #0f172a;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --primary: #60a5fa;
            --primary-hover: #93c5fd;
        }

        body.dark-mode .terminos-navbar {
            background: #0f172a;
            border-bottom-color: #334155;
        }

        body.dark-mode .terminos-logo span {
            color: #f8fafc;
        }

        body.dark-mode .terminos-documento {
            background: #0f172a;
            border-color: #334155;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }

        body.dark-mode .terminos-header {
            border-bottom-color: #1e293b;
        }

        body.dark-mode .terminos-header h1 {
            color: #f8fafc;
        }

        body.dark-mode .insignia-legal {
            background: #1e293b;
            color: #93c5fd;
            border-color: #3b82f6;
        }

        body.dark-mode .indice-rapido {
            background: #1e293b;
            border-color: #334155;
        }

        body.dark-mode .indice-rapido h4 {
            color: #f1f5f9;
        }

        body.dark-mode .lista-indice a {
            color: #60a5fa;
        }

        body.dark-mode .seccion-termino p,
        body.dark-mode .seccion-termino li {
            color: #cbd5e1;
        }

        body.dark-mode .numero-seccion {
            background: #2563eb;
            color: #ffffff;
        }

        body.dark-mode .btn-regresar-home {
            background: #1e293b;
            color: #cbd5e1;
            border-color: #334155;
        }

        body.dark-mode .btn-regresar-home:hover {
            background: #334155;
            color: #f8fafc;
        }

        body.dark-mode .alerta-disclaimer {
            background: #451a03;
            border-color: #78350f;
            color: #fde68a;
        }

        body.dark-mode .alerta-fraude {
            background: #450a0a;
            border-color: #7f1d1d;
            color: #fecaca;
        }

        body.dark-mode .alerta-privacidad {
            background: #064e3b;
            border-color: #065f46;
            color: #a7f3d0;
        }

        /* Impresión */
        @media print {
            .terminos-navbar,
            .indice-rapido,
            .btn-imprimir {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .terminos-layout {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .terminos-documento {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }

        @media (max-width: 768px) {
            .terminos-documento {
                padding: 30px 20px;
            }

            .terminos-header h1 {
                font-size: 1.45rem;
            }

            .terminos-nav-acciones .btn-crear-cuenta-cta {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Navegación Superior -->
    <header class="terminos-navbar">
        <a href="<?= base_url('/') ?>" class="terminos-logo">
            <img src="<?= base_url('img/lay8_logo.png') ?>" alt="Logo LAY-8">
            <span>LAY-8</span>
        </a>

        <div class="terminos-nav-acciones">
            <button type="button" onclick="window.print()" class="btn-nav-terminos btn-imprimir" title="Imprimir copia">
                <i class="bx bx-printer"></i> Imprimir
            </button>
            <a href="<?= base_url('/') ?>" class="btn-nav-terminos btn-regresar-home">
                <i class="bx bx-arrow-back"></i> Volver al Inicio
            </a>
            <a href="<?= base_url('registro') ?>" class="btn-nav-terminos btn-crear-cuenta-cta">
                <i class="bx bx-user-plus"></i> Crear Cuenta
            </a>
        </div>
    </header>

    <main class="terminos-layout">
        <article class="terminos-documento">
            
            <header class="terminos-header">
                <span class="insignia-legal">
                    <i class="bx bx-shield-quarter"></i> Marco Normativo Institucional
                </span>
                <h1>Términos, Condiciones de Uso y Política de Privacidad</h1>
                <p>Plataforma Académica Digital <b>LAY-8</b> &bull; Universidad Nacional Experimental Marítima del Caribe (UMC)</p>
                <span class="fecha-actualizacion">Vigente a partir del 26 de octubre de 2026 &bull; Catia La Mar, Venezuela</span>
            </header>

            <!-- Índice de Navegación Rápida -->
            <nav class="indice-rapido">
                <h4><i class="bx bx-list-ul"></i> Contenido del Documento</h4>
                <ul class="lista-indice">
                    <li><a href="#sec-1"><i class="bx bx-chevron-right"></i> 1. Naturaleza y Aceptación</a></li>
                    <li><a href="#sec-2"><i class="bx bx-chevron-right"></i> 2. Deslinde Institucional UMC</a></li>
                    <li><a href="#sec-3"><i class="bx bx-chevron-right"></i> 3. Cero Tolerancia al Fraude</a></li>
                    <li><a href="#sec-4"><i class="bx bx-chevron-right"></i> 4. Privacidad y Datos Personales</a></li>
                    <li><a href="#sec-5"><i class="bx bx-chevron-right"></i> 5. Biblioteca y Propiedad Intelectual</a></li>
                    <li><a href="#sec-6"><i class="bx bx-chevron-right"></i> 6. Aulas y Código de Conducta</a></li>
                    <li><a href="#sec-7"><i class="bx bx-chevron-right"></i> 7. Régimen Sancionatorio y Baneo</a></li>
                    <li><a href="#sec-8"><i class="bx bx-chevron-right"></i> 8. Legislación y Jurisdicción</a></li>
                </ul>
            </nav>

            <!-- Sección 1 -->
            <section id="sec-1" class="seccion-termino">
                <h2><span class="numero-seccion">1</span> Naturaleza del Servicio y Aceptación Vinculante</h2>
                <p>
                    El presente instrumento constituye un acuerdo legal vinculante entre el usuario (estudiante, docente, investigador o personal administrativo) y los desarrolladores y administradores del ecosistema <b>LAY-8</b> (en adelante, <i>«La Plataforma»</i>).
                </p>
                <p>
                    El registro de una cuenta, la carga de credenciales, el acceso a las aulas virtuales, el uso de la biblioteca o la consulta del mapa interactivo implica la <b>aceptación irrevocable y plena de la totalidad de las cláusulas</b> establecidas en este documento. Si el usuario no estuviere conforme con los términos presentes, deberá abstenerse de crear cuenta o utilizar los servicios de LAY-8.
                </p>
            </section>

            <!-- Sección 2 -->
            <section id="sec-2" class="seccion-termino">
                <h2><span class="numero-seccion">2</span> Deslinde de Responsabilidad Institucional (Disclaimer UMC)</h2>
                <p>
                    <b>LAY-8</b> es un proyecto de software colaborativo concebido para modernizar la orientación física en el campus, la gestión de horarios y el apoyo académico de la comunidad universitaria. En consecuencia:
                </p>
                <ul>
                    <li><b>Carácter No Oficial Sustitutivo:</b> LAY-8 no reemplaza bajo ninguna circunstancia a los sistemas administrativos centrales de la UMC (<i>campus.umc.edu.ve</i>), ni a las publicaciones oficiales de Secretaría General o Vicerrectorado Académico. Las decisiones y actas institucionales prevalecen sobre cualquier dato mostrado en esta plataforma.</li>
                    <li><b>Semáforo de Aulas y Horarios en Vivo:</b> Los indicadores de disponibilidad (verde, amarillo, rojo o azul) y las aulas físicas mostradas poseen carácter <b>estrictamente referencial e informativo</b>. LAY-8 no asume responsabilidad civil, académica ni disciplinaria por cambios imprevistos de aula ordenados por coordinaciones docentes, inasistencias docentes no notificadas, o evaluaciones no rendidas.</li>
                    <li><b>Continuidad y Disponibilidad Técnica:</b> El servicio se suministra <i>«tal cual está»</i>. No se garantiza la disponibilidad permanente ni la infalibilidad del sistema frente a interrupciones derivadas del fluido eléctrico, conectividad a Internet o labores de mantenimiento técnico en los servidores.</li>
                </ul>

                <div class="caja-alerta-legal alerta-disclaimer">
                    <i class="bx bx-info-circle"></i>
                    <div>
                        <b>Aviso Informativo Importante:</b> Siempre consulta los canales físicos y memorandos oficiales de tu coordinación de carrera para trámites de retiro de asignaturas, actas de notas definitivas y calendarios de exámenes finales.
                    </div>
                </div>
            </section>

            <!-- Sección 3 -->
            <section id="sec-3" class="seccion-termino">
                <h2><span class="numero-seccion">3</span> Autenticidad Documental y Cero Tolerancia al Fraude</h2>
                <p>
                    El sistema cuenta con un motor inteligente de verificación que procesa comprobantes emitidos por la universidad (Formulario de Registro de Inscripción UPE-002, Constancias de Estudio y Carnets Institucionales). Al suministrar un documento a la plataforma:
                </p>
                <ul>
                    <li>El usuario certifica bajo fe de juramento que el archivo adjuntado es un documento legítimo, emitido a su nombre y sin alteraciones en texto, códigos de barras, sellos o firmas.</li>
                    <li>Queda terminantemente prohibida la manipulación digital de planillas para inscribir asignaturas no cursadas o suplantar la identidad de otros estudiantes o docentes.</li>
                </ul>

                <div class="caja-alerta-legal alerta-fraude">
                    <i class="bx bx-error-alt"></i>
                    <div>
                        <b>Sanción Inmediata por Forjamiento:</b> Cualquier intento deliberado de inyectar documentos falsificados o manipulados acarreará el <b>bloqueo permanente e irreversible de la cuenta</b>, así como la notificación correspondiente a las autoridades universitarias y judiciales competentes bajo la <i>Ley Especial contra Delitos Informáticos</i> de Venezuela.
                    </div>
                </div>
            </section>

            <!-- Sección 4 -->
            <section id="sec-4" class="seccion-termino">
                <h2><span class="numero-seccion">4</span> Privacidad y Tratamiento Ético de Datos Personales</h2>
                <p>
                    LAY-8 respeta el derecho a la privacidad y al honor de todos los integrantes del campus UMC:
                </p>
                <ul>
                    <li><b>Alcance de los Datos Recabados:</b> Únicamente se almacenan los datos indispensables para el funcionamiento del perfil: Nombres, Cédula de Identidad, Código de Estudiante, Correo Electrónico, Carrera Universitaria (<i>ING-INF, ING-MAR, ING-AMB, ADM o TUR</i>), asignaturas inscritas y foto de perfil voluntaria.</li>
                    <li><b>No Comercialización:</b> Los datos académicos y personales recolectados jamás serán vendidos, cedidos, comercializados ni compartidos con empresas de publicidad o entidades externas a la comunidad universitaria.</li>
                    <li><b>Cifrado de Credenciales:</b> Las contraseñas se almacenan mediante algoritmos criptográficos unidireccionales (*hashing* irreversible). Ningún administrador ni tercero tiene acceso a visualizar tu contraseña en texto plano.</li>
                </ul>

                <div class="caja-alerta-legal alerta-privacidad">
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <b>Protección de Documentos:</b> Las planillas de inscripción se procesan mediante análisis en memoria para extraer los metadatos necesarios y se descartan de forma segura para resguardar la privacidad de tu registro académico.
                    </div>
                </div>
            </section>

            <!-- Sección 5 -->
            <section id="sec-5" class="seccion-termino">
                <h2><span class="numero-seccion">5</span> Propiedad Intelectual y Uso de la Biblioteca Estudiantil</h2>
                <p>
                    El módulo de <b>Biblioteca</b> fomenta el aprendizaje colaborativo. Todo material subido debe ajustarse estrictamente a las siguientes normas:
                </p>
                <ul>
                    <li><b>Material Permitido:</b> Guías de estudio elaboradas por estudiantes, ejercicios resueltos, esquemas, resúmenes de clase y presentaciones académicas propias.</li>
                    <li><b>Prohibición Expresa de Piratería:</b> Queda prohibido alojar o distribuir libros comerciales protegidos por derechos de autor (*copyright*) sin la debida licencia del titular, así como software pirata o material sujeto a patentes comerciales.</li>
                    <li><b>Integridad Académica:</b> Queda estrictamente vedada la filtración no autorizada de exámenes vigentes, pruebas en curso o mecanismos de fraude en evaluaciones institucionales.</li>
                    <li><b>Derecho de Retiro Inmediato:</b> La administración se reserva el derecho de eliminar cualquier archivo reportado o que vulnere la legislación de propiedad intelectual sin necesidad de previo aviso.</li>
                </ul>
            </section>

            <!-- Sección 6 -->
            <section id="sec-6" class="seccion-termino">
                <h2><span class="numero-seccion">6</span> Aulas Virtuales, Enlaces y Código de Conducta</h2>
                <p>
                    El módulo de <b>Aulas</b> permite interactuar con asignaturas, cronogramas de tareas y sesiones virtuales:
                </p>
                <ul>
                    <li><b>Responsabilidad Docente en Salas Virtuales:</b> Los usuarios con rol de Profesor acreditado son los únicos responsables de los enlaces a salas remotas (Google Meet, Zoom, Teams o Jitsi) que publiquen para sus secciones. Dichos enlaces deben destinarse exclusivamente a fines pedagógicos.</li>
                    <li><b>Normas de Convivencia:</b> Se prohíbe el uso de lenguaje soez, hostigamiento, discriminación, acoso (*cyberbullying*) o difusión de enlaces maliciosos (*phishing*) a través de los títulos de tareas, avisos de clase o descripciones de actividades.</li>
                </ul>
            </section>

            <!-- Sección 7 -->
            <section id="sec-7" class="seccion-termino">
                <h2><span class="numero-seccion">7</span> Régimen Sancionatorio, Auditoría y Baneo de Cuentas</h2>
                <p>
                    Con el fin de preservar la seguridad de la comunidad, la plataforma implementa un registro de auditoría administrativa forense (<i>auditoria_admin</i>):
                </p>
                <ul>
                    <li>El equipo administrador podrá suspender preventivamente o banear de forma indefinida cualquier cuenta que cometa actos de desacato a estas normas, suplantación de identidad o ataques informáticos (fuerza bruta, inyecciones de código, escaneo de vulnerabilidades).</li>
                    <li>Un usuario cuya cuenta sea suspendida no podrá volver a registrarse utilizando el mismo número de cédula institucional.</li>
                </ul>
            </section>

            <!-- Sección 8 -->
            <section id="sec-8" class="seccion-termino">
                <h2><span class="numero-seccion">8</span> Legislación Aplicable y Resolución de Controversias</h2>
                <p>
                    Los presentes Términos se rigen bajo el ordenamiento jurídico de la <b>República Bolivariana de Venezuela</b>, en concordancia con la <i>Ley Orgánica de Educación</i>, el <i>Reglamento Disciplinario Estudiantil de la UMC</i> y la <i>Ley Especial contra los Delitos Informáticos</i>.
                </p>
                <p>
                    Cualquier controversia que no pudiere resolverse de mutuo acuerdo entre las partes será remitida a las instancias institucionales universitarias competentes o a los tribunales ordinarios de la jurisdicción del Estado La Guaira.
                </p>
            </section>

            <footer class="terminos-footer">
                <p><b>LAY-8 &bull; Innovación y Tecnología para la Comunidad UMC</b></p>
                <p>Desarrollado con fines académicos por estudiantes de Ingeniería en Informática de la Universidad Marítima del Caribe.</p>
                <p style="margin-top: 10px; font-size: 0.8rem; color: #94a3b8;">&copy; <?= date('Y') ?> LAY-8. Todos los derechos reservados.</p>
            </footer>

        </article>
    </main>

</body>
</html>
