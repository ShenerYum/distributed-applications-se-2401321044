<?php
$title = 'My Offers';
require __DIR__ . '/../shared/header.php';
?>

<div style="max-width:920px;margin:28px auto">
	<h1>My Offers</h1>

	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>skills">Make an offer</a></p>

	<?php if (!empty($offers) && is_array($offers)): ?>
		<table>
			<thead>
				<tr>
					<th>Title</th>
					<th>Availability</th>
					<th>Description</th>
					<th>Active</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($offers as $o): ?>
					<tr>
						<td><?= norm($o['title'] ?? '') ?></td>
						<td><?= norm($o['availability'] ?? '') ?></td>
						<td><?= norm($o['description'] ?? '') ?></td>
						<td><?= isset($o['is_active']) && $o['is_active'] ? 'Yes' : 'No' ?></td>
						<td>
							<a href="<?= $baseUrl . $basepath ?>offers/<?= norm($o['id']) ?>/edit">Edit</a>
							<form method="post" action="<?= $baseUrl . $basepath ?>offers/<?= norm($o['id']) ?>/delete" style="display:inline-block;margin-left:8px;">
								<button class="btn danger" type="submit" onclick="return confirm('Delete this offer?');">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p>No offers created yet.</p>
	<?php endif; ?>

	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>">Back to home</a></p>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>