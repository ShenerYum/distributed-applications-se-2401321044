<?php
$title = 'My Requests';
require __DIR__ . '/../shared/header.php';
?>

<div style="max-width:920px;margin:28px auto">
	<h1>My Requests</h1>

	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>skills">Make a request</a></p>


	<?php if (!empty($requests) && is_array($requests)): ?>
		<table>
			<thead>
				<tr>
					<th>Title</th>
					<th>Skill</th>
					<th>Desired Level</th>
					<th>Notes</th>
					<th>Max Hours</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($requests as $r): ?>
					<tr>
						<td><?= norm($r['title'] ?? '') ?></td>
						<td><?= norm($r['skill_name'] ?? $r['skill_id'] ?? '') ?> <small><?= norm($r['skill_category'] ?? '') ?></small></td>
						<td><?= norm($r['desired_level'] ?? '') ?></td>
						<td><?= norm($r['notes'] ?? '') ?></td>
						<td><?= norm($r['max_hours'] ?? '') ?></td>
						<td>
							<a href="<?= $baseUrl . $basepath ?>requests/<?= norm($r['id']) ?>/edit">Edit</a>
							<form method="post" action="<?= $baseUrl . $basepath ?>requests/<?= norm($r['id']) ?>/delete" style="display:inline-block;margin-left:8px;">
								<button class="btn danger" type="submit" onclick="return confirm('Delete this request?');">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p>You have not created any requests yet.</p>
	<?php endif; ?>

	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath ?>profile">Back to profile</a></p>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>