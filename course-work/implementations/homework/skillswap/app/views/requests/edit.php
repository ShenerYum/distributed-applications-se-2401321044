<?php
$title = 'Edit Request';
require __DIR__ . '/../shared/header.php';

assert(!empty($request) && is_array($request));
$r = $request;
?>

<div class="container my-4 p-4 border rounded" style="max-width:760px">
	<h1>Edit Request</h1>

	<form method="post" action="<?= $baseUrl . $basepath ?>requests/<?= norm($r['id']) ?>/edit">
		<div class="mb-3">
			<label class="form-label">Title</label>
			<input type="text" class="form-control" name="title" value="<?= norm($r['title']) ?>" required />
		</div>

		<div class="mb-3">
			<label class="form-label">Desired level</label>
			<input type="text" class="form-control" name="desired_level" value="<?= norm($r['desired_level']) ?>" />
		</div>

		<div class="mb-3">
			<label class="form-label">Notes</label>
			<textarea class="form-control" name="notes"><?= norm($r['notes']) ?></textarea>
		</div>

		<div class="mb-3">
			<label class="form-label">Max hours</label>
			<input type="number" class="form-control" name="max_hours" value="<?= norm($r['max_hours']) ?>" />
		</div>

		<p>
			<button class="btn btn-primary" type="submit">Save</button>
			<a class="btn btn-secondary ms-2" href="<?= $baseUrl . $basepath ?>profile/requests">Cancel</a>
		</p>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>