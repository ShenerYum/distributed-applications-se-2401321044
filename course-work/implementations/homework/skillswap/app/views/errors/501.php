<?php
$title = '501 Not Implemented';
require __DIR__ . '/../shared/header.php';
?>

<h2>501 Not Implemented</h2>
<p><?= norm($errorMessage ?? 'This functionality is not implemented on the server.') ?></p>

<?php require __DIR__ . '/../shared/footer.php';
