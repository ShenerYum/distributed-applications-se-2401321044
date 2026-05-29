<?php
$title = 'Create Offer';
require __DIR__ . '/../shared/header.php';
assert(!empty($skill_id));
?>

<div class="container my-4 p-4 border rounded" style="max-width:760px">
	<h1>Create Offer</h1>

	<form method="post" action="<?= $baseUrl . $basepath ?>offers/create">
		<input type="hidden" name="skill_id" value="<?= norm((string)$skill_id) ?>" />

		<div class="mb-3">
			<label class="form-label">Title</label>
			<input type="text" class="form-control" name="title" value="<?= norm($_POST['title'] ?? '') ?>" />
		</div>
		<div class="mb-3">
			<label class="form-label">Availability</label>
			<input type="text" class="form-control" name="availability" value="<?= norm($_POST['availability'] ?? '') ?>" />
		</div>
		<div class="mb-3">
			<label class="form-label">Description</label>
			<textarea class="form-control" name="description"><?= norm($_POST['description'] ?? '') ?></textarea>
		</div>
		<div class="mt-3">
			<button class="btn btn-primary" type="submit">Create</button>
			<a class="btn btn-secondary ms-2" href="<?= $baseUrl . $basepath ?>profile/offers" style="margin-left:8px">Cancel</a>
		</div>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>