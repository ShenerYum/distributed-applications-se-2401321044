<?php

$title = 'Edit Skill';
require __DIR__ . '/../shared/header.php';

assert(!empty($skill) && is_array($skill));
?>

<div class="container my-4 p-4 border rounded" style="max-width:760px">
	<h1>Edit Skill</h1>

	<?php if (!empty($errors) && is_array($errors)): ?>
		<?php foreach ($errors as $e): ?>
			<div class="alert alert-danger alert-dismissible fade show" role="alert">
				<?= norm($e) ?>
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

	<form method="post" action="<?= $baseUrl . $basepath ?>skills/<?= norm($skill['id']) ?>/edit">
		<div class="mb-3">
			<label class="form-label">Name</label>
			<input type="text" class="form-control" name="name" value="<?= norm($skill['name'] ?? '') ?>" required />
		</div>
		<div class="mb-3">
			<label class="form-label">Category</label>
			<input type="text" class="form-control" name="category" value="<?= norm($skill['category'] ?? '') ?>" />
		</div>
		<div class="mb-3">
			<label class="form-label">Description</label>
			<textarea class="form-control" name="description"><?= norm($skill['description'] ?? '') ?></textarea>
		</div>
		<div class="mb-3">
			<label class="form-label">Difficulty</label>
			<input type="text" class="form-control" name="difficulty" value="<?= norm($skill['difficulty'] ?? '') ?>" />
		</div>
		<div class="mb-3 form-check">
			<input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= (!empty($skill['is_active']) ? 'checked' : '') ?> />
			<label class="form-check-label" for="is_active">Active</label>
		</div>
		<div class="mt-3">
			<button class="btn btn-primary" type="submit">Save</button>
			<a class="btn btn-secondary ms-2" href="<?= $baseUrl . $basepath ?>skills">Cancel</a>
		</div>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>