<?= $this->extend('dashboard/main_view')?>

<?= $this->section('content') ?>
<div class="content-box">
    <h2>Hola, <?= session()->get('primer_nombre'); ?>!</h2>
    <p>¿Qué planes tienes para hoy?</p>
</div>
<?= $this->endSection() ?>