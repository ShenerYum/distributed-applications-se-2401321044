<?php
$title = '404 Not Found';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<div class="alert alert-info alert-lg" role="alert">
		<h2>404 Not Found</h2>
		<p class="mb-0"><?= norm($errorMessage ?? 'The requested resource could not be found.') ?></p>
	</div>
</div>

<?php require __DIR__ . '/../shared/footer.php';
