<?php
$title = 'User Requests';
require __DIR__ . '/../shared/header.php';

assert(isset($user_id));
?>

<h2>User Requests</h2>
<?php if (empty($requests)): ?>
	<p>No requests posted.</p>
<?php else: ?>
	<table>
		<thead>
			<tr>
				<th>Title</th>
				<th>Skill</th>
				<th>When</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($requests as $r): ?>
				<tr>
					<td><?= norm($r['title'] ?? '') ?></td>
					<td><?= norm($r['skill_name'] ?? $r['skill_id'] ?? '') ?></td>
					<td><?= norm($r['created_at'] ?? '') ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<p><a class="btn" href="<?= $baseUrl . $basepath ?>users/<?= norm($user_id) ?>">Back to user</a></p>

<?php require __DIR__ . '/../shared/footer.php'; ?>