<?php
$title = 'Edit Offer';
require __DIR__ . '/../shared/header.php';

assert(!empty($offer) && is_array($offer));
$o = $offer;
?>

<div class="container my-4 p-4 border rounded" style="max-width:760px">
	<h1>Edit Offer</h1>

	<form method="post" action="<?= $baseUrl . $basepath ?>offers/<?= norm((string)$o['id']) ?>/edit">

		<div class="mb-3">
			<label class="form-label">Title</label>
			<input type="text" class="form-control" name="title" value="<?= norm($o['title']) ?>" />
		</div>
		<div class="mb-3">
			<label class="form-label">Availability</label>
			<input type="text" class="form-control" name="availability" value="<?= norm($o['availability']) ?>" />
		</div>
		<div class="mb-3">
			<label class="form-label">Description</label>
			<textarea class="form-control" name="description"><?= norm($o['description']) ?></textarea>
		</div>
		<div class="mb-3 form-check">
			<input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= isset($o['is_active']) && $o['is_active'] ? 'checked' : '' ?> />
			<label class="form-check-label" for="is_active">Active</label>
		</div>
		<div class="mt-3">
			<button class="btn btn-primary" type="submit">Save</button>
			<a class="btn btn-secondary ms-2" href="<?= $baseUrl . $basepath ?>profile/offers" style="margin-left:8px">Cancel</a>
		</div>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>