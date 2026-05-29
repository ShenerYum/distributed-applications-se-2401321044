<?php
$title = '403 Forbidden';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<div class="alert alert-danger alert-lg" role="alert">
		<h2>403 Forbidden</h2>
		<p class="mb-0"><?= norm($errorMessage ?? 'You do not have permission to access this resource.') ?></p>
	</div>
</div>

<?php require __DIR__ . '/../shared/footer.php';
