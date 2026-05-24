<?php
$title = 'Create Offer';
require __DIR__ . '/../shared/header.php';
?>

<div style="max-width:760px;margin:36px auto;padding:18px;border:1px solid #eee;border-radius:6px">
	<h1>Create Offer</h1>

	<?php if (!empty($errors) && is_array($errors)): ?>
		<div class="errors">
			<ul><?php foreach ($errors as $e) echo '<li>' . norm($e) . '</li>'; ?></ul>
		</div>
	<?php endif; ?>

	<form method="post" action="<?= $baseUrl . $basepath ?>offers/create">
		<input type="hidden" name="skill_id" value="<?= norm($skill_id ?? '') ?>" />
		<div class="form-row">
			<label>Title</label>
			<input type="text" name="title" value="<?= norm($_POST['title'] ?? '') ?>" />
		</div>
		<div class="form-row">
			<label>Availability</label>
			<input type="text" name="availability" value="<?= norm($_POST['availability'] ?? '') ?>" />
		</div>
		<div class="form-row">
			<label>Description</label>
			<textarea name="description"><?= norm($_POST['description'] ?? '') ?></textarea>
		</div>
		<div style="margin-top:12px">
			<button class="btn" type="submit">Create</button>
			<a class="btn muted" href="<?= $baseUrl . $basepath ?>profile/offers" style="margin-left:8px">Cancel</a>
		</div>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>