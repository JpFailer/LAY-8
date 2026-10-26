<?= $this->extend('Dashboard/main_view') ?>

<?= $this->section('content') ?>
<div class="perfil-pagina-centrada">
    <!-- Alertas Flash -->
    <?php if(session()->getFlashdata('mensaje')): ?>
        <div class="alerta-notif alerta-exito" style="max-width: 580px; margin: 0 auto 20px auto;">
            <i class="bx bx-check-circle" style="font-size: 1.3rem;"></i>
            <span><?= session()->getFlashdata('mensaje') ?></span>
        </div>
    <?php endif; ?>
    
    <?php if(session()->getFlashdata('error')): ?>
        <div class="alerta-notif alerta-error" style="max-width: 580px; margin: 0 auto 20px auto;">
            <i class="bx bx-error-circle" style="font-size: 1.3rem;"></i>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>

    <!-- Tarjeta de Perfil idéntica al modal y al diseño de referencia -->
    <div class="perfil-card-standalone">
        <!-- Banner decorativo superior -->
        <div class="perfil-card-banner"></div>

        <div class="perfil-card-inner">
            <div class="perfil-user-header">
                <div class="perfil-avatar-wrapper">
                    <?php if (!empty($usuario['foto_perfil'])): ?>
                        <img src="<?= base_url($usuario['foto_perfil']) ?>" class="perfil-card-avatar" alt="Foto de perfil">
                    <?php else: ?>
                        <img src="<?= base_url('img/usuario.png') ?>" class="perfil-card-avatar" alt="Foto de perfil">
                    <?php endif; ?>
                </div>

                <div class="perfil-user-meta">
                    <div class="perfil-name-badge-row">
                        <h2 class="perfil-user-name"><?= esc($usuario['primer_nombre'] . ' ' . $usuario['apellidos']) ?></h2>
                        <span class="perfil-pill-badge"><?= ucfirst(esc($usuario['rol'])) ?></span>
                    </div>
                    <div class="perfil-user-handle">@<?= esc($usuario['nombre_usuario']) ?></div>
                </div>
            </div>

            <!-- Línea azul divisoria continua -->
            <div class="perfil-blue-divider"></div>

            <!-- Grilla 2x2 de Información -->
            <div class="perfil-info-grid">
                <div class="perfil-grid-cell">
                    <span class="grid-label">Carrera</span>
                    <span class="grid-value"><?= ($usuario['rol'] === 'profesor') ? 'Docente UMC' : esc($usuario['carrera'] ?? 'ING-INF') ?></span>
                </div>

                <div class="perfil-grid-cell">
                    <span class="grid-label">Contacto</span>
                    <span class="grid-value" title="<?= esc($usuario['correo'] ?? '') ?>"><?= esc($usuario['correo'] ?? 'No disponible') ?></span>
                </div>

                <div class="perfil-grid-cell">
                    <span class="grid-label">Aportes</span>
                    <span class="grid-value"><?= (int)($total_aportes ?? 0) ?> <?= ($total_aportes == 1) ? 'recurso' : 'recursos' ?></span>
                </div>

                <div class="perfil-grid-cell">
                    <span class="grid-label">Estado</span>
                    <span class="grid-value"><i class="bx bxs-check-circle" style="color:#10b981; font-size:1.05rem; vertical-align:middle;"></i> <?= ucfirst(esc($usuario['estado_cuenta'] ?? 'Activo')) ?></span>
                </div>
            </div>

            <!-- Pie con botón de Editar perfil -->
            <div class="perfil-card-bottom">
                <button type="button" class="btn-editar-perfil-trigger" onclick="abrirModalEditarPerfil()">
                    <i class="bx bx-edit-alt"></i> Editar perfil
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
