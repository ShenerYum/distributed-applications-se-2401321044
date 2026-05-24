<?php
$title = 'User Offers';
require __DIR__ . '/../shared/header.php';

assert(isset($user_id));
?>

<h2>User Offers</h2>
<?php if (empty($offers)): ?>
	<p>No offers posted.</p>
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
			<?php foreach ($offers as $o): ?>
				<tr>
					<td><?= norm($o['description'] ?? '') ?></td>
					<td><?= norm($o['skill_name'] ?? $o['skill_id'] ?? '') ?></td>
					<td><?= norm($o['created_at'] ?? '') ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<p><a class="btn" href="<?= $baseUrl . $basepath ?>users/<?= norm($user_id) ?>">Back to user</a></p>

<?php require __DIR__ . '/../shared/footer.php'; ?>