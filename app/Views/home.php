<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<p class="text-center display-1 fw-bold mb-5">Welcome!</p>
<div class="d-flex gap-3">
    <div class="card w-25">
        <a href="<?= route_to('group') ?>" class="text-decoration-none fw-semibold text-dark">
            <div class="card-body">
                Group
            </div>
        </a>
    </div>
</div>
<?= $this->endsection() ?>