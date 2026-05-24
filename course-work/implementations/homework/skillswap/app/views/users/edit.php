<?php

require __DIR__ . '/../shared/header.php';

assert(!empty($user) && is_array($user));

$title = 'Edit User - ' . norm($user['name']);
?>

<div class="profile-edit" style="max-width:760px;margin:36px auto;padding:18px;border:1px solid #eee;border-radius:6px">
	<h1>Edit User</h1>

	<?php if (!empty($errors) && is_array($errors)): ?>
		<div class="errors">
			<ul>
				<?php foreach ($errors as $err): ?>
					<li><?= norm($err) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form method="post" action="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/edit">
		<div class="form-row">
			<label>Name</label>
			<input type="text" name="name" value="<?= norm($user['name']) ?>" />
		</div>
		<div class="form-row">
			<label>Email</label>
			<input type="email" name="email" value="<?= norm($user['email']) ?>" />
		</div>
		<div class="form-row">
			<label>Administrator</label>
			<input type="checkbox" name="is_admin" value="1" <?= (!empty($user['is_admin']) && $user['is_admin'] === 1) ? 'checked' : '' ?> />
		</div>
		<div class="form-row">
			<label>New Password (leave blank to keep)</label>
			<input type="password" name="password" />
		</div>
		<div class="form-row">
			<label>Confirm Password</label>
			<input type="password" name="password_confirm" />
		</div>

		<div style="margin-top:12px">
			<button class="btn" type="submit">Save changes</button>
			<a class="btn muted" href="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>" style="margin-left:8px">Cancel</a>
		</div>
	</form>

</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>