<?php
$title = 'Create Match';
require __DIR__ . '/../shared/header.php';

assert(isset($target_type) && in_array($target_type, ['offer', 'request'], true), 'Invalid target type');
assert(isset($target) && is_array($target), 'Invalid target data');
assert(isset($candidates) && is_array($candidates), 'Invalid candidates data');
?>

<h2>Create Match</h2>
<?php if (!empty($errors)): ?>
	<div class="errors"><?= implode('<br>', array_map('norm', $errors)) ?></div>
<?php endif; ?>
<div>
	<?php if ($target_type === 'offer'): ?>
		<h3>Target Offer</h3>
		<p>Offer: <?= norm($target['title'] ?? $target['description'] ?? '') ?></p>
		<p>Skill: <?= norm($target['skill_name'] ?? $target['skill_id'] ?? '') ?></p>
	<?php else: ?>
		<h3>Target Request</h3>
		<p>Request: <?= norm($target['title'] ?? $target['notes'] ?? '') ?></p>
		<p>Skill: <?= norm($target['skill_name'] ?? $target['skill_id'] ?? '') ?></p>
	<?php endif; ?>

	<form method="post" action="?route=matches/create">
		<input type="hidden" name="<?= $target_type === 'offer' ? 'offer_id' : 'request_id' ?>" value="<?= norm($target['id'] ?? '') ?>">
		<div class="form-row">
			<label for="my_selection">Select one of your matching <?= $target_type === 'offer' ? 'requests' : 'offers' ?>:</label>
			<select name="my_selection" id="my_selection">
				<?php foreach ($candidates as $c): ?>
					<option value="<?= norm($c['id'] ?? '') ?>"><?= norm($c['title'] ?? $c['description'] ?? ($c['notes'] ?? '')) ?> (Skill: <?= norm($c['skill_name'] ?? $c['skill_id'] ?? '') ?>)</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-row">
			<label for="score">Optional score:</label>
			<input type="number" name="score" id="score" min="0" max="100">
		</div>
		<div style="margin-top:12px">
			<button type="submit">Create Match</button>
			<a class="btn muted" href="<?= $baseUrl . $basepath ?>profile/offers" style="margin-left:8px">Cancel</a>
		</div>
	</form>
</div>
<?php require __DIR__ . '/../shared/footer.php'; ?>