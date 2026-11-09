<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/admin.css?v=' . time()) ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="admin-dashboard-container">

  <!-- Encabezado del Panel -->
  <div class="admin-header-row">
    <div>
      <div class="titulos-inline">
        <h2 class="form-titulo"><i class="bx bx-shield-quarter" style="color: #1e3a8a; vertical-align: middle;"></i> Panel de Administración</h2>
      </div>
    </div>

    <div class="admin-badge-status">
      <span class="badge-admin-pill"><i class="bx bx-check-shield"></i> Modo Administrador Activo</span>
    </div>
  </div>

  <!-- Mensajes Flash de Retroalimentación -->
  <?php if(session()->getFlashdata('mensaje')): ?>
    <div class="alerta-notif alerta-exito" style="margin-bottom: 20px;">
      <i class="bx bx-check-circle" style="font-size: 1.3rem;"></i>
      <span><?= session()->getFlashdata('mensaje') ?></span>
    </div>
  <?php endif; ?>

  <?php if(session()->getFlashdata('error')): ?>
    <div class="alerta-notif alerta-error" style="margin-bottom: 20px;">
      <i class="bx bx-error-circle" style="font-size: 1.3rem;"></i>
      <span><?= session()->getFlashdata('error') ?></span>
    </div>
  <?php endif; ?>

  <!-- Grid de Métricas Principales (KPI Cards) -->
  <div class="admin-kpi-grid">
    <div class="kpi-card" onclick="mostrarTabAdmin('usuarios')">
      <div class="kpi-icon icon-blue"><i class="bx bx-group"></i></div>
      <div class="kpi-data">
        <span class="kpi-num"><?= esc($metricas['total_usuarios']) ?></span>
        <span class="kpi-label">Usuarios Totales</span>
      </div>
    </div>

    <div class="kpi-card" onclick="mostrarTabAdmin('profesores')">
      <div class="kpi-icon icon-orange"><i class="bx bx-user-check"></i></div>
      <div class="kpi-data">
        <span class="kpi-num"><?= esc($metricas['solicitudes_pendientes']) ?></span>
        <span class="kpi-label">Profesores por Validar</span>
      </div>
    </div>

    <div class="kpi-card" onclick="mostrarTabAdmin('biblioteca')">
      <div class="kpi-icon icon-teal"><i class="bx bx-book-content"></i></div>
      <div class="kpi-data">
        <span class="kpi-num"><?= esc($metricas['total_aportes']) ?></span>
        <span class="kpi-label">Aportes en Biblioteca</span>
      </div>
    </div>

    <div class="kpi-card" onclick="mostrarTabAdmin('horario')">
      <div class="kpi-icon icon-purple"><i class="bx bx-calendar-event"></i></div>
      <div class="kpi-data">
        <span class="kpi-num"><?= esc($horarioInfo['total_clases']) ?></span>
        <span class="kpi-label">Bloques de Horario</span>
      </div>
    </div>
  </div>

  <!-- Barra de Pestañas de Navegación del Panel -->
  <div class="admin-tabs-bar">
    <button type="button" class="tab-btn-admin activa" id="tabbtn-profesores" onclick="mostrarTabAdmin('profesores')">
      <i class="bx bx-user-check"></i> Solicitudes Docentes 
      <?php if ($metricas['solicitudes_pendientes'] > 0): ?>
        <span class="tab-badge-contador"><?= $metricas['solicitudes_pendientes'] ?></span>
      <?php endif; ?>
    </button>
    <button type="button" class="tab-btn-admin" id="tabbtn-docentes" onclick="mostrarTabAdmin('docentes')">
      <i class="bx bx-chalkboard"></i> Asignar Secciones (Aulas)
      <?php if (!empty($profesoresActivos)): ?>
        <span class="tab-badge-contador" style="background: #2563eb;"><?= count($profesoresActivos) ?></span>
      <?php endif; ?>
    </button>
    <button type="button" class="tab-btn-admin" id="tabbtn-usuarios" onclick="mostrarTabAdmin('usuarios')">
      <i class="bx bx-user"></i> Gestión de Cuentas
    </button>
    <button type="button" class="tab-btn-admin" id="tabbtn-biblioteca" onclick="mostrarTabAdmin('biblioteca')">
      <i class="bx bx-book-bookmark"></i> Biblioteca y Publicaciones
    </button>
    <button type="button" class="tab-btn-admin" id="tabbtn-horario" onclick="mostrarTabAdmin('horario')">
      <i class="bx bx-time-five"></i> Horario Oficial del Mapa
    </button>
    <button type="button" class="tab-btn-admin" id="tabbtn-auditoria" onclick="mostrarTabAdmin('auditoria')">
      <i class="bx bx-history"></i> Auditoría
    </button>
  </div>

  <!-- tab 1: profes por aprobar -->
  <div id="tab-profesores" class="admin-tab-pane activa">
    <div class="admin-section-box">
      <div class="section-box-header">
        <div>
          <h3>Solicitudes de Registro Docente</h3>
          <p>Verifica el carnet institucional y aprueba o rechaza el rol de profesor.</p>
        </div>
      </div>

      <?php if (empty($solicitudes)): ?>
        <div class="admin-empty-state">
          <i class="bx bx-check-double" style="font-size: 3rem; color: #10b981;"></i>
          <h4>¡Al día! No hay solicitudes pendientes</h4>
          <p>Todos los profesores registrados han sido revisados y procesados.</p>
        </div>
      <?php else: ?>
        <div class="tabla-responsive-container">
          <table class="tabla-admin">
            <thead>
              <tr>
                <th>Docente</th>
                <th>Cédula</th>
                <th>Usuario / Correo</th>
                <th>Fecha de Registro</th>
                <th>Comprobante / Carnet</th>
                <th style="text-align: right;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($solicitudes as $sol): ?>
                <tr>
                  <td>
                    <strong><?= esc($sol['primer_nombre'] . ' ' . $sol['apellidos']) ?></strong>
                  </td>
                  <td>
                    <code style="background: #eff6ff; color: #1e40af; padding: 3px 7px; border-radius: 5px; font-weight: 600; font-size: 0.85rem;"><?= esc($sol['cedula'] ?? 'N/D') ?></code>
                  </td>
                  <td>
                    <div style="font-weight: 600;">@<?= esc($sol['nombre_usuario']) ?></div>
                    <div style="font-size: 0.82rem; color: #64748b;"><?= esc($sol['correo']) ?></div>
                  </td>
                  <td><?= date('d/m/Y H:i', strtotime($sol['fecha_registro'])) ?></td>
                  <td>
                    <?php if (!empty($sol['ruta_carnet'])): ?>
                      <a href="<?= base_url($sol['ruta_carnet']) ?>" target="_blank" class="btn-carnet-link" title="Abrir carnet en nueva pestaña">
                        <i class="bx bx-id-card"></i> Ver Carnet
                      </a>
                    <?php else: ?>
                      <span style="color: #94a3b8; font-size: 0.85rem;">Sin archivo</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: right;">
                    <div class="acciones-btn-group">
                      <button type="button" class="btn-accion-admin btn-aprobar" onclick="aprobarProfesor(<?= (int)$sol['id'] ?>, '<?= esc($sol['nombre_usuario'], 'js') ?>')">
                        <i class="bx bx-check"></i> Aprobar
                      </button>
                      <button type="button" class="btn-accion-admin btn-rechazar" onclick="abrirModalDenegarProfesor(<?= (int)$sol['id'] ?>, '<?= esc($sol['nombre_usuario'], 'js') ?>')">
                        <i class="bx bx-x"></i> Denegar
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

      </div>

  <!-- tab: asignacion de materias a profesores -->
  <div id="tab-docentes" class="admin-tab-pane">
    <div class="admin-section-box">
      <div class="section-box-header">
        <div>
          <h3><i class="bx bx-chalkboard"></i> Docentes Activos y Asignación de Secciones (Aulas)</h3>
          <p>Asigna las materias y secciones oficiales a cada profesor para que puedan gestionar sus aulas, estados y tareas.</p>
        </div>
      </div>

      <?php if (empty($profesoresActivos)): ?>
        <div class="admin-empty-state">
          <i class="bx bx-user-x" style="font-size: 2.8rem; color: #94a3b8;"></i>
          <h4>No hay profesores aprobados aún</h4>
          <p>Aprueba las solicitudes docentes pendientes arriba para habilitar su asignación de materias.</p>
        </div>
      <?php else: ?>
        <div class="tabla-responsive-container">
          <table class="tabla-admin">
            <thead>
              <tr>
                <th>Docente</th>
                <th>Cédula</th>
                <th>Usuario / Correo</th>
                <th>Secciones Asignadas</th>
                <th style="text-align: right;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($profesoresActivos as $prof): ?>
                <tr>
                  <td>
                    <strong><?= esc($prof['primer_nombre'] . ' ' . $prof['apellidos']) ?></strong>
                  </td>
                  <td>
                    <code style="background: #eff6ff; color: #1e40af; padding: 3px 7px; border-radius: 5px; font-weight: 600; font-size: 0.85rem;"><?= esc($prof['cedula'] ?? 'N/D') ?></code>
                  </td>
                  <td>
                    <div style="font-weight: 600;">@<?= esc($prof['nombre_usuario']) ?></div>
                    <div style="font-size: 0.82rem; color: #64748b;"><?= esc($prof['correo']) ?></div>
                  </td>
                  <td>
                    <span class="badge-accion-tag" style="background: #eff6ff; color: #2563eb; font-weight: 700;">
                      <i class="bx bx-door-open"></i> <?= (int)$prof['total_secciones_asignadas'] ?> secciones
                    </span>
                  </td>
                  <td style="text-align: right;">
                    <button type="button" class="btn-accion-admin btn-aprobar" onclick="abrirModalAsignarSecciones(<?= (int)$prof['id'] ?>, '<?= esc($prof['primer_nombre'] . ' ' . $prof['apellidos'], 'js') ?>')">
                      <i class="bx bx-link-alt"></i> Asignar Secciones
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 2: GESTIÓN DE USUARIOS Y BANEO -->
  <div id="tab-usuarios" class="admin-tab-pane">
    <div class="admin-section-box">
      <div class="section-box-header">
        <div>
          <h3>Directorio y Control de Usuarios</h3>
          <p>Supervisa cuentas, gestiona suspensiones, baneos y reasignación de roles.</p>
        </div>
        <div class="admin-search-wrap">
          <input type="text" id="filtro-usuarios-input" class="input-global input-admin-search" placeholder="Buscar por nombre, usuario, correo..." oninput="filtrarTablaUsuarios()">
        </div>
      </div>

      <div class="tabla-responsive-container">
        <table class="tabla-admin" id="tabla-usuarios-admin">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre Completo</th>
              <th>Cédula</th>
              <th>Usuario / Contacto</th>
              <th>Carrera</th>
              <th>Rol</th>
              <th>Estado</th>
              <th style="text-align: right;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
              <tr class="fila-usuario-item" data-texto="<?= strtolower(esc($u['primer_nombre'] . ' ' . $u['apellidos'] . ' ' . $u['nombre_usuario'] . ' ' . $u['correo'] . ' ' . $u['carrera'] . ' ' . $u['rol'])) ?>">
                <td>#<?= (int)$u['id'] ?></td>
                <td>
                  <strong><?= esc($u['primer_nombre'] . ' ' . $u['apellidos']) ?></strong>
                  <?php if (in_array($u['rol'], ['admin', 'administrador'])): ?>
                    <i class="bx bxs-check-circle" style="color: #2563eb; vertical-align: middle; margin-left: 2px;" title="Administrador Verificado"></i>
                  <?php elseif ($u['rol'] === 'profesor'): ?>
                    <i class="bx bxs-check-circle" style="color: #10b981; vertical-align: middle; margin-left: 2px;" title="Profesor Verificado"></i>
                  <?php endif; ?>
                </td>
                <td><code style="background: #eff6ff; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 0.84rem;"><?= esc($u['cedula'] ?? 'N/D') ?></code></td>
                <td>
                  <div>@<?= esc($u['nombre_usuario']) ?></div>
                  <div style="font-size: 0.8rem; color: #64748b;"><?= esc($u['correo']) ?></div>
                </td>
                <td><span class="badge-carrera-tag"><?= esc($u['carrera'] ?: 'ING-INF') ?></span></td>
                <td>
                  <span class="badge-rol-tag badge-rol-<?= esc($u['rol']) ?>"><?= ucfirst(esc($u['rol'])) ?></span>
                </td>
                <td>
                  <?php if (($u['estado_cuenta'] ?? 'activo') === 'baneado'): ?>
                    <span class="badge-estado-baneado" title="<?= esc($u['motivo_baneo'] ?? '') ?>"><i class="bx bx-block"></i> Baneado</span>
                  <?php else: ?>
                    <span class="badge-estado-activo"><i class="bx bx-check-circle"></i> Activo</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <div class="acciones-btn-group">
                    <?php if (($u['estado_cuenta'] ?? 'activo') === 'baneado'): ?>
                      <button type="button" class="btn-accion-admin btn-desbanear" onclick="desbanearUsuario(<?= (int)$u['id'] ?>, '<?= esc($u['nombre_usuario'], 'js') ?>')">
                        <i class="bx bx-undo"></i> Reactivar
                      </button>
                    <?php else: ?>
                      <?php if ($u['rol'] !== 'administrador'): ?>
                        <button type="button" class="btn-accion-admin btn-banear" onclick="abrirModalBanear(<?= (int)$u['id'] ?>, '<?= esc($u['nombre_usuario'], 'js') ?>')">
                          <i class="bx bx-block"></i> Banear
                        </button>
                      <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($u['rol'] !== 'administrador'): ?>
                      <button type="button" class="btn-accion-admin btn-eliminar" onclick="eliminarUsuario(<?= (int)$u['id'] ?>, '<?= esc($u['nombre_usuario'], 'js') ?>')">
                        <i class="bx bx-trash"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 3: BIBLIOTECA Y PUBLICACIONES -->
  <div id="tab-biblioteca" class="admin-tab-pane">
    <div class="admin-section-box">
      <div class="section-box-header">
        <div>
          <h3>Moderación de Publicaciones y Recursos</h3>
          <p>Revisa y elimina archivos o publicaciones que infrinjan los términos de uso.</p>
        </div>
      </div>

      <?php if (empty($aportes)): ?>
        <div class="admin-empty-state">
          <i class="bx bx-folder-open" style="font-size: 3rem; color: #94a3b8;"></i>
          <h4>No hay aportes registrados</h4>
          <p>La biblioteca aún no cuenta con archivos subidos.</p>
        </div>
      <?php else: ?>
        <div class="tabla-responsive-container">
          <table class="tabla-admin">
            <thead>
              <tr>
                <th>Recurso</th>
                <th>Categoría</th>
                <th>Subido por</th>
                <th>Tamaño</th>
                <th>Fecha</th>
                <th style="text-align: right;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($aportes as $ap): ?>
                <tr>
                  <td>
                    <strong><?= esc($ap['titulo']) ?></strong>
                    <div style="font-size: 0.8rem; color: #64748b;"><?= esc($ap['nombre_original']) ?></div>
                  </td>
                  <td><span class="badge-categoria-tag"><?= ucfirst(esc($ap['categoria'])) ?></span></td>
                  <td>
                    <?php $autor = $ap['nombre_usuario'] ?? $ap['autor_usuario'] ?? null; ?>
                    <?php if (!empty($autor)): ?>
                      @<?= esc($autor) ?>
                    <?php else: ?>
                      <span style="color: #94a3b8;">Desvinculado</span>
                    <?php endif; ?>
                  </td>
                  <td><?= round(($ap['tamano_bytes'] ?? 0) / 1024, 1) ?> KB</td>
                  <td><?= !empty($ap['fecha_subida']) ? date('d/m/Y', strtotime($ap['fecha_subida'])) : (!empty($ap['created_at']) ? date('d/m/Y', strtotime($ap['created_at'])) : 'Reciente') ?></td>
                  <td style="text-align: right;">
                    <div class="acciones-btn-group">
                      <a href="<?= base_url('biblioteca/descargar/' . $ap['id']) ?>" class="btn-accion-admin btn-descargar" target="_blank" title="Descargar comprobante">
                        <i class="bx bx-download"></i>
                      </a>
                      <button type="button" class="btn-accion-admin btn-eliminar" onclick="abrirModalEliminarAporte(<?= (int)$ap['id'] ?>, '<?= esc($ap['titulo'], 'js') ?>')">
                        <i class="bx bx-trash"></i> Eliminar
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 4: HORARIO OFICIAL Y MAPA -->
  <div id="tab-horario" class="admin-tab-pane">
    <div class="admin-section-box">
      <div class="section-box-header">
        <div>
          <h3>Actualización del Horario de Referencia para el Mapa</h3>
          <p>Sube el documento PDF oficial emitido por la universidad para sincronizar automáticamente las aulas y horarios del campus.</p>
        </div>
      </div>

      <div class="horario-admin-layout">
        <div class="horario-status-card">
          <h4><i class="bx bx-file"></i> Estado del Archivo Actual</h4>
          <ul class="horario-status-list">
            <li><strong>Archivo activo:</strong> <code>scripts_python/horario.pdf</code></li>
            <li><strong>Estado en disco:</strong> <?= $horarioInfo['existe_archivo'] ? '<span style="color:#10b981; font-weight:700;">Presente</span>' : '<span style="color:#ef4444; font-weight:700;">No encontrado</span>' ?></li>
            <li><strong>Tamaño:</strong> <?= esc($horarioInfo['tamano_kb']) ?> KB</li>
            <li><strong>Última sincronización:</strong> <?= esc($horarioInfo['ultima_modificacion']) ?></li>
            <li><strong>Bloques de clases en BD:</strong> <span class="badge-counter-active"><?= esc($horarioInfo['total_clases']) ?> clases</span></li>
            <li><strong>Total de Aulas mapeadas:</strong> <?= esc($horarioInfo['total_aulas']) ?> aulas</li>
          </ul>
        </div>

        <div class="horario-upload-card">
          <h4><i class="bx bx-calendar-event"></i> Gestión de Horarios por Carrera</h4>
          <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 15px;">
            Sincroniza y procesa los documentos PDF oficiales (Formulario CGD-001) para cada carrera de la UMC. Cada carrera cuenta con su programación académica del 1er al último semestre.
          </p>

          <div class="carreras-horarios-summary-box">
            <div class="carreras-horarios-header">
              <span>Programación en el Mapa</span>
              <span class="badge-counter-active"><?= esc($horarioInfo['total_clases']) ?> clases activas</span>
            </div>
            <div class="carreras-horarios-grid">
              <?php if (!empty($carrerasHorarios)): ?>
                <?php foreach ($carrerasHorarios as $cCode => $cInfo): ?>
                  <div class="carreras-horarios-chip">
                    <i class="bx <?= esc($cInfo['icono']) ?>" style="color: <?= $cInfo['color'] ?>; font-size: 1.3rem;"></i>
                    <div>
                      <div class="carreras-chip-code"><?= esc($cCode) ?></div>
                      <div class="carreras-chip-count"><?= $cInfo['total_clases'] ?> clases</div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <button type="button" class="btn-primary" onclick="abrirModalHorariosCarreras()" style="width: 100%; padding: 13px; font-weight: 600; font-size: 0.95rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);">
            <i class="bx bx-calendar-edit" style="font-size: 1.25rem;"></i> Actualizar horarios de todas las carreras
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 5: AUDITORÍA DE SEGURIDAD -->
  <div id="tab-auditoria" class="admin-tab-pane">
    <div class="admin-section-box">
      <div class="section-box-header">
        <div>
          <h3>Historial Inmutable de Auditoría</h3>
          <p>Trazabilidad estricta de cada acción administrativa ejecutada en la plataforma.</p>
        </div>
      </div>

      <div class="tabla-responsive-container">
        <table class="tabla-admin">
          <thead>
            <tr>
              <th>Fecha y Hora</th>
              <th>Administrador</th>
              <th>Acción Realizada</th>
              <th>Entidad Afectada</th>
              <th>Detalles</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($auditoria as $log): ?>
              <tr>
                <td><?= !empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime($log['created_at'])) : 'N/D' ?></td>
                <td>
                  <strong>@<?= esc($log['admin_username'] ?? 'Sistema') ?></strong>
                </td>
                <td><span class="badge-accion-tag"><?= esc($log['accion']) ?></span></td>
                <td><?= esc($log['entidad'] ?? 'General') ?> #<?= esc($log['entidad_id'] ?? '-') ?></td>
                <td>
                  <code style="font-size: 0.78rem;"><?= esc($log['detalles'] ?? '{}') ?></code>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- MODALES INTERACTIVOS PARA ACCIONES ADMINISTRATIVAS -->
<!-- Modal: Denegar Profesor con Motivo -->
<div id="modal-denegar-profesor" class="modal-carreras-overlay" style="display: none;">
  <div class="modal-editar-perfil-content" style="max-width: 480px;">
    <div class="modal-editar-header">
      <h3 style="color: #dc2626;"><i class="bx bx-user-x"></i> Denegar Solicitud Docente</h3>
      <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalDenegar()">&times;</button>
    </div>
    <div class="modal-editar-body">
      <p id="denegar-docente-nombre" style="font-weight: 600; margin-bottom: 12px;"></p>
      <label class="form-label">Motivo del rechazo (se notificará al docente):</label>
      <textarea id="motivo-denegar-input" class="input-global" rows="3" placeholder="Ej: El carnet no está vigente o no es legible..."></textarea>
    </div>
    <div class="modal-editar-footer">
      <button type="button" class="btn-cancelar-modal" onclick="cerrarModalDenegar()">Cancelar</button>
      <button type="button" class="btn-accion-admin btn-rechazar" onclick="confirmarDenegarProfesor()"><i class="bx bx-check"></i> Confirmar Rechazo</button>
    </div>
  </div>
</div>

<!-- Modal: Banear Usuario con Motivo -->
<div id="modal-banear-usuario" class="modal-carreras-overlay" style="display: none;">
  <div class="modal-editar-perfil-content" style="max-width: 480px;">
    <div class="modal-editar-header">
      <h3 style="color: #dc2626;"><i class="bx bx-block"></i> Suspender / Banear Usuario</h3>
      <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalBanear()">&times;</button>
    </div>
    <div class="modal-editar-body">
      <p id="banear-usuario-nombre" style="font-weight: 600; margin-bottom: 12px;"></p>
      <label class="form-label">Motivo de la suspensión:</label>
      <textarea id="motivo-banear-input" class="input-global" rows="3" placeholder="Ej: Infracción a los términos de convivencia o documentación falsa..."></textarea>
    </div>
    <div class="modal-editar-footer">
      <button type="button" class="btn-cancelar-modal" onclick="cerrarModalBanear()">Cancelar</button>
      <button type="button" class="btn-accion-admin btn-rechazar" onclick="confirmarBanearUsuario()"><i class="bx bx-block"></i> Aplicar Baneo</button>
    </div>
  </div>
</div>

<!-- Modal: Eliminar Aporte con Motivo -->
<div id="modal-eliminar-aporte" class="modal-carreras-overlay" style="display: none;">
  <div class="modal-editar-perfil-content" style="max-width: 480px;">
    <div class="modal-editar-header">
      <h3 style="color: #dc2626;"><i class="bx bx-trash"></i> Eliminar Publicación / Aporte</h3>
      <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalEliminarAporte()">&times;</button>
    </div>
    <div class="modal-editar-body">
      <p id="eliminar-aporte-titulo" style="font-weight: 600; margin-bottom: 12px;"></p>
      <label class="form-label">Motivo de eliminación:</label>
      <textarea id="motivo-eliminar-aporte-input" class="input-global" rows="3" placeholder="Ej: Contenido inapropiado, documento erróneo o derechos de autor..."></textarea>
    </div>
    <div class="modal-editar-footer">
      <button type="button" class="btn-cancelar-modal" onclick="cerrarModalEliminarAporte()">Cancelar</button>
      <button type="button" class="btn-accion-admin btn-rechazar" onclick="confirmarEliminarAporte()"><i class="bx bx-trash"></i> Eliminar Definitivamente</button>
    </div>
  </div>
</div>



<!-- modal asignar materias a profe -->
<div id="modal-asignar-secciones-docente" class="modal-carreras-overlay" style="display: none; padding: 15px; box-sizing: border-box; z-index: 100000;" onclick="if(event.target === this) cerrarModalAsignarSecciones()">
  <div class="modal-editar-perfil-content" style="max-width: 650px; width: 100%; border-radius: 18px; max-height: calc(100vh - 40px); display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);">
    <div class="modal-editar-header" style="padding: 16px 20px; background: #1e3a8a; color: white; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 1.15rem; color: white; display: flex; align-items: center; gap: 8px;">
        <i class="bx bx-chalkboard"></i> Asignación de Secciones (Aulas)
      </h3>
      <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalAsignarSecciones()" style="color: white; font-size: 1.5rem; background: none; border: none; cursor: pointer;">&times;</button>
    </div>

    <div style="padding: 14px 20px; font-size: 0.9rem;" class="modal-docentes-info-banner">
      Docente Seleccionado: <strong id="docente-asignar-nombre" style="color: #0f172a;"></strong>
    </div>

    <div style="padding: 18px 20px; overflow-y: auto; max-height: 55vh; box-sizing: border-box;">
      <h4 style="margin: 0 0 10px 0; font-size: 0.92rem;">Secciones actualmente asignadas a este docente:</h4>
      <div id="lista-secciones-asignadas" style="margin-bottom: 20px;">
        <div style="text-align: center; padding: 15px; color: #64748b;"><i class="bx bx-loader-alt bx-spin"></i> Cargando...</div>
      </div>

      <h4 style="margin: 0 0 8px 0; font-size: 0.92rem;">Buscar y Vincular Nueva Sección / Asignatura:</h4>
      <div style="position: relative; margin-bottom: 12px;">
        <i class="bx bx-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.15rem;"></i>
        <input type="text" id="input-buscar-seccion" class="input-global" placeholder="Escribe el nombre de la materia o código (ej: Lógica, Cálculo, LOG-101)..." oninput="buscarSeccionesDisponibles(this.value)" style="padding-left: 36px; width: 100%; box-sizing: border-box;">
      </div>

      <div id="lista-secciones-busqueda" style="max-height: 200px; overflow-y: auto;">
        <p style="font-size: 0.82rem; color: #94a3b8; margin: 0;">Escribe arriba para buscar entre las secciones oficiales del campus.</p>
      </div>
    </div>

    <div class="modal-editar-footer" style="padding: 12px 20px; display: flex; justify-content: flex-end;">
      <button type="button" class="btn-cancelar-modal" onclick="cerrarModalAsignarSecciones()">Cerrar</button>
    </div>
  </div>
</div>

<!-- modal subir pdfs carreras -->
<div id="modal-actualizar-horarios-carreras" class="modal-carreras-overlay" style="display: none; padding: 20px; box-sizing: border-box; z-index: 100000; align-items: center; justify-content: center;">
  <div class="modal-editar-perfil-content" style="max-width: 860px; width: 100%; max-height: 90vh; overflow-y: auto; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);">
    <div class="modal-editar-header" style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h3 style="color: #1e3a8a; font-size: 1.25rem; margin: 0; display: flex; align-items: center; gap: 8px;">
          <i class="bx bx-calendar-event" style="color: #2563eb;"></i> Actualizar Horarios de Todas las Carreras
        </h3>
        <p style="font-size: 0.85rem; color: #64748b; margin: 4px 0 0 0;">
          Sube el archivo PDF oficial (Formulario CGD-001) de cada carrera para procesar y reflejar automáticamente el día y la hora exacta de inicio y fin de todas las materias en el mapa.
        </p>
      </div>
      <button type="button" class="btn-cerrar-carreras" onclick="cerrarModalHorariosCarreras()">&times;</button>
    </div>

    <div class="modal-editar-body" style="padding: 20px 24px;">
      <!-- Banner informativo / éxito -->
      <div id="banner-feedback-horarios" style="display: none; margin-bottom: 16px; padding: 12px 16px; border-radius: 10px; font-size: 0.88rem; font-weight: 500;"></div>

      <!-- Lista de tarjetas de las 5 carreras -->
      <div class="carreras-horarios-grid" style="display: flex; flex-direction: column; gap: 14px;">
        <?php if (!empty($carrerasHorarios)): ?>
          <?php foreach ($carrerasHorarios as $codCarrera => $cData): ?>
            <div class="carrera-horario-card" id="card-horario-<?= $codCarrera ?>" style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; background: #ffffff; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; transition: all 0.2s ease;">
              <!-- Info de la carrera -->
              <div style="display: flex; align-items: center; gap: 14px; min-width: 260px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: <?= $cData['color'] ?>15; color: <?= $cData['color'] ?>; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0;">
                  <i class="bx <?= esc($cData['icono']) ?>"></i>
                </div>
                <div>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <h4 style="margin: 0; font-size: 1rem; color: #0f172a; font-weight: 700;"><?= esc($cData['nombre']) ?></h4>
                    <span style="font-size: 0.72rem; font-weight: 700; background: #f1f5f9; color: #475569; padding: 2px 7px; border-radius: 6px;"><?= esc($codCarrera) ?></span>
                  </div>
                  <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;"><?= esc($cData['escuela']) ?></div>
                  <div class="carrera-meta-status" style="margin-top: 5px; font-size: 0.78rem;">
                    <?php if ($cData['total_clases'] > 0): ?>
                      <span class="badge-status-ok" style="color: #059669; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="bx bxs-check-circle"></i> <span id="conteo-clases-<?= $codCarrera ?>"><?= $cData['total_clases'] ?></span> bloques sincronizados
                      </span>
                      <span style="color: #94a3b8; margin: 0 4px;">•</span>
                      <span style="color: #64748b;" id="fecha-sync-<?= $codCarrera ?>"><?= esc($cData['ultima_modificacion']) ?></span>
                    <?php else: ?>
                      <span class="badge-status-empty" style="color: #d97706; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="bx bx-time-five"></i> Sin horario cargado
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Formulario de subida individual -->
              <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <input type="file" id="file-horario-<?= $codCarrera ?>" accept=".pdf" style="display: none;" onchange="onArchivoCarreraSeleccionado('<?= $codCarrera ?>', this)">
                <button type="button" class="btn-seleccionar-pdf" onclick="document.getElementById('file-horario-<?= $codCarrera ?>').click()" style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 600; color: #334155; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                  <i class="bx bx-upload"></i> <span id="label-file-<?= $codCarrera ?>">Seleccionar PDF</span>
                </button>
                <button type="button" class="btn-subir-carrera-accion" id="btn-subir-<?= $codCarrera ?>" onclick="subirHorarioCarrera('<?= $codCarrera ?>')" style="background: <?= $cData['color'] ?>; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; opacity: 0.6;" disabled>
                  <i class="bx bx-sync"></i> Procesar
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="modal-editar-footer" style="padding: 14px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
      <span style="font-size: 0.8rem; color: #64748b;">Los horarios sincronizados se reflejan automáticamente en el mapa y la disponibilidad de aulas.</span>
      <button type="button" class="btn-cancelar-modal" onclick="cerrarModalHorariosCarreras()">Cerrar Ventana</button>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/admin.js?v=' . time()) ?>"></script>
<?= $this->endSection() ?>
