<?php

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

$title = 'Profile';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4" style="max-width:760px">
	<div class="card shadow-sm">
		<div class="card-body">
			<h1 class="card-title mb-4">Your Profile</h1>

			<?php if (!empty($user) && is_array($user)): ?>
				<table class="table table-striped table-hover">
					<tbody>

						<?php
						foreach ($user as $key => $value):
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
										echo '<a href="' . $baseUrl . $basepath . 'profile/reviews">' . norm((string)$value) . '</a>';
									} else {
										echo norm((string)$value);
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


			<div class="mt-3">
				<a class="btn btn-primary" href="<?= $baseUrl . $basepath ?>profile/edit">Edit profile</a>

				<form method="post" action="<?= $baseUrl . $basepath ?>profile/delete" style="display:inline-block;margin-left:12px;">
					<button class="btn btn-danger" type="submit" onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.');">Delete profile</button>
				</form>

				<div class="mt-3 d-flex flex-column gap-2">
					<?php if (!$isAdmin): ?>
						<a href="<?= $baseUrl . $basepath; ?>profile/offers" class="btn btn-outline-secondary">My Offers</a>
						<a href="<?= $baseUrl . $basepath; ?>profile/requests" class="btn btn-outline-secondary">My Requests</a>
						<a href="<?= $baseUrl . $basepath; ?>profile/matches" class="btn btn-outline-secondary">My Matches</a>
					<?php endif; ?>



					<p class="mt-3 mb-0"><a href="<?= $baseUrl . $basepath; ?>" class="btn btn-outline-secondary">Back to home</a></p>
				</div>
			</div>
		</div>


		<?php require __DIR__ . '/../shared/footer.php'; ?>