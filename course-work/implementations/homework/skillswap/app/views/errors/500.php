<?php
$title = '500 Application Error';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<div class="alert alert-danger alert-lg" role="alert">
		<h2>500 Application Error</h2>
		<p class="mb-0"><?= norm($errorMessage ?? 'An internal server error occurred.') ?></p>
	</div>
</div>

<?php require __DIR__ . '/../shared/footer.php';
