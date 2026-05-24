<?php
$title = 'Edit Request';
require __DIR__ . '/../shared/header.php';

$req = $request ?? [];
?>

<div style="max-width:640px;margin:28px auto">
	<h1>Edit Request</h1>

	<?php if (!empty($errors)): ?>
		<div class="errors">
			<?php foreach ($errors as $e): ?>
				<div><?= norm($e) ?></div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?= $baseUrl . $basepath ?>requests/<?= norm($req['id'] ?? '') ?>/edit">
		<div class="form-row">
			<label>Title</label>
			<input type="text" name="title" value="<?= norm($req['title'] ?? '') ?>" required />
		</div>

		<div class="form-row">
			<label>Desired level</label>
			<input type="text" name="desired_level" value="<?= norm($req['desired_level'] ?? '') ?>" />

		</div>

		<div class="form-row">
			<label>Notes</label>
			<textarea name="notes"><?= norm($req['notes'] ?? '') ?></textarea>
		</div>

		<div class="form-row">
			<label>Max hours</label>
			<input type="number" name="max_hours" value="<?= norm($req['max_hours'] ?? '') ?>" />

		</div>

		<p>
			<button class="btn" type="submit">Save</button>
			<a href="<?= $baseUrl . $basepath ?>profile/requests">Cancel</a>
		</p>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>