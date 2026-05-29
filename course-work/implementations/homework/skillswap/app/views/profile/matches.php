<?php
$title = 'My Matches';
require __DIR__ . '/../shared/header.php';

$meId = $_SESSION['user_data']['id'] ?? null;
?>

<div class="container my-4">
	<h1>My Matches</h1>

	<?php if (empty($matches)): ?>
		<div class="alert alert-info">No matches yet.</div>
	<?php else: ?>
		<table class="table table-striped table-hover">
			<thead class="table-light">
				<tr>
					<th>Other User</th>
					<th>Requested</th>
					<th>Offered</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($matches as $m):
					$meIsUserA = ($_SESSION['user_data']['id'] ?? null) === ($m['user_a_id']);

					$me = $meIsUserA ? $m['user_a_name'] : $m['user_b_name'];
					$otherId = $meIsUserA ? $m['user_b_id'] : $m['user_a_id'];
					$other = $meIsUserA ? ($m['user_b_name']) : ($m['user_a_name']);

					$offer = $m['offer_title'];
					$request = $m['request_title'];
				?>
					<tr>
						<td><a href="<?= $baseUrl . $basepath ?>users/<?= norm($otherId) ?>"><?= norm($other) ?></a></td>
						<td><?= norm($request ?? '') ?></td>
						<td><?= norm($offer ?? '') ?></td>
						<td>
							<span class="badge bg-secondary"><?= norm($m['status'] ?? '') ?></span>
						</td>
						<td>
							<?php if (($m['status'] ?? '') === 'pending'): ?>
								<?php if (!empty($meId) && $meId === ($m['user_b_id'] ?? null)): ?>
									<form method="post" action="<?= $baseUrl . $basepath ?>matches/accept" style="display:inline;">
										<input type="hidden" name="match_id" value="<?= norm($m['id']) ?>">
										<button class="btn btn-sm btn-success" type="submit">Accept</button>
									</form>
									<form method="post" action="<?= $baseUrl . $basepath ?>matches/reject" style="display:inline;">
										<input type="hidden" name="match_id" value="<?= norm($m['id']) ?>">
										<button class="btn btn-sm btn-danger" type="submit">Reject</button>
									</form>
								<?php else: ?>
									<form method="post" action="<?= $baseUrl . $basepath ?>matches/reject" style="display:inline;">
										<input type="hidden" name="match_id" value="<?= norm($m['id']) ?>">
										<button class="btn btn-sm btn-warning" type="submit">Cancel</button>
									</form>
								<?php endif; ?>
							<?php elseif (($m['status'] ?? '') === 'rejected'): ?>
								<span class="text-muted">No actions available</span>
							<?php elseif (($m['status'] ?? '') === 'accepted'): ?>
								<form method="post" action="<?= $baseUrl . $basepath ?>matches/complete" style="display:inline;">
									<input type="hidden" name="match_id" value="<?= norm($m['id']) ?>">
									<button class="btn btn-sm btn-primary" type="submit">Mark Completed</button>
								</form>
							<?php elseif (($m['status'] ?? '') === 'completed'): ?>
								<?php
								$meId = $_SESSION['user_id'] ?? null;
								$isParticipant = $meId && (($m['user_a_id'] ?? null) === $meId || ($m['user_b_id'] ?? null) === $meId);
								$myReview = $m['my_review'] ?? null;
								if ($isParticipant && !$myReview):
								?>
									<a class="btn btn-sm btn-info" href="<?= $baseUrl . $basepath ?>reviews/create?match_id=<?= norm($m['id']) ?>">Make review</a>
								<?php elseif ($myReview): ?>
									<a class="btn btn-sm btn-warning" href="<?= $baseUrl . $basepath ?>reviews/<?= norm($myReview['id']) ?>/edit">Edit review</a>
									<form method="post" action="<?= $baseUrl . $basepath ?>reviews/<?= norm($myReview['id']) ?>/delete" style="display:inline;">
										<button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete review?')">Delete</button>
									</form>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<div class="mt-3"><a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath; ?>">Back to home</a></div>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>