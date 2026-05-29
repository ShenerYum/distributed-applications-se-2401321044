<?php
$title = 'All Matches';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<h2>All Matches</h2>

	<?php if (empty($matches)): ?>
		<p>No matches.</p>
	<?php else: ?>
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>ID</th>
					<th>Offer</th>
					<th>Request</th>
					<th>Initiator</th>
					<th>Reciever</th>
					<th>Status</th>
					<th>When</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($matches as $m): ?>
					<tr>
						<td><?= norm($m['id'] ?? '') ?></td>
						<td><?= norm($m['offer_title'] ?? '') ?></td>
						<td><?= norm($m['request_title'] ?? '') ?></td>
						<td><a href="<?= $baseUrl . $basepath ?>users/<?= norm($m['user_a_id']) ?>"><?= norm($m['user_a_email'] ?? '') ?></a></td>
						<td><a href="<?= $baseUrl . $basepath ?>users/<?= norm($m['user_b_id']) ?>"><?= norm($m['user_b_email'] ?? '') ?></a></td>
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