<?php
$isAdmin = isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;

$title = 'Requests';
require __DIR__ . '/../shared/header.php';
?>

<div style="max-width:920px;margin:28px auto">
	<h1>Requests</h1>

	<?php if (!empty($needs_skill)): ?>
		<div style="padding:12px;border:1px solid #ddd;background:#fff6e6">
			<p>You don't have any offers yet — create an offer for the skills you can provide first. <a class="btn" href="<?= $baseUrl . $basepath ?>skills">Go to skills</a></p>
		</div>
	<?php elseif (!empty($requests) && is_array($requests)): ?>
		<table>
			<thead>
				<tr>
					<th>Title</th>
					<th>Skill</th>
					<th>Desired Level</th>
					<th>Notes</th>
					<th>Max Hours</th>
					<th>By</th>
					<th></th>
					<?php if ($isAdmin): ?>
						<th></th>
					<?php endif; ?>
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
						<td><?= norm($r['user_name'] ?? $r['user_id'] ?? '') ?></td>
						<td><a class="btn" href="<?= $baseUrl . $basepath ?>matches/create?request_id=<?= norm($r['id']) ?>" style="margin-left:8px;">Match</a></td>
						<?php if ($isAdmin): ?>
							<td>
								<a href="<?= $baseUrl . $basepath ?>requests/<?= norm($r['id']) ?>/edit">Edit</a>
								<form method="post" action="<?= $baseUrl . $basepath ?>requests/<?= norm($r['id']) ?>/delete" style="display:inline-block;margin-left:8px;">
									<button class="btn danger" type="submit" onclick="return confirm('Delete this request?');">Delete</button>
								</form>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p>No requests found.</p>
	<?php endif; ?>

	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>">Back to home</a></p>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>