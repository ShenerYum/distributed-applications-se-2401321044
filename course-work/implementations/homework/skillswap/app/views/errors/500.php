<?php
$title = '500 Application Error';
require __DIR__ . '/../shared/header.php';
?>

<h2>500 Application Error</h2>
<p><?= norm($errorMessage ?? 'An internal server error occurred.') ?></p>

<?php require __DIR__ . '/../shared/footer.php';
