<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PRiADI Fingertest demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.datatables.net/v/bs5/dt-2.3.6/datatables.min.css" rel="stylesheet" integrity="sha384-Op52dEl5kUgSEZdHZBipbmlFw81qZygnw1QZv+p1KFhUsirA7OJQnkaHgcJmXCTj" crossorigin="anonymous">
    <?= $this->renderSection('style') ?>
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container">
            <a class="navbar-brand" href="<?= route_to('/') ?>">Fingerprint Taking</a>
            <div class="d-flex gap-3">
                <a class="nav-link" href="<?= route_to('group') ?>">Group</a>
            </div>
        </div>
    </nav>

    <div class="container my-3">
        <?= $this->renderSection('content') ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.3.6/datatables.min.js" integrity="sha384-kbj0kfdGeXuGxFs602DcfnL0cwxrpYR1MK4bZpH5ORM44q7KnoAa83jyxZs3QF1d" crossorigin="anonymous"></script>
    <?= $this->renderSection('script') ?>
</body>

</html>