<?php
$title = '404 Not Found';
require __DIR__ . '/../shared/header.php';
?>

<h2>404 Not Found</h2>
<p><?= norm($errorMessage ?? 'The requested resource could not be found.') ?></p>

<?php require __DIR__ . '/../shared/footer.php';
