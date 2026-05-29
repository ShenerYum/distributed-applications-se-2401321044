<?php
$title = 'All Reviews';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<h2>All Reviews (admin)</h2>

	<p>Sort by: <a href="<?= $baseUrl . $basepath ?>reviews?sort=created_at">Created</a> | <a href="<?= $baseUrl . $basepath ?>reviews?sort=rating">Rating</a> | <a href="<?= $baseUrl . $basepath ?>reviews?sort=match">Match</a> | <a href="<?= $baseUrl . $basepath ?>reviews?sort=reviewer">Reviewer</a></p>

	<?php if (empty($reviews)): ?>
		<p>No reviews.</p>
	<?php else: ?>
		<table>
			<thead>
				<tr>
					<th>ID</th>
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
						<td><?= norm($r['id'] ?? '') ?></td>
						<td><?= norm($r['match_id'] ?? '') ?></td>
						<td><?= norm($r['reviewer_id'] ?? '') ?></td>
						<td><?= norm((string)($r['rating'] ?? '')) ?></td>
						<td><?= norm($r['feedback'] ?? '') ?></td>
						<td><?= norm($r['created_at'] ?? '') ?></td>
						<td>
							<a class="btn" href="<?= $baseUrl . $basepath ?>reviews/<?= norm($r['id']) ?>/edit">Edit</a>
							<form method="post" action="<?= $baseUrl . $basepath ?>reviews/<?= norm($r['id']) ?>/delete" style="display:inline">
								<button class="btn danger" type="submit" onclick="return confirm('Delete review?')">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php require __DIR__ . '/../shared/footer.php'; ?>