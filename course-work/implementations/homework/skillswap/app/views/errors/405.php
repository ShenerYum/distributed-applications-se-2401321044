<?php
$title = '405 Method Not Allowed';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<div class="alert alert-warning alert-lg" role="alert">
		<h2>405 Method Not Allowed</h2>
		<p class="mb-0"><?= norm($errorMessage ?? 'The requested method is not allowed for this resource.') ?></p>
	</div>
</div>

<?php require __DIR__ . '/../shared/footer.php';
