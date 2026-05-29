<?php
$title = '501 Not Implemented';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<div class="alert alert-danger alert-lg" role="alert">
		<h2>501 Not Implemented</h2>
		<p class="mb-0"><?= norm($errorMessage ?? 'This functionality is not implemented on the server.') ?></p>
	</div>
</div>

<?php require __DIR__ . '/../shared/footer.php';
