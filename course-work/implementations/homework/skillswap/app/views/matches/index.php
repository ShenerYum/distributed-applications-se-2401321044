<?php
$title = 'All Matches';
require __DIR__ . '/../shared/header.php';
?>

<h2>All Matches</h2>
<p>Sort by: <a href="<?= $baseUrl . $basepath ?>matches?sort=created_at">Created</a> | <a href="<?= $baseUrl . $basepath ?>matches?sort=score">Score</a> | <a href="<?= $baseUrl . $basepath ?>matches?sort=status">Status</a> | <a href="<?= $baseUrl . $basepath ?>matches?sort=user">User</a></p>

<?php if (empty($matches)): ?>
	<p>No matches.</p>
<?php else: ?>
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Offer</th>
				<th>Request</th>
				<th>Initiator</th>
				<th>Reciever</th>
				<th>Score</th>
				<th>Status</th>
				<th>When</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($matches as $m): ?>
				<tr>
					<td><?= norm($m['id'] ?? '') ?></td>
					<td><?= norm($m['offer_id'] ?? '') ?></td>
					<td><?= norm($m['request_id'] ?? '') ?></td>
					<td><?= norm($m['user_a_id'] ?? '') ?></td>
					<td><?= norm($m['user_b_id'] ?? '') ?></td>
					<td><?= norm((string)($m['score'] ?? '')) ?></td>
					<td><?= norm($m['status'] ?? '') ?></td>
					<td><?= norm($m['created_at'] ?? '') ?></td>
					<td>
						<form method="post" action="<?= $baseUrl . $basepath ?>matches/<?= norm($m['id']) ?>/delete" style="display:inline">
							<button class="btn danger" type="submit" onclick="return confirm('Delete match and its reviews?')">Delete</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<?php require __DIR__ . '/../shared/footer.php'; ?>