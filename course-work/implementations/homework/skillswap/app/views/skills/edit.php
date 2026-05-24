<?php

require __DIR__ . '/../shared/header.php';

$title = 'Edit Skill';

assert(!empty($skill) && is_array($skill));
?>

<div style="max-width:760px;margin:36px auto;padding:18px;border:1px solid #eee;border-radius:6px">
	<h1>Edit Skill</h1>

	<?php if (!empty($errors) && is_array($errors)): ?>
		<div class="errors">
			<ul><?php foreach ($errors as $e) echo '<li>' . norm($e) . '</li>'; ?></ul>
		</div>
	<?php endif; ?>

	<form method="post" action="<?= $baseUrl . $basepath ?>skills/<?= norm($skill['id']) ?>/edit">
		<div class="form-row">
			<label>Name</label>
			<input type="text" name="name" value="<?= norm($skill['name'] ?? '') ?>" />
		</div>
		<div class="form-row">
			<label>Category</label>
			<input type="text" name="category" value="<?= norm($skill['category'] ?? '') ?>" />
		</div>
		<div class="form-row">
			<label>Description</label>
			<textarea name="description"><?= norm($skill['description'] ?? '') ?></textarea>
		</div>
		<div style="margin-top:12px">
			<button class="btn" type="submit">Save</button>
			<a class="btn muted" href="<?= $baseUrl . $basepath ?>skills" style="margin-left:8px">Cancel</a>
		</div>
	</form>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>