<?php
$title = 'User Reviews';
require __DIR__ . '/../shared/header.php';

assert(isset($user_id));
?>

<h2>Reviews for User</h2>
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
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<p><a class="btn" href="<?= $baseUrl . $basepath ?>users/<?= norm($user_id) ?>">Back to user</a></p>

<?php require __DIR__ . '/../shared/footer.php'; ?>