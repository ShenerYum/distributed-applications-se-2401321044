<?php
$title = 'Create Request';
require __DIR__ . '/../shared/header.php';
assert(!empty($skill_id));
?>

<div class="container my-4 p-4 border rounded" style="max-width:760px">
	<h1>Create Request</h1>

	<form method="post" action="<?= $baseUrl . $basepath ?>requests/create">
		<input type="hidden" name="skill_id" value="<?= norm((string)$skill_id) ?>" />


		<div class="mb-3">
			<label class="form-label">Title</label>
			<input type="text" class="form-control" name="title" value="<?= norm($_POST['title'] ?? '') ?>" required />
		</div>

		<div class="mb-3">
			<label class="form-label">Desired level</label>
			<input type="text" class="form-control" name="desired_level" value="<?= norm($_POST['desired_level'] ?? '') ?>" />
		</div>

		<div class="mb-3">
			<label class="form-label">Notes</label>
			<textarea class="form-control" name="notes"><?= norm($_POST['notes'] ?? '') ?></textarea>
		</div>

		<div class="mb-3">
			<label class="form-label">Max hours</label>
			<input type="number" class="form-control" name="max_hours" value="<?= norm($_POST['max_hours'] ?? '') ?>" />
		</div>

		<p>
			<button class="btn btn-primary" type="submit">Create</button>
			<a class="btn btn-secondary ms-2" href="<?= $baseUrl . $basepath ?>requests">Cancel</a>
		</p>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>