<?php

$title = 'Skills';
require __DIR__ . '/../shared/header.php';

?>

<div class="container my-4">
	<h1>Skills</h1>

	<?php if ($isAdmin): ?>
		<p><a class="btn btn-primary" href="<?= $baseUrl . $basepath ?>skills/create">Create skill</a></p>
	<?php endif; ?>

	<form method="get" action="<?= $baseUrl . $basepath ?>skills" class="mb-4 p-3 bg-light rounded">
		<div class="row g-3 align-items-end">
			<div class="col-auto">
				<label class="form-label">Search by name:</label>
				<input type="text" class="form-control" name="name" value="<?= norm($_GET['name'] ?? '') ?>" placeholder="e.g. Essay writing" />
			</div>
			<div class="col-auto">
				<label class="form-label">Search by category:</label>
				<input type="text" class="form-control" name="category" value="<?= norm($_GET['category'] ?? '') ?>" placeholder="e.g. Writing" />
			</div>
			<div class="col-auto">
				<label class="form-label">Per page:</label>
				<select class="form-select" name="limit">

					<option value="10" <?= ($_GET['limit'] ?? 50) == 10 ? 'selected' : '' ?>>10</option>
					<option value="25" <?= ($_GET['limit'] ?? 50) == 25 ? 'selected' : '' ?>>25</option>
					<option value="50" <?= ($_GET['limit'] ?? 50) == 50 ? 'selected' : '' ?>>50</option>
					<option value="100" <?= ($_GET['limit'] ?? 50) == 100 ? 'selected' : '' ?>>100</option>
				</select>
			</div>
			<div class="col-auto">
				<button class="btn btn-primary" type="submit">Search</button>
				<a class="btn btn-secondary" href="<?= $baseUrl . $basepath ?>skills">Clear</a>
			</div>
		</div>
	</form>

	<?php if (!empty($skills) && is_array($skills)): ?>
		<table class="table table-striped table-hover">
			<thead class="table-light">
				<tr>
					<th>Name</th>
					<th>Category</th>
					<th>Description</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($skills as $s): ?>
					<tr>
						<td><?= norm($s['name'] ?? '') ?></td>
						<td><?= norm($s['category'] ?? '') ?></td>
						<td><?= norm($s['description'] ?? '') ?></td>
						<td>
							<a class="btn btn-sm btn-info" href="<?= $baseUrl . $basepath ?>offers/create?skill=<?= norm($s['id']) ?>">Create offer</a>
							<a class="btn btn-sm btn-info" href="<?= $baseUrl . $basepath ?>requests/create?skill=<?= norm($s['id']) ?>">Create request</a>

							<?php if ($isAdmin): ?>
								<a class="btn btn-sm btn-warning" href="<?= $baseUrl . $basepath ?>skills/<?= norm($s['id']) ?>/edit">Edit</a>
								<form method="post" action="<?= $baseUrl . $basepath ?>skills/<?= norm($s['id']) ?>/delete" style="display:inline-block;">
									<button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this skill?');">Delete</button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<nav class="d-flex justify-content-center mt-4" aria-label="Page navigation">
			<ul class="pagination">
				<?php
				$limit = (int)($_GET['limit'] ?? 50);
				$offset = (int)($_GET['offset'] ?? 0);
				$name = !empty($_GET['name']) ? (string)$_GET['name'] : '';
				$queryString = !empty($name) ? '&name=' . urlencode($name) : '';
				?>

				<?php if ($offset > 0): ?>
					<li class="page-item">
						<a class="page-link" href="<?= $baseUrl . $basepath ?>skills?limit=<?= $limit ?>&offset=<?= max(0, $offset - $limit) ?><?= $queryString ?>">← Previous</a>
					</li>
				<?php endif; ?>

				<li class="page-item disabled">
					<span class="page-link">Page <?= ($offset / $limit) + 1 ?> (<?= count($skills) ?> results)</span>
				</li>

				<?php if (count($skills) >= $limit): ?>
					<li class="page-item">
						<a class="page-link" href="<?= $baseUrl . $basepath ?>skills?limit=<?= $limit ?>&offset=<?= $offset + $limit ?><?= $queryString ?>">Next →</a>
					</li>
				<?php endif; ?>
			</ul>
		</nav>
	<?php else: ?>
		<div class="alert alert-info">No skills found.</div>
	<?php endif; ?>

	<div class="mt-3"><a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath; ?>">Back to home</a></div>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>