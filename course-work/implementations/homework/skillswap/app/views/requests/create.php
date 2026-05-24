<?php
$title = 'Create Request';
require __DIR__ . '/../shared/header.php';
?>

<div style="max-width:640px;margin:28px auto">
	<h1>Create Request</h1>

	<?php if (!empty($errors)): ?>
		<div class="errors">
			<?php foreach ($errors as $e): ?>
				<div><?= norm($e) ?></div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?= $baseUrl . $basepath ?>requests/create">
		<input type="hidden" name="skill_id" value="<?= norm($skill_id ?? '') ?>" />

		<div class="form-row">
			<label>Title</label>
			<input type="text" name="title" value="<?= norm($_POST['title'] ?? '') ?>" required />
		</div>

		<div class="form-row">
			<label>Desired level</label>
			<input type="text" name="desired_level" value="<?= norm($_POST['desired_level'] ?? '') ?>" />
		</div>

		<div class="form-row">
			<label>Notes</label>
			<textarea name="notes"><?= norm($_POST['notes'] ?? '') ?></textarea>
		</div>

		<div class="form-row">
			<label>Max hours</label>
			<input type="number" name="max_hours" value="<?= norm($_POST['max_hours'] ?? '') ?>" />
		</div>

		<p>
			<button class="btn" type="submit">Create</button>
			<a href="<?= $baseUrl . $basepath ?>requests">Cancel</a>
		</p>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>