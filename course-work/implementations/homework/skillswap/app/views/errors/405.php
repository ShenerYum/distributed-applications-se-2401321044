<?php
$title = '405 Method Not Allowed';
require __DIR__ . '/../shared/header.php';
?>

<h2>405 Method Not Allowed</h2>
<p><?= norm($errorMessage ?? 'The requested method is not allowed for this resource.') ?></p>

<?php require __DIR__ . '/../shared/footer.php';
