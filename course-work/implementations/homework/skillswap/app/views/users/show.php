<?php

$isAdmin = isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;

require __DIR__ . '/../shared/header.php';

$title = (!empty($user) && is_array($user)) ? 'User - ' . norm($user['name']) : 'User Profile';
?>

<div style="max-width:760px;margin:36px auto;padding:18px;border:1px solid #eee;border-radius:6px">
	<h1>User profile</h1>

	<?php if (!empty($user) && is_array($user)): ?>
		<table>
			<tbody>
				<?php foreach ($user as $key => $value):
					if ($key === 'password') continue;
					if ($key === 'is_admin' && !$isAdmin) continue;
				?>
					<tr>
						<th><?= norm(ucwords(str_replace('_', ' ', $key))) ?></th>

						<td>
							<?php
							if (is_null($value)) {
								echo '<span class="muted">(not set)</span>';
							} else {
								// Link rating to profile reviews
								if ($key === 'rating') {
									echo '<a href="' . $baseUrl . $basepath . 'users/' . norm($user['id']) . '/reviews">' . norm((string)$value) . '</a>';
								} else {
									echo norm((string)$value);
								}
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div style="margin-top:18px;">
			<a class="btn" href="<?= $baseUrl . $basepath ?>users">Back to users</a>

			<p style="margin-top:12px">
				<a class="btn" href="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/offers">Offers</a>
				<a class="btn" href="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/requests" style="margin-left:8px">Requests</a>
			</p>

			<?php if ($isAdmin): ?>
				<a class="btn" href="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/edit" style="margin-left:12px;">Edit user</a>

				<form method="post" action="<?= $baseUrl . $basepath ?>users/<?= norm($user['id']) ?>/delete" style="display:inline-block;margin-left:12px;">
					<button class="btn danger" type="submit" onclick="return confirm('Delete this user?');">Delete user</button>
				</form>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<p>User not found.</p>

		<div style="margin-top:18px;">
			<a class="btn" href="<?= $baseUrl . $basepath ?>users">Back to users</a>
		</div>
	<?php endif; ?>


</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>