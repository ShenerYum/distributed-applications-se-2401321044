<?php
$title = '400 Bad Request';
require __DIR__ . '/../shared/header.php';
?>

<h2>400 Bad Request</h2>
<p><?= norm($errorMessage ?? 'The request could not be understood by the server.') ?></p>

<?php require __DIR__ . '/../shared/footer.php';
