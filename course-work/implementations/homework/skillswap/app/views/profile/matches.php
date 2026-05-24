<?php
$title = 'My Matches';
require __DIR__ . '/../shared/header.php';
?>
<div style="max-width:920px;margin:28px auto">
	<h2>My Matches</h2>
	<?php if (empty($matches)): ?>
		<p>No matches yet.</p>
	<?php else: ?>
		<table>
			<tr>
				<th>Other User</th>
				<th>Their Item</th>
				<th>Your Item</th>
				<th>Status</th>
				<th>Actions</th>
			</tr>
			<?php foreach ($matches as $m):
				$meId = $_SESSION['user_id'] ?? null;
				$meIsUserA = $meId !== null && $meId === ($m['user_a_id'] ?? null);
				$other = $meIsUserA ? ($m['user_b'] ?? []) : ($m['user_a'] ?? []);
				// decide which item belongs to me vs them by checking owner ids
				if (!empty($m['offer']) && (($m['offer']['user_id'] ?? null) === $meId)) {
					$myItem = $m['offer'];
					$theirItem = $m['request'];
				} else {
					$myItem = $m['request'];
					$theirItem = $m['offer'];
				}
			?>
				<tr>
					<td><?= norm($other['name'] ?? $other['email'] ?? 'User') ?></td>
					<td><?= norm($theirItem['title'] ?? $theirItem['description'] ?? $theirItem['notes'] ?? '') ?></td>
					<td><?= norm($myItem['title'] ?? $myItem['description'] ?? $myItem['notes'] ?? '') ?></td>
					<td><?= norm($m['status'] ?? '') ?></td>
					<td>
						<?php if (($m['status'] ?? '') === 'pending'): ?>
							<?php if (!empty($meId) && $meId === ($m['user_b_id'] ?? null)): ?>
								<form method="post" action="?route=matches/accept" style="display:inline">
									<input type="hidden" name="match_id" value="<?= norm($m['id']) ?>">
									<button type="submit">Accept</button>
								</form>
								<form method="post" action="?route=matches/reject" style="display:inline">
									<input type="hidden" name="match_id" value="<?= norm($m['id']) ?>">
									<button type="submit">Reject</button>
								</form>
							<?php else: ?>
								<!-- initiator can cancel the pending match -->
								<form method="post" action="?route=matches/reject" style="display:inline">
									<input type="hidden" name="match_id" value="<?= norm($m['id']) ?>">
									<button type="submit">Cancel</button>
								</form>
							<?php endif; ?>
						<?php elseif (($m['status'] ?? '') === 'rejected'): ?>
							<span class="muted">No actions available</span>
							<!-- Optionally, you could allow the other party to propose a new match or edit the existing one -->
						<?php elseif (($m['status'] ?? '') === 'accepted'): ?>
							<form method="post" action="?route=matches/complete" style="display:inline">
								<input type="hidden" name="match_id" value="<?= norm($m['id']) ?>">
								<button type="submit">Mark Completed</button>
							</form>
						<?php elseif (($m['status'] ?? '') === 'completed'): ?>
							<?php
							// If match completed, allow the participant to make a review if they haven't already
							$meId = $_SESSION['user_id'] ?? null;
							$isParticipant = $meId && (($m['user_a_id'] ?? null) === $meId || ($m['user_b_id'] ?? null) === $meId);
							$myReview = $m['my_review'] ?? null;
							if ($isParticipant && !$myReview):
							?>
								<a class="btn" href="<?= $baseUrl . $basepath ?>reviews/create?match_id=<?= norm($m['id']) ?>">Make review</a>
							<?php elseif ($myReview): ?>
								<a class="btn" href="<?= $baseUrl . $basepath ?>reviews/<?= norm($myReview['id']) ?>/edit">Edit review</a>
								<form method="post" action="<?= $baseUrl . $basepath ?>reviews/<?= norm($myReview['id']) ?>/delete" style="display:inline">
									<button class="btn danger" type="submit" onclick="return confirm('Delete review?')">Delete</button>
								</form>
							<?php endif; ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>">Back to home</a></p>
</div>
<?php require __DIR__ . '/../shared/footer.php'; ?>