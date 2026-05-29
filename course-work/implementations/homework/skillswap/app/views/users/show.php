<?php

$title = (!empty($user) && is_array($user)) ? 'User - ' . htmlspecialchars($user['name']) : 'User Profile';
require __DIR__ . '/../shared/header.php';

$isAdmin = isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;
?>

<div class="container my-4" style="max-width:760px">
	<div class="card shadow-sm">
		<div class="card-body">
			<h1 class="card-title mb-4">User Profile</h1>

			<?php if (!empty($user) && is_array($user)): ?>
				<table class="table table-striped table-hover">
					<tbody>
						<?php foreach ($user as $key => $value):
							switch ($key) {
								case 'id':
								case 'is_admin':
								case 'created_at':
									if (!$isAdmin) continue 2; // Skip this field for non-admins
									break;
								case 'password':
									continue 2; // Skip password field for everyone
							}
						?>
							<tr>
								<th><?= norm(ucwords(str_replace('_', ' ', $key))) ?></th>
								<td>
									<?php
									if (is_null($value)) {
										echo '<span class="muted">(not set)</span>';
									} else if ($key === 'rating') {
										echo '<a href="' . $baseUrl . $basepath . 'users/' . norm($user['id']) . '/reviews">' . norm((string)$value) . '</a>';
									} else {
										echo norm((string)$value);
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div class="mt-4">
					<div class="d-flex flex-column gap-2">
						<a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/offers">Offers</a>
						<a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/requests">Requests</a>

					</div>

					<?php if ($isAdmin): ?>
						<div class="d-flex gap-2 mt-3">
							<a class="btn btn-warning" href="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/edit">Edit user</a>
							<form method="post" action="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/delete" style="display:inline-block;">
								<button class="btn btn-danger mx-auto" type="submit" onclick="return confirm('Delete this user?');">Delete user</button>
							</form>
						</div>
					<?php endif; ?>
					<p class="mt-3 mb-0"><a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath ?>users">Back to users</a></p>
				</div>
			<?php else: ?>
				<p>User not found.</p>
				<p class="mt-3 mb-0"><a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath ?>users">Back to users</a></p>
			<?php endif; ?>


		</div>
	</div>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>