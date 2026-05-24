<?php
$title = 'Create Review';
require __DIR__ . '/../shared/header.php';
?>

<h2>Create Review</h2>
<form method="post" action="<?= $baseUrl . $basepath ?>reviews/create">
	<input type="hidden" name="match_id" value="<?= norm($match_id ?? '') ?>">
	<div class="form-row">
		<label for="rating">Rating (1-5)</label>
		<input type="number" name="rating" id="rating" min="1" max="5" required>
	</div>
	<div class="form-row">
		<label for="feedback">Feedback</label>
		<textarea name="feedback" id="feedback"></textarea>
	</div>
	<div style="margin-top:12px">
		<button type="submit">Submit Review</button>
		<a class="btn muted" href="<?= $baseUrl . $basepath ?>profile/matches">Cancel</a>
	</div>
</form>

<?php require __DIR__ . '/../shared/footer.php'; ?>