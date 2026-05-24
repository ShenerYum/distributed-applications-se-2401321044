<?php
$title = '403 Forbidden';
require __DIR__ . '/../shared/header.php';
?>

<h2>403 Forbidden</h2>
<p><?= norm($errorMessage ?? 'You do not have permission to access this resource.') ?></p>

<?php require __DIR__ . '/../shared/footer.php';
