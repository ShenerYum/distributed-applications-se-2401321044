<?php
$isAdmin = isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;

$title = 'Skills';
require __DIR__ . '/../shared/header.php';
?>

<div style="max-width:920px;margin:28px auto">
	<h1>Skills</h1>

	<?php if ($isAdmin): ?>
		<p>
			<a class="btn" href="<?= $baseUrl . $basepath ?>skills/create">Create skill</a>
		</p>
	<?php endif; ?>

	<?php if (!empty($skills) && is_array($skills)): ?>
		<table>
			<thead>
				<tr>
					<th>Name</th>
					<th>Category</th>
					<th>Description</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($skills as $s): ?>
					<tr>
						<td><?= norm($s['name'] ?? '') ?></td>
						<td><?= norm($s['category'] ?? '') ?></td>
						<td><?= norm($s['description'] ?? '') ?></td>
						<td>
							<?php if ($isAdmin): ?>
								<a href="<?= $baseUrl . $basepath ?>skills/<?= norm($s['id']) ?>/edit">Edit</a>
								<form method="post" action="<?= $baseUrl . $basepath ?>skills/<?= norm($s['id']) ?>/delete" style="display:inline-block;margin-left:8px;">
									<button class="btn danger" type="submit" onclick="return confirm('Delete this skill?');">Delete</button>
								</form>
							<?php endif; ?>
							<a class="btn" href="<?= $baseUrl . $basepath ?>offers/create?skill=<?= norm($s['id']) ?>" style="margin-left:8px;">Create offer</a>
							<a class="btn" href="<?= $baseUrl . $basepath ?>requests/create?skill=<?= norm($s['id']) ?>" style="margin-left:8px;">Create request</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p>No skills found.</p>
	<?php endif; ?>

	<p style="margin-top:12px"><a href="<?= $baseUrl . $basepath; ?>">Back to home</a></p>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>