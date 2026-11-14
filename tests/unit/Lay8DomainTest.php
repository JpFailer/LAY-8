<?php

use App\Models\ActividadClaseModel;
use App\Models\AuditoriaAdminModel;
use App\Models\UsuarioModel;
use App\Services\ActividadService;
use App\Services\AdminGestionService;
use App\Services\BibliotecaService;
use App\Services\DocentePanelService;
use App\Services\HorarioSyncService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pruebas unitarias y de integración aisladas sobre SQLite :memory: para servicios y modelos de LAY-8.
 * No lee ni modifica la base de datos MySQL real.
 *
 * @internal
 */
final class Lay8DomainTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        $this->db->setPrefix('');
        $this->crearEsquemaTemporal();
    }

    private function crearEsquemaTemporal(): void
    {
        $p = $this->db->getPrefix();
        $ddl = [
            "DROP TABLE IF EXISTS {$p}solicitudes_extemporaneas",
            "DROP TABLE IF EXISTS {$p}notificaciones",
            "DROP TABLE IF EXISTS {$p}noticias_comentarios",
            "DROP TABLE IF EXISTS {$p}noticias_muro",
            "DROP TABLE IF EXISTS {$p}estudiante_bloques_excluidos",
            "DROP TABLE IF EXISTS {$p}entregas_actividades",
            "DROP TABLE IF EXISTS {$p}actividades_estudiante_estado",
            "DROP TABLE IF EXISTS {$p}actividades_clase",
            "DROP TABLE IF EXISTS {$p}estados_clase_profesor",
            "DROP TABLE IF EXISTS {$p}clases_programadas",
            "DROP TABLE IF EXISTS {$p}inscripciones",
            "DROP TABLE IF EXISTS {$p}secciones",
            "DROP TABLE IF EXISTS {$p}materias",
            "DROP TABLE IF EXISTS {$p}aportes_biblioteca",
            "DROP TABLE IF EXISTS {$p}auditoria_admin",
            "DROP TABLE IF EXISTS {$p}aulas",
            "DROP TABLE IF EXISTS {$p}usuarios",
            "CREATE TABLE {$p}usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cedula VARCHAR(20),
                primer_nombre VARCHAR(100),
                segundo_nombre VARCHAR(100),
                apellidos VARCHAR(120),
                nombre_usuario VARCHAR(80),
                correo VARCHAR(150),
                telefono VARCHAR(30),
                rol VARCHAR(30) DEFAULT 'estudiante',
                carrera VARCHAR(30) DEFAULT 'ING-INF',
                estado_verificacion VARCHAR(30) DEFAULT 'aprobado',
                ruta_carnet VARCHAR(255),
                estado_cuenta VARCHAR(30) DEFAULT 'activo',
                motivo_baneo TEXT,
                baneado_en DATETIME,
                fecha_registro DATETIME,
                ultimo_acceso DATETIME,
                foto_perfil VARCHAR(255)
            )",
            "CREATE TABLE {$p}materias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo_materia VARCHAR(30),
                nombre VARCHAR(150),
                unidades_credito INTEGER DEFAULT 0,
                semestre_trayecto INTEGER DEFAULT 1
            )",
            "CREATE TABLE {$p}secciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                materia_id INTEGER NOT NULL,
                codigo_seccion VARCHAR(20),
                periodo_academico VARCHAR(20) DEFAULT '2026-I',
                carrera VARCHAR(30) DEFAULT 'ING-INF',
                profesor_id INTEGER
            )",
            "CREATE TABLE {$p}inscripciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                estudiante_id INTEGER NOT NULL,
                seccion_id INTEGER NOT NULL
            )",
            "CREATE TABLE {$p}aportes_biblioteca (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER,
                titulo VARCHAR(200),
                categoria VARCHAR(50),
                carrera VARCHAR(30),
                nombre_original VARCHAR(255),
                nombre_guardado VARCHAR(255),
                ruta_archivo VARCHAR(255),
                tamano_bytes INTEGER DEFAULT 1024,
                extension VARCHAR(20),
                fecha_subida DATETIME
            )",
            "CREATE TABLE {$p}actividades_clase (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                seccion_id INTEGER NOT NULL,
                creado_por INTEGER NOT NULL,
                es_personal INTEGER DEFAULT 0,
                titulo VARCHAR(200),
                descripcion TEXT,
                tipo VARCHAR(40),
                fecha_entrega DATETIME,
                ponderacion REAL DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )",
            "CREATE TABLE {$p}actividades_estudiante_estado (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actividad_id INTEGER NOT NULL,
                estudiante_id INTEGER NOT NULL,
                completada INTEGER DEFAULT 0,
                fecha_completada DATETIME
            )",
            "CREATE TABLE {$p}entregas_actividades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actividad_id INTEGER NOT NULL,
                estudiante_id INTEGER NOT NULL,
                archivo_ruta VARCHAR(255),
                archivo_nombre VARCHAR(255),
                comentario_alumno TEXT,
                calificacion REAL,
                feedback_profesor TEXT,
                calificado_por INTEGER,
                calificado_en DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )",
            "CREATE TABLE {$p}solicitudes_extemporaneas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                estudiante_id INTEGER NOT NULL,
                seccion_id INTEGER NOT NULL,
                profesor_id INTEGER,
                estado VARCHAR(30) DEFAULT 'pendiente',
                motivo_rechazo VARCHAR(255),
                created_at DATETIME,
                updated_at DATETIME
            )",
            "CREATE TABLE {$p}notificaciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                estudiante_id INTEGER,
                profesor_id INTEGER,
                seccion_id INTEGER,
                tipo VARCHAR(50),
                titulo VARCHAR(200),
                mensaje TEXT,
                modalidad VARCHAR(30),
                enlace_virtual VARCHAR(255),
                enlace_accion VARCHAR(255),
                leido INTEGER DEFAULT 0,
                created_at DATETIME
            )",
            "CREATE TABLE {$p}auditoria_admin (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER,
                accion VARCHAR(80),
                entidad VARCHAR(80),
                entidad_id INTEGER,
                detalles TEXT,
                created_at DATETIME
            )",
            "CREATE TABLE {$p}aulas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_json VARCHAR(150),
                alias_pdf VARCHAR(100),
                coord_x REAL DEFAULT 0,
                coord_y REAL DEFAULT 0,
                estado VARCHAR(40) DEFAULT 'disponible',
                nota_estado TEXT
            )",
            "CREATE TABLE {$p}clases_programadas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                seccion_id INTEGER,
                estudiante_id INTEGER,
                aula VARCHAR(100),
                carrera VARCHAR(30),
                dia_semana VARCHAR(20),
                hora_inicio VARCHAR(20),
                hora_fin VARCHAR(20)
            )",
            "CREATE TABLE {$p}estados_clase_profesor (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                profesor_id INTEGER,
                seccion_id INTEGER,
                fecha VARCHAR(20),
                estado VARCHAR(50),
                motivo TEXT,
                modalidad VARCHAR(30),
                enlace_virtual VARCHAR(255),
                updated_at DATETIME
            )",
            "CREATE TABLE {$p}noticias_muro (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                autor_id INTEGER NOT NULL,
                titulo VARCHAR(200),
                contenido TEXT,
                categoria VARCHAR(50),
                fijado INTEGER DEFAULT 0,
                total_likes INTEGER DEFAULT 0,
                total_comentarios INTEGER DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )",
            "CREATE TABLE {$p}noticias_comentarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                noticia_id INTEGER NOT NULL,
                usuario_id INTEGER NOT NULL,
                parent_id INTEGER,
                contenido TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )",
            "CREATE TABLE {$p}estudiante_bloques_excluidos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                estudiante_id INTEGER NOT NULL,
                clase_id INTEGER NOT NULL,
                created_at DATETIME,
                UNIQUE(estudiante_id, clase_id)
            )",
        ];

        foreach ($ddl as $sql) {
            $this->db->query($sql);
        }
    }

    public function testObtenerCedulasAdminsInicialesParsesAndNormalizesCsv(): void
    {
        $this->assertSame([], obtener_cedulas_admins_iniciales(''));
        $this->assertSame([], obtener_cedulas_admins_iniciales('   ,  '));

        $parsed = obtener_cedulas_admins_iniciales(' V-30.101.010 , 30202020, 30101010 ');
        $this->assertSame(['30101010', '30202020'], $parsed);
    }

    public function testAssetUrlUsesFilemtimeForExistingAssetsAndFallbackForMissing(): void
    {
        $urlReal = asset_url('css/global.css');
        $mtime   = (string) filemtime(FCPATH . 'css' . DIRECTORY_SEPARATOR . 'global.css');
        $this->assertStringContainsString('css/global.css?v=' . $mtime, $urlReal);

        $urlFaltante = asset_url('js/no-existe-archivo.js');
        $this->assertStringEndsWith('js/no-existe-archivo.js?v=1', $urlFaltante);
    }

    public function testBibliotecaServiceContadoresYPaginacion(): void
    {
        $this->db->table('usuarios')->insert([
            'nombre_usuario' => 'ana_umc',
            'primer_nombre'  => 'Ana',
            'apellidos'      => 'Pérez',
            'correo'         => 'ana@umc.edu.ve',
            'rol'            => 'estudiante',
            'carrera'        => 'ING-INF',
        ]);
        $uid = (int) $this->db->insertID();

        for ($i = 1; $i <= 21; $i++) {
            $this->db->table('aportes_biblioteca')->insert([
                'usuario_id'      => $uid,
                'titulo'          => ($i === 21) ? 'Algoritmo Avanzado Especial' : "Guía Base {$i}",
                'categoria'       => $i <= 10 ? 'guias' : 'resumenes',
                'carrera'         => $i <= 15 ? 'ING-INF' : 'GENERAL',
                'nombre_original' => "archivo_{$i}.pdf",
                'ruta_archivo'    => "uploads/biblioteca/guias/archivo_{$i}.pdf",
                'tamano_bytes'    => 2048,
                'fecha_subida'    => sprintf('2026-11-11 10:%02d:00', $i),
            ]);
        }

        $service    = new BibliotecaService();
        $contadores = $service->obtenerContadores('ING-INF');
        $this->assertSame(21, $contadores['conteos']['todos']);
        $this->assertSame(10, $contadores['conteos']['guias']);
        $this->assertSame(11, $contadores['conteos']['resumenes']);
        $this->assertSame(15, $contadores['conteosCarreras']['ING-INF']);
        $this->assertSame(6, $contadores['conteosCarreras']['GENERAL']);

        $pag1 = $service->obtenerAportesPaginados(null, null, null, 1, 18);
        $this->assertSame(21, $pag1['total']);
        $this->assertSame(2, $pag1['totalPaginas']);
        $this->assertCount(18, $pag1['aportes']);

        $pag2 = $service->obtenerAportesPaginados(null, null, null, 2, 18);
        $this->assertCount(3, $pag2['aportes']);

        $busq = $service->obtenerAportesPaginados(null, 'Algoritmo Avanzado', null, 1, 18);
        $this->assertSame(1, $busq['total']);
        $this->assertSame('Algoritmo Avanzado Especial', $busq['aportes'][0]['titulo']);
    }

    public function testDocentePanelServicePaginatesAdminSupervision(): void
    {
        $this->db->table('materias')->insert([
            'codigo_materia'    => 'INF-101',
            'nombre'            => 'Programación I',
            'semestre_trayecto' => 1,
        ]);
        $mid = (int) $this->db->insertID();

        for ($i = 1; $i <= 15; $i++) {
            $this->db->table('secciones')->insert([
                'materia_id'        => $mid,
                'codigo_seccion'    => 'S' . $i,
                'periodo_academico' => '2026-I',
                'carrera'           => $i <= 12 ? 'ING-INF' : 'ADM',
            ]);
        }

        $service = new DocentePanelService();
        $panel1  = $service->obtenerDatosPanel(1, 'administrador', ['page' => 1, 'por_pagina' => 12]);
        $this->assertTrue($panel1['esModoServidor']);
        $this->assertSame(15, $panel1['paginacion']['totalFiltradas']);
        $this->assertSame(2, $panel1['paginacion']['totalPaginas']);
        $this->assertCount(12, $panel1['secciones']);

        $panel2 = $service->obtenerSeccionesPaginadas(1, 'administrador', ['page' => 2, 'por_pagina' => 12]);
        $this->assertCount(3, $panel2['secciones']);

        $panelAdm = $service->obtenerSeccionesPaginadas(1, 'administrador', ['carrera' => 'ADM', 'page' => 1, 'por_pagina' => 12]);
        $this->assertSame(3, $panelAdm['totalFiltradas']);
    }

    public function testAdminDashboardAndUsuarioFilterSearchByCedulaConPaginacion(): void
    {
        for ($i = 1; $i <= 23; $i++) {
            $this->db->table('usuarios')->insert([
                'cedula'              => (string) (30101000 + $i),
                'primer_nombre'       => "Usuario{$i}",
                'apellidos'           => 'Prueba',
                'nombre_usuario'      => "user{$i}",
                'correo'              => "user{$i}@umc.edu.ve",
                'rol'                 => 'estudiante',
                'carrera'             => 'ING-INF',
                'estado_verificacion' => 'aprobado',
                'estado_cuenta'       => 'activo',
            ]);
        }

        $usuarioModel = new UsuarioModel();
        $filtrados    = $usuarioModel->obtenerUsuariosFiltrados(['buscar' => '30101022'], 20, 0);
        $totalCedula  = $usuarioModel->contarUsuariosFiltrados(['buscar' => '30101022']);

        $this->assertSame(1, $totalCedula);
        $this->assertSame('30101022', $filtrados[0]['cedula']);

        $service = new AdminGestionService();
        $datos   = $service->obtenerDatosDashboard($usuarioModel, new AuditoriaAdminModel());

        $this->assertSame(23, $datos['usuariosTotal']);
        $this->assertSame(2, $datos['usuariosPaginas']);
        $this->assertCount(20, $datos['usuarios']);
    }

    public function testActividadServiceCalificacionYActividadClaseModelPorRol(): void
    {
        $this->db->table('usuarios')->insert([
            'nombre_usuario' => 'profe_umc',
            'primer_nombre'  => 'Carlos',
            'apellidos'      => 'Ruiz',
            'correo'         => 'cruiz@umc.edu.ve',
            'rol'            => 'profesor',
        ]);
        $profId = (int) $this->db->insertID();

        $this->db->table('usuarios')->insert([
            'nombre_usuario' => 'est_umc',
            'primer_nombre'  => 'Luis',
            'apellidos'      => 'Gómez',
            'correo'         => 'lgomez@umc.edu.ve',
            'rol'            => 'estudiante',
        ]);
        $estId = (int) $this->db->insertID();

        $this->db->table('materias')->insert([
            'codigo_materia'    => 'MAT-201',
            'nombre'            => 'Cálculo II',
            'semestre_trayecto' => 2,
        ]);
        $matId = (int) $this->db->insertID();

        $this->db->table('secciones')->insert([
            'materia_id'        => $matId,
            'codigo_seccion'    => 'A',
            'periodo_academico' => '2026-I',
            'carrera'           => 'ING-INF',
            'profesor_id'       => $profId,
        ]);
        $secId = (int) $this->db->insertID();

        $this->db->table('inscripciones')->insert([
            'estudiante_id' => $estId,
            'seccion_id'    => $secId,
        ]);

        $actService = new ActividadService();
        $creada = $actService->crearActividadEnSeccion($profId, 'profesor', [
            'seccion_id'    => $secId,
            'titulo'        => 'Parcial 1',
            'tipo'          => 'parcial',
            'fecha_entrega' => '2026-11-20 12:00:00',
            'ponderacion'   => 25,
            'descripcion'   => 'Derivadas e integrales',
        ], true);
        $this->assertTrue($creada['ok']);
        $actId = $creada['actividad_id'];

        $this->db->table('entregas_actividades')->insert([
            'actividad_id'      => $actId,
            'estudiante_id'     => $estId,
            'comentario_alumno' => 'Adjunto solución',
            'created_at'        => '2026-11-11 12:00:00',
            'updated_at'        => '2026-11-11 12:00:00',
        ]);
        $entregaId = (int) $this->db->insertID();

        $resInvalid = $actService->calificarEntregaEstudiante($entregaId, $profId, 'profesor', 150, 'Inválida');
        $this->assertFalse($resInvalid['ok']);
        $this->assertSame(400, $resInvalid['status']);

        $resOk = $actService->calificarEntregaEstudiante($entregaId, $profId, 'profesor', 19.0, 'Excelente');
        $this->assertTrue($resOk['ok']);

        $modelo = new ActividadClaseModel();
        session()->set('rol', 'estudiante');
        $actEst = $modelo->obtenerParaEstudiante($estId);
        $this->assertCount(1, $actEst);
        $this->assertEqualsWithDelta(19.0, (float) $actEst[0]['calificacion'], 0.01);
        $this->assertSame('Excelente', $actEst[0]['feedback_profesor']);

        $this->db->table('entregas_actividades')->insert([
            'actividad_id'      => $actId,
            'estudiante_id'     => $profId,
            'comentario_alumno' => 'Prueba admin',
            'calificacion'      => 17.5,
            'feedback_profesor' => 'Muy bien',
            'created_at'        => '2026-11-11 13:00:00',
            'updated_at'        => '2026-11-11 13:00:00',
        ]);
        session()->set('rol', 'administrador');
        $actAdm = $modelo->obtenerParaEstudiante($profId);
        $this->assertCount(1, $actAdm);
        $this->assertEqualsWithDelta(17.5, (float) $actAdm[0]['calificacion'], 0.01);
        $this->assertContains('ING-INF', HorarioSyncService::CARRERAS_VALIDAS);
    }

    public function testHorarioSyncServiceBatchAtomicPreservesPersonalBlocksAndRejectsInvalid(): void
    {
        $sync = new HorarioSyncService();

        // 1. Carga inicial por lotes para ING-INF y ADM (reutilizando misma materia/sección sin duplicar)
        $bloquesInf = [
            [
                'codigo_materia' => 'INF-101',
                'nombre_materia' => 'Programación I',
                'semestre'       => 1,
                'seccion'        => 'A',
                'periodo'        => '2026-I',
                'carrera'        => 'ING-INF',
                'aula'           => 'Aula 1',
                'dia'            => 'Lunes',
                'hora_inicio'    => '07:15:00',
                'hora_fin'       => '08:45:00',
            ],
            [
                'codigo_materia' => 'INF-101',
                'nombre_materia' => 'Programación I',
                'semestre'       => 1,
                'seccion'        => 'A',
                'periodo'        => '2026-I',
                'carrera'        => 'ING-INF',
                'aula'           => 'Lab Ing I',
                'dia'            => 'Miercoles',
                'hora_inicio'    => '07:15:00',
                'hora_fin'       => '08:45:00',
            ],
        ];
        $bloquesAdm = [
            [
                'codigo_materia' => 'ADM-101',
                'nombre_materia' => 'Contabilidad I',
                'semestre'       => 1,
                'seccion'        => 'A',
                'periodo'        => '2026-I',
                'carrera'        => 'ADM',
                'aula'           => 'Aula 10',
                'dia'            => 'Martes',
                'hora_inicio'    => '08:00:00',
                'hora_fin'       => '09:30:00',
            ],
        ];

        $this->assertSame(2, $sync->insertarBloquesEstructurados($bloquesInf, 'ING-INF', true));
        $this->assertSame(1, $sync->insertarBloquesEstructurados($bloquesAdm, 'ADM', true));
        $this->assertSame(2, $this->db->table('materias')->countAllResults());
        $this->assertSame(2, $this->db->table('secciones')->countAllResults());

        // Bloque manual personal de estudiante en ING-INF debe sobrevivir al reemplazo oficial
        $secInf = $this->db->table('secciones')->where('carrera', 'ING-INF')->get()->getRowArray();
        $this->db->table('clases_programadas')->insert([
            'seccion_id'    => (int) $secInf['id'],
            'estudiante_id' => 77,
            'aula'          => 'Aula 5',
            'carrera'       => 'ING-INF',
            'dia_semana'    => 'Viernes',
            'hora_inicio'   => '10:15:00',
            'hora_fin'      => '11:45:00',
        ]);

        // 2. Payload vacío/inválido no debe borrar horarios ya existentes
        $resVacio = $sync->insertarBloquesEstructurados([['codigo_materia' => 'HUERFANO']], 'ING-INF', true);
        $this->assertSame(0, $resVacio);
        $this->assertSame(4, $this->db->table('clases_programadas')->countAllResults());

        // 3. Reemplazar solo ING-INF mantiene ADM y bloque personal del alumno
        $bloquesInfNuevo = [
            [
                'codigo_materia' => 'INF-101',
                'nombre_materia' => 'Programación I',
                'semestre'       => 1,
                'seccion'        => 'A',
                'periodo'        => '2026-I',
                'carrera'        => 'ING-INF',
                'aula'           => 'Aula 22',
                'dia'            => 'Jueves',
                'hora_inicio'    => '08:00:00',
                'hora_fin'       => '09:30:00',
            ],
        ];
        $this->assertSame(1, $sync->insertarBloquesEstructurados($bloquesInfNuevo, 'ING-INF', true));
        $this->assertSame(1, $this->db->table('clases_programadas')->where('carrera', 'ADM')->countAllResults());
        $this->assertSame(1, $this->db->table('clases_programadas')->where('estudiante_id', 77)->countAllResults());
        $this->assertSame(1, $this->db->table('clases_programadas')->where('carrera', 'ING-INF')->where('estudiante_id', null)->countAllResults());

        // 4. Validación de subida de archivo no permitido
        $fakeBadFile = new class (__FILE__, 'virus.exe', 'application/octet-stream') extends \CodeIgniter\HTTP\Files\UploadedFile {
            public function isValid(): bool { return true; }
            public function hasMoved(): bool { return false; }
            public function getClientExtension(): string { return 'exe'; }
        };
        $resArchivoBad = $sync->sincronizarDesdeArchivo($fakeBadFile, 'ING-INF');
        $this->assertFalse($resArchivoBad['ok']);
    }

    /**
     * Prueba el flujo de solicitud de asignaturas extemporáneas y su control por parte del docente.
     */
    public function testSolicitudExtemporaneaWorkflow(): void
    {
        // 0. Registrar materia, sección asignada al docente 20 y estudiante 99
        $this->db->table('materias')->insert([
            'id'             => 5,
            'nombre'         => 'Cálculo Avanzado',
            'codigo_materia' => 'CAL-500',
        ]);
        $this->db->table('secciones')->insert([
            'id'             => 10,
            'materia_id'     => 5,
            'profesor_id'    => 20,
            'codigo_seccion' => 'SVC-A',
            'carrera'        => 'ING-INF',
        ]);
        $this->db->table('usuarios')->insert([
            'id'             => 99,
            'primer_nombre'  => 'Carlos',
            'apellidos'      => 'Pérez',
            'cedula'         => 'V-29000111',
            'correo'         => 'carlos@umc.edu.ve',
            'rol'            => 'estudiante',
        ]);

        $solModel = new \App\Models\SolicitudExtemporaneaModel();

        // 1. Crear una solicitud de un estudiante a la sección 10 (Profesor 20)
        $solId = $solModel->insert([
            'estudiante_id' => 99,
            'seccion_id'    => 10,
            'profesor_id'   => 20,
            'estado'        => 'pendiente',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->assertGreaterThan(0, $solId);

        // 2. Verificar que el estudiante tiene la sección en lista de pendientes
        $pendientes = $solModel->obtenerIdsSolicitudesPendientesEstudiante(99);
        $this->assertContains(10, $pendientes);

        // 3. Verificar que el profesor tiene 1 solicitud pendiente
        $conteoProf = $solModel->contarPendientesParaDocente(20, false);
        $this->assertSame(1, $conteoProf);

        // Otro profesor no debe verla
        $conteoOtroProf = $solModel->contarPendientesParaDocente(999, false);
        $this->assertSame(0, $conteoOtroProf);

        // 4. Simular aprobación: se inscribe al alumno y se actualiza estado
        $this->db->table('inscripciones')->insert([
            'estudiante_id' => 99,
            'seccion_id'    => 10,
        ]);
        $solModel->update($solId, ['estado' => 'aprobada']);

        // Ahora no debe estar pendiente
        $pendientesDespues = $solModel->obtenerIdsSolicitudesPendientesEstudiante(99);
        $this->assertNotContains(10, $pendientesDespues);
        $this->assertSame(0, $solModel->contarPendientesParaDocente(20, false));
    public function testNoticiaComentarioModelValidaParentYEvitaDobleEscapadoYNotifDuplicada(): void
    {
        $this->db->table('usuarios')->insertBatch([
            ['id' => 1, 'primer_nombre' => 'Ana', 'apellidos' => 'Autora', 'nombre_usuario' => 'ana', 'rol' => 'profesor'],
            ['id' => 2, 'primer_nombre' => 'Luis', 'apellidos' => 'Alumno', 'nombre_usuario' => 'luis', 'rol' => 'estudiante'],
        ]);

        $this->db->table('noticias_muro')->insertBatch([
            ['id' => 10, 'autor_id' => 1, 'titulo' => 'Aviso 1', 'contenido' => 'Contenido', 'total_comentarios' => 0],
            ['id' => 20, 'autor_id' => 1, 'titulo' => 'Aviso 2', 'contenido' => 'Otro', 'total_comentarios' => 0],
        ]);

        $modelo = new \App\Models\NoticiaComentarioModel();

        // Comentario inicial del propio autor de la noticia en Aviso 1
        $cPadre = $modelo->agregarComentario(10, 1, 'Duda con "comillas" & apostrofe \'simple\'');
        $this->assertTrue($cPadre['success']);
        $this->assertSame('Duda con "comillas" & apostrofe \'simple\'', $cPadre['comentario']['contenido']);
        $comentarioPadreId = (int) $cPadre['comentario_id'];

        // Responder a ese parent_id desde Aviso 2 debe rechazarse
        $cCruzado = $modelo->agregarComentario(20, 2, 'Respuesta cruzada', $comentarioPadreId);
        $this->assertArrayHasKey('error', $cCruzado);

        // Responder en Aviso 1 al comentario del autor de la noticia debe generar 1 sola notificación al autor (no 2)
        $cResp = $modelo->agregarComentario(10, 2, 'Respuesta válida', $comentarioPadreId);
        $this->assertTrue($cResp['success']);
        $notifsAutor = $this->db->table('notificaciones')->where('estudiante_id', 1)->get()->getResultArray();
        $this->assertCount(1, $notifsAutor);
        $this->assertSame('respuesta', $notifsAutor[0]['tipo']);
    }

    public function testHorarioSyncLegacyUsaFallbackCortoParaSeccionYPeriodo(): void
    {
        $tmpJson = tempnam(sys_get_temp_dir(), 'lay8_legacy_') . '.json';
        file_put_contents($tmpJson, json_encode([
            [
                'Hora'  => "07:15\n08:00",
                'Lunes' => "INF-101\nAlgoritmos\nAula 4",
            ],
        ]));

        try {
            $sync = new HorarioSyncService();
            $ref = new \ReflectionMethod($sync, 'insertarBloquesLegacy');
            $ref->setAccessible(true);
            $insertados = $ref->invoke($sync, json_decode(file_get_contents($tmpJson), true));

            $this->assertSame(1, $insertados);
            $sec = $this->db->table('secciones')->get()->getRowArray();
            $this->assertNotNull($sec);
            $this->assertSame('N/D', $sec['codigo_seccion']);
            $this->assertLessThanOrEqual(10, strlen((string) $sec['codigo_seccion']));
        } finally {
            @unlink($tmpJson);
        }
    }

    public function testAulasControllerLimpiarHorarioYEliminarBloqueRbac(): void
    {
        $this->db->table('materias')->insert(['id' => 1, 'codigo_materia' => 'CAL-101', 'nombre' => 'Cálculo I']);
        $this->db->table('secciones')->insert(['id' => 5, 'materia_id' => 1, 'codigo_seccion' => 'A']);
        $this->db->table('inscripciones')->insert(['estudiante_id' => 99, 'seccion_id' => 5]);
        $this->db->table('clases_programadas')->insert([
            'id'            => 50,
            'seccion_id'    => 5,
            'estudiante_id' => null,
            'aula'          => 'Aula 2',
            'dia_semana'    => 'Lunes',
            'hora_inicio'   => '07:15:00',
            'hora_fin'      => '08:00:00',
        ]);

        // Un docente pendiente de aprobación NO puede borrar bloques oficiales directamente
        session()->set([
            'usuario_id'          => 99,
            'rol'                 => 'profesor',
            'estado_verificacion' => 'pendiente',
        ]);

        $req = \Config\Services::incomingrequest();
        $req->setHeader('X-Requested-With', 'XMLHttpRequest');
        $ctrl = new \App\Controllers\AulasController();
        $ctrl->initController($req, \Config\Services::response(), \Config\Services::logger());

        $respDel = $ctrl->eliminarBloqueManual(50);
        $this->assertSame(200, $respDel->getStatusCode());
        $this->assertSame(1, $this->db->table('clases_programadas')->where('id', 50)->countAllResults());
        $this->assertSame(1, $this->db->table('estudiante_bloques_excluidos')->where('estudiante_id', 99)->where('clase_id', 50)->countAllResults());

        // Limpiar horario personal debe usar obtenerSeccionesDeEstudiante() e insertar exclusiones sin fallar
        $this->db->table('estudiante_bloques_excluidos')->truncate();
        $respClean = $ctrl->limpiarHorarioPersonal();
        $this->assertSame(200, $respClean->getStatusCode());
        $this->assertSame(1, $this->db->table('estudiante_bloques_excluidos')->where('estudiante_id', 99)->where('clase_id', 50)->countAllResults());
    }

    public function testAulasControllerCrearYEliminarActividadRbacYValidaciones(): void
    {
        $this->db->table('usuarios')->insertBatch([
            ['id' => 11, 'primer_nombre' => 'Est', 'apellidos' => 'Uno', 'nombre_usuario' => 'est11', 'rol' => 'estudiante', 'estado_verificacion' => 'aprobado'],
            ['id' => 12, 'primer_nombre' => 'Prof', 'apellidos' => 'Pend', 'nombre_usuario' => 'prof12', 'rol' => 'profesor', 'estado_verificacion' => 'pendiente'],
            ['id' => 13, 'primer_nombre' => 'Admin', 'apellidos' => 'Short', 'nombre_usuario' => 'adm13', 'rol' => 'admin', 'estado_verificacion' => 'aprobado'],
        ]);
        $this->db->table('materias')->insert(['id' => 2, 'codigo_materia' => 'PRG-101', 'nombre' => 'Programación I']);
        $this->db->table('secciones')->insert(['id' => 8, 'materia_id' => 2, 'codigo_seccion' => 'A']);
        $this->db->table('inscripciones')->insert(['estudiante_id' => 11, 'seccion_id' => 8]);

        // 1. Validación de fecha inválida y ponderación fuera de rango en ActividadService
        $svc = new ActividadService();
        $badFecha = $svc->crearActividadEnSeccion(13, 'admin', [
            'seccion_id'    => 8,
            'titulo'        => 'Eval Fecha Mala',
            'tipo'          => 'parcial',
            'fecha_entrega' => 'no-es-fecha',
            'ponderacion'   => 20,
        ], true);
        $this->assertFalse($badFecha['ok']);
        $this->assertSame(400, $badFecha['status']);

        $badPond = $svc->crearActividadEnSeccion(13, 'admin', [
            'seccion_id'    => 8,
            'titulo'        => 'Eval Ponderacion Mala',
            'tipo'          => 'parcial',
            'fecha_entrega' => '2026-12-01 10:00:00',
            'ponderacion'   => 150,
        ], true);
        $this->assertFalse($badPond['ok']);
        $this->assertSame(400, $badPond['status']);

        // 2. Un estudiante NO debe poder crear actividades vía POST /aulas/crear-actividad
        $postPayload = [
            'seccion_id'    => '8',
            'titulo'        => 'Actividad Inyectada',
            'descripcion'   => 'Prueba',
            'tipo'          => 'tarea',
            'fecha_entrega' => '2026-12-01T10:00',
            'ponderacion'   => '10',
        ];
        $req = \Config\Services::incomingrequest(null, false);
        $req->setGlobal('post', $postPayload);

        session()->set(['usuario_id' => 11, 'rol' => 'estudiante', 'estado_verificacion' => 'aprobado']);
        $ctrl = new \App\Controllers\AulasController();
        $ctrl->initController($req, \Config\Services::response(), \Config\Services::logger());
        $ctrl->crearActividad();
        $this->assertSame(0, $this->db->table('actividades_clase')->countAllResults());

        // 3. Un docente pendiente NO debe poder crear actividades vía POST /aulas/crear-actividad
        session()->set(['usuario_id' => 12, 'rol' => 'profesor', 'estado_verificacion' => 'pendiente']);
        $ctrl->crearActividad();
        $this->assertSame(0, $this->db->table('actividades_clase')->countAllResults());

        // 4. Un admin creando desde /aulas/crear-actividad debe crear tarea oficial (es_personal = 0) y notificar por lote
        session()->set(['usuario_id' => 13, 'rol' => 'admin', 'estado_verificacion' => 'aprobado']);
        $ctrl->crearActividad();
        $act = $this->db->table('actividades_clase')->get()->getRowArray();
        $this->assertNotNull($act);
        $this->assertSame(0, (int) $act['es_personal']);
        $this->assertSame(1, $this->db->table('notificaciones')->where('estudiante_id', 11)->countAllResults());

        // 5. Eliminar actividad debe aceptar rol 'admin' (además de 'administrador') aunque no sea creador
        $this->db->table('actividades_clase')->where('id', $act['id'])->update(['creado_por' => 999]);
        $ctrl->eliminarActividad((int) $act['id']);
        $this->assertSame(0, $this->db->table('actividades_clase')->where('id', $act['id'])->countAllResults());
    }
}
