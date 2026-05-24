<?php

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

$title = 'Profile';
require __DIR__ . '/../shared/header.php';
?>

<div class="profile" style="max-width:760px;margin:36px auto;padding:18px;border:1px solid #eee;border-radius:6px">
	<h1>Your Profile</h1>
	<?php if (!empty($user) && is_array($user)): ?>
		<table>
			<tbody>
				<?php
				foreach ($user as $key => $value):
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
									echo '<a href="' . $baseUrl . $basepath . 'profile/reviews">' . norm((string)$value) . '</a>';
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
	<?php else: ?>
		<p>No user data available.</p>
	<?php endif; ?>

	<div style="margin-top:18px;">
		<a class="btn" href="<?= $baseUrl . $basepath ?>profile/edit">Edit profile</a>

		<form method="post" action="<?= $baseUrl . $basepath ?>profile/delete" style="display:inline-block;margin-left:12px;">
			<button class="btn danger" type="submit" onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.');">Delete profile</button>
		</form>

		<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>profile/offers">My Offers</a></p>
		<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>profile/requests">My Requests</a></p>
		<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>profile/matches">My Matches</a></p>

		<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>">Back to home</a></p>
	</div>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>