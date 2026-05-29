<?php
$title = 'Offers';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<h1>Offers</h1>

	<form method="get" action="<?= $baseUrl . $basepath ?>offers" class="mb-4 p-3 bg-light rounded">
		<div class="row g-3 align-items-end">
			<div class="col-auto">
				<label class="form-label">Search by title:</label>
				<input type="text" class="form-control" name="title" value="<?= norm($_GET['title'] ?? '') ?>" placeholder="e.g. Learn Piano" />
			</div>
			<div class="col-auto">
				<label class="form-label">Search by skill:</label>
				<input type="text" class="form-control" name="skill_name" value="<?= norm($_GET['skill_name'] ?? '') ?>" placeholder="e.g. Music" />
			</div>
			<div class="col-auto">
				<label class="form-label">Search by user:</label>
				<input type="text" class="form-control" name="user_name" value="<?= norm($_GET['user_name'] ?? '') ?>" placeholder="e.g. Jane Smith" />
			</div>
			<div class="col-auto">
				<label class="form-label">Per page:</label>
				<select class="form-select" name="limit">
					<option value="10" <?= ($_GET['limit'] ?? 20) == 10 ? 'selected' : '' ?>>10</option>
					<option value="25" <?= ($_GET['limit'] ?? 20) == 25 ? 'selected' : '' ?>>25</option>
					<option value="50" <?= ($_GET['limit'] ?? 20) == 50 ? 'selected' : '' ?>>50</option>
					<option value="100" <?= ($_GET['limit'] ?? 20) == 100 ? 'selected' : '' ?>>100</option>
				</select>
			</div>
			<div class="col-auto">
				<button class="btn btn-primary" type="submit">Search</button>
				<a class="btn btn-secondary" href="<?= $baseUrl . $basepath ?>offers">Clear</a>
			</div>
		</div>
	</form>

	<?php if (!empty($empty)): ?>
		<div class="alert alert-info">No offers available.</div>
	<?php elseif (!empty($offers) && is_array($offers)): ?>
		<table class="table table-striped table-hover">
			<thead class="table-light">
				<tr>
					<th>Title</th>
					<th>Skill</th>
					<th>Availability</th>
					<th>By</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($offers as $o): ?>
					<tr>
						<td><?= norm($o['title'] ?? '') ?></td>
						<td>
							<?= norm($o['skill_name'] ?? $o['skill_id'] ?? '') ?>
							<?php if (!empty($o['skill_category'])): ?>
								<small class="text-muted"><?= norm($o['skill_category']) ?></small>
							<?php endif; ?>
						</td>
						<td><?= norm($o['availability'] ?? '') ?></td>
						<td><?= norm($o['user_name'] ?? $o['user_id'] ?? '') ?></td>
						<td>
							<a class="btn btn-sm btn-info" href="<?= $baseUrl . $basepath ?>matches/create?offer_id=<?= norm($o['id']) ?>">Match</a>
							<?php if ($isAdmin): ?>
								<a class="btn btn-sm btn-warning" href="<?= $baseUrl . $basepath ?>offers/<?= norm($o['id']) ?>/edit">Edit</a>
								<form method="post" action="<?= $baseUrl . $basepath ?>offers/<?= norm($o['id']) ?>/delete" style="display:inline-block;">
									<button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this offer?');">Delete</button>
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
				$limit = (int)($_GET['limit'] ?? 20);
				$offset = (int)($_GET['offset'] ?? 0);
				$titleFilter = !empty($_GET['title']) ? (string)$_GET['title'] : '';
				$skillNameFilter = !empty($_GET['skill_name']) ? (string)$_GET['skill_name'] : '';
				$userNameFilter = !empty($_GET['user_name']) ? (string)$_GET['user_name'] : '';
				$titleQuery = !empty($titleFilter) ? '&title=' . urlencode($titleFilter) : '';
				$skillQuery = !empty($skillNameFilter) ? '&skill_name=' . urlencode($skillNameFilter) : '';
				$userQuery = !empty($userNameFilter) ? '&user_name=' . urlencode($userNameFilter) : '';
				?>

				<?php if ($offset > 0): ?>
					<li class="page-item">
						<a class="page-link" href="<?= $baseUrl . $basepath ?>offers?limit=<?= $limit ?>&offset=<?= max(0, $offset - $limit) ?><?= $titleQuery ?><?= $skillQuery ?><?= $userQuery ?>">← Previous</a>
					</li>
				<?php endif; ?>

				<li class="page-item disabled">
					<span class="page-link">Page <?= ($offset / $limit) + 1 ?> (<?= count($offers) ?> results)</span>
				</li>

				<?php if (count($offers) >= $limit): ?>
					<li class="page-item">
						<a class="page-link" href="<?= $baseUrl . $basepath ?>offers?limit=<?= $limit ?>&offset=<?= $offset + $limit ?><?= $titleQuery ?><?= $skillQuery ?><?= $userQuery ?>">Next →</a>
					</li>
				<?php endif; ?>
			</ul>
		</nav>
	<?php else: ?>
		<div class="alert alert-info">
			<?php if (!empty($hasFilters)): ?>
				<p>No offers found matching your search. <a href="<?= $baseUrl . $basepath ?>offers">Clear filters</a></p>
			<?php elseif (empty($hasRequests) || !(bool)$hasRequests): ?>
				<p>You don't have any requests yet — create requests for the skills you're interested in first. <a class="btn btn-sm btn-primary" href="<?= $baseUrl . $basepath ?>skills">Browse skills</a></p>
			<?php else: ?>
				<p>No offers available for your requested skills yet.</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="mt-3"><a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath; ?>">Back to home</a></div>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>