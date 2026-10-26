<?= $this->extend('Dashboard/main_view')?>

<?= $this->section('content') ?>
    <h2>Hola, <?= session()->get('primer_nombre'); ?>!</h2>
    <p>¿Qué planes tienes para hoy?</p>
<?= $this->endSection() ?>
