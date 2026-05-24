<?php
$title = 'My Reviews';
require __DIR__ . '/../shared/header.php';
?>

<h2>Reviews About You</h2>
<?php if (empty($reviews)): ?>
	<p>No reviews yet.</p>
<?php else: ?>
	<table>
		<thead>
			<tr>
				<th>Match</th>
				<th>Reviewer</th>
				<th>Rating</th>
				<th>Feedback</th>
				<th>When</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($reviews as $r): ?>
				<tr>
					<td><?= norm($r['match_id'] ?? '') ?></td>
					<td><?= norm($r['reviewer_id'] ?? '') ?></td>
					<td><?= norm((string)($r['rating'] ?? '')) ?></td>
					<td><?= norm($r['feedback'] ?? '') ?></td>
					<td><?= norm($r['created_at'] ?? '') ?></td>
					<td>
						<?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === ($r['reviewer_id'] ?? null)): ?>
							<a class="btn" href="<?= $baseUrl . $basepath ?>reviews/<?= norm($r['id']) ?>/edit">Edit</a>
							<form method="post" action="<?= $baseUrl . $basepath ?>reviews/<?= norm($r['id']) ?>/delete" style="display:inline">
								<button class="btn danger" type="submit" onclick="return confirm('Delete review?')">Delete</button>
							</form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<?php require __DIR__ . '/../shared/footer.php'; ?>