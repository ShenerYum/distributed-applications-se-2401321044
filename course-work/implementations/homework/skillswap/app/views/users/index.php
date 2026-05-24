<?php
$title = 'Users';
require __DIR__ . '/../shared/header.php';
?>

<div style="max-width:920px;margin:28px auto">
	<h1>All Users</h1>

	<?php if (!empty($users) && is_array($users)): ?>
		<table>
			<thead>
				<tr>
					<th>Name</th>
					<th>Email</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($users as $u): ?>
					<tr>
						<td><?= norm($u['name'] ?? '') ?></td>
						<td><?= norm($u['email'] ?? '') ?></td>
						<td><a href="<?= $baseUrl . $basepath ?>users/<?= norm($u['id']) ?>">View</a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p>No users found.</p>
	<?php endif; ?>

	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>">Back to home</a></p>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>