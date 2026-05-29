<?php
$title = '400 Bad Request';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<div class="alert alert-warning alert-lg" role="alert">
		<h2>400 Bad Request</h2>
		<p class="mb-0"><?= norm($errorMessage ?? 'The request could not be understood by the server.') ?></p>
	</div>
</div>

<?php require __DIR__ . '/../shared/footer.php';
