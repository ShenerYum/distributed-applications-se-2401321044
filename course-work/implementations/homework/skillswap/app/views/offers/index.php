<?php
$isAdmin = isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;

$title = 'Browse Offers';
require __DIR__ . '/../shared/header.php';
?>

<div style="max-width:920px;margin:28px auto">
	<h1>Offers</h1>

	<?php if (!empty($needs_skill)): ?>
		<div style="padding:12px;border:1px solid #ddd;background:#fff6e6">
			<p>You don't have any requests yet — create a request for the skills you're interested in first. <a class="btn" href="<?= $baseUrl . $basepath ?>skills">Go to skills</a></p>
		</div>
	<?php elseif (!empty($offers) && is_array($offers)): ?>
		<table>
			<thead>
				<tr>
					<th>Title</th>
					<th>Availability</th>
					<th>Description</th>
					<th>Skill</th>
					<th>By</th>
					<th></th>
					<?php if ($isAdmin): ?>
						<th></th>
					<?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($offers as $o): ?>
					<tr>
						<td><?= norm($o['title'] ?? '') ?></td>
						<td><?= norm($o['availability'] ?? '') ?></td>
						<td><?= norm($o['description'] ?? '') ?></td>
						<td><?= norm($o['skill_name'] ?? $o['skill_id'] ?? '') ?> <small><?= norm($o['skill_category'] ?? '') ?></small></td>
						<td><?= norm($o['user_name'] ?? $o['user_id'] ?? '') ?></td>
						<td><a class="btn" href="<?= $baseUrl . $basepath ?>matches/create?offer_id=<?= norm($o['id']) ?>" style="margin-left:8px;">Match</a></td>
						<?php if ($isAdmin): ?>
							<td>
								<a href="<?= $baseUrl . $basepath ?>offers/<?= norm($o['id']) ?>/edit">Edit</a>
								<form method="post" action="<?= $baseUrl . $basepath ?>offers/<?= norm($o['id']) ?>/delete" style="display:inline-block;margin-left:8px;">
									<button class="btn danger" type="submit" onclick="return confirm('Delete this offer?');">Delete</button>
								</form>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p>No offers available.</p>
	<?php endif; ?>

	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>">Back to home</a></p>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>