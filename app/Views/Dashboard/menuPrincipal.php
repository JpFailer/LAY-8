<main class="dashboard-contenedor">

  <div class="dashboard-header">
    <h2>Hola, <?= esc(session()->get('primer_nombre') ?? 'Usuario') ?>!</h2>
    <p>¿Qué planes tienes para hoy? Tu rol actual es: <strong><?= esc(ucfirst(session()->get('rol') ?? 'estudiante')) ?></strong>.</p>
  </div>

  <div class="tarjetas-contenedor">
    <a href="<?= base_url('perfil') ?>" class="tarjeta-opcion">
      <img src="<?= base_url('img/comu_icono.png') ?>" alt="Icono Comunidad">
      <span class="tarjeta-titulo">Mi Perfil</span>
    </a>

    <a href="<?= base_url('mapa') ?>" class="tarjeta-opcion">
      <img src="<?= base_url('img/mapa_icono.png') ?>" alt="Icono Mapa">
      <span class="tarjeta-titulo">Mapa UMC</span>
    </a>

    <a href="<?= base_url('biblioteca') ?>" class="tarjeta-opcion">
      <img src="<?= base_url('img/biblio_icono.png') ?>" alt="Icono Biblioteca">
      <span class="tarjeta-titulo">Biblioteca</span>
    </a>
  </div>

</main>
