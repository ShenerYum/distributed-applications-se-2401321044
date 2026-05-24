<?php
$title = 'Edit Review';
require __DIR__ . '/../shared/header.php';
assert(isset($review) && is_array($review));
?>

<h2>Edit Review</h2>
<form method="post" action="<?= $baseUrl . $basepath ?>reviews/<?= norm($review['id'] ?? '') ?>/edit">
	<div class="form-row">
		<label for="rating">Rating (1-5)</label>
		<input type="number" name="rating" id="rating" min="1" max="5" value="<?= norm((string)($review['rating'] ?? '')) ?>" required>
	</div>
	<div class="form-row">
		<label for="feedback">Feedback</label>
		<textarea name="feedback" id="feedback"><?= norm($review['feedback'] ?? '') ?></textarea>
	</div>
	<div style="margin-top:12px">
		<button type="submit">Update Review</button>
		<a class="btn muted" href="<?= $baseUrl . $basepath ?>profile/matches">Cancel</a>
	</div>
</form>

<?php require __DIR__ . '/../shared/footer.php'; ?>