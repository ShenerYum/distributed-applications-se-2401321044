<?php

require __DIR__ . '/../shared/header.php';

$title = 'Edit Profile';

assert(!empty($user) && is_array($user));
?>

<div class="container my-4" style="max-width:760px">
	<div class="card shadow-sm">
		<div class="card-body">
			<h1 class="card-title mb-4">Edit Profile</h1>

			<form method="post" action="<?= $baseUrl . $basepath ?>profile/edit">
				<div class="mb-3">
					<label class="form-label" for="name">Name</label>
					<input id="name" class="form-control" type="text" name="name" value="<?= norm($user['name']) ?>" />
				</div>

				<div class="mb-3">
					<label class="form-label" for="email">Email</label>
					<input id="email" class="form-control" type="email" name="email" value="<?= norm($user['email']) ?>" />
				</div>

				<div class="mb-3">
					<label class="form-label" for="password">New Password (leave blank to keep)</label>
					<input id="password" class="form-control" type="password" name="password" />
				</div>

				<div class="mb-3">
					<label class="form-label" for="password_confirm">Confirm Password</label>
					<input id="password_confirm" class="form-control" type="password" name="password_confirm" />
				</div>

				<div class="mt-3 d-flex gap-2 align-items-center">
					<button class="btn btn-primary" type="submit">Save changes</button>
					<a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath ?>profile">Cancel</a>
				</div>
			</form>
		</div>
	</div>
</div>


<?php require __DIR__ . '/../shared/footer.php'; ?>