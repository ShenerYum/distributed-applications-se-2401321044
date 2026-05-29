<?php
$title = 'Create Skill';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4 p-4 border rounded" style="max-width:760px">
	<h1>Create Skill</h1>

	<form method="post" action="<?= $baseUrl . $basepath ?>skills/create">
		<div class="mb-3">
			<label class="form-label">Name</label>
			<input type="text" class="form-control" name="name" value="<?= norm($_POST['name'] ?? '') ?>" required />
		</div>
		<div class="mb-3">
			<label class="form-label">Category</label>
			<input type="text" class="form-control" name="category" value="<?= norm($_POST['category'] ?? '') ?>" />
		</div>
		<div class="mb-3">
			<label class="form-label">Description</label>
			<textarea class="form-control" name="description"><?= norm($_POST['description'] ?? '') ?></textarea>
		</div>
		<div class="mb-3">
			<label class="form-label">Difficulty</label>
			<input type="text" class="form-control" name="difficulty" value="<?= norm($_POST['difficulty'] ?? '') ?>" />
		</div>
		<div class="mb-3 form-check">
			<input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= (!empty($_POST['is_active']) ? 'checked' : '') ?> />
			<label class="form-check-label" for="is_active">Active</label>
		</div>
		<div class="mt-3">
			<button class="btn btn-primary" type="submit">Create</button>
			<a class="btn btn-secondary ms-2" href="<?= $baseUrl . $basepath ?>skills">Cancel</a>
		</div>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>