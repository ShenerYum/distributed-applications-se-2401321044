<?php
$title = 'Requests';
require __DIR__ . '/../shared/header.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
?>

<div class="container my-4">
	<h1>Requests</h1>

	<form method="get" action="<?= $baseUrl . $basepath ?>requests" class="mb-4 p-3 bg-light rounded">
		<div class="row g-3 align-items-end">
			<div class="col-auto">
				<label class="form-label">Search by title:</label>
				<input type="text" class="form-control" name="title" value="<?= norm($_GET['title'] ?? '') ?>" placeholder="e.g. Learn Python" />
			</div>
			<div class="col-auto">
				<label class="form-label">Search by skill:</label>
				<input type="text" class="form-control" name="skill_name" value="<?= norm($_GET['skill_name'] ?? '') ?>" placeholder="e.g. Python" />
			</div>
			<div class="col-auto">
				<label class="form-label">Search by user:</label>
				<input type="text" class="form-control" name="user_name" value="<?= norm($_GET['user_name'] ?? '') ?>" placeholder="e.g. John Doe" />
			</div>
			<div class="col-auto">
				<label class="form-label">Per page:</label>
				<select class="form-select" name="limit">
					<option value="10" <?= ($limit ?? 20) == 10 ? 'selected' : '' ?>>10</option>
					<option value="25" <?= ($limit ?? 20) == 25 ? 'selected' : '' ?>>25</option>
					<option value="50" <?= ($limit ?? 20) == 50 ? 'selected' : '' ?>>50</option>
					<option value="100" <?= ($limit ?? 20) == 100 ? 'selected' : '' ?>>100</option>
				</select>
			</div>
			<div class="col-auto">
				<button class="btn btn-primary" type="submit">Search</button>
				<a class="btn btn-secondary" href="<?= $baseUrl . $basepath ?>requests">Clear</a>
			</div>
		</div>
	</form>

	<?php if (!empty($empty)): ?>
		<div class="alert alert-info">No requests available.</div>
	<?php elseif (!empty($requests) && is_array($requests)): ?>
		<table class="table table-striped table-hover">
			<thead class="table-light">
				<tr>
					<th>Title</th>
					<th>Skill</th>
					<th>Level</th>
					<th>Max Hours</th>
					<th>By</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($requests as $r): ?>
					<tr>
						<td><?= norm($r['title'] ?? '') ?></td>
						<td>
							<?= norm($r['skill_name'] ?? $r['skill_id'] ?? '') ?>
							<?php if (!empty($r['skill_category'])): ?>
								<small class="text-muted"><?= norm($r['skill_category']) ?></small>
							<?php endif; ?>
						</td>
						<td><?= norm($r['desired_level'] ?? '') ?></td>
						<td><?= norm($r['max_hours'] ?? '') ?></td>
						<td><?= norm($r['user_name'] ?? $r['user_id'] ?? '') ?></td>
						<td>
							<a class="btn btn-sm btn-info" href="<?= $baseUrl . $basepath ?>matches/create?request_id=<?= norm($r['id']) ?>">Match</a>
							<?php if ($isAdmin): ?>
								<a class="btn btn-sm btn-warning" href="<?= $baseUrl . $basepath ?>requests/<?= norm($r['id']) ?>/edit">Edit</a>
								<form method="post" action="<?= $baseUrl . $basepath ?>requests/<?= norm($r['id']) ?>/delete" style="display:inline-block;">
									<button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this request?');">Delete</button>
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
				$titleFilter = !empty($_GET['title']) ? (string)$_GET['title'] : '';
				$skillNameFilter = !empty($_GET['skill_name']) ? (string)$_GET['skill_name'] : '';
				$userNameFilter = !empty($_GET['user_name']) ? (string)$_GET['user_name'] : '';
				$titleQuery = !empty($titleFilter) ? '&title=' . urlencode($titleFilter) : '';
				$skillQuery = !empty($skillNameFilter) ? '&skill_name=' . urlencode($skillNameFilter) : '';
				$userQuery = !empty($userNameFilter) ? '&user_name=' . urlencode($userNameFilter) : '';
				?>

				<?php if ($offset > 0): ?>
					<li class="page-item">
						<a class="page-link" href="<?= $baseUrl . $basepath ?>requests?limit=<?= $limit ?>&offset=<?= max(0, $offset - $limit) ?><?= $titleQuery ?><?= $skillQuery ?><?= $userQuery ?>">← Previous</a>
					</li>
				<?php endif; ?>

				<li class="page-item disabled">
					<span class="page-link">Page <?= ($offset / $limit) + 1 ?> (<?= count($requests) ?> results)</span>
				</li>

				<?php if (count($requests) >= $limit): ?>
					<li class="page-item">
						<a class="page-link" href="<?= $baseUrl . $basepath ?>requests?limit=<?= $limit ?>&offset=<?= $offset + $limit ?><?= $titleQuery ?><?= $skillQuery ?><?= $userQuery ?>">Next →</a>
					</li>
				<?php endif; ?>
			</ul>
		</nav>
	<?php else: ?>
		<div class="alert alert-info">
			<?php if (!empty($hasFilters)): ?>
				<p>No requests found matching your search. <a href="<?= $baseUrl . $basepath ?>requests">Clear filters</a></p>
			<?php elseif (!empty($hasOffers) === false): ?>
				<p>You don't have any offers yet — create offers for the skills you can provide first. <a class="btn btn-sm btn-primary" href="<?= $baseUrl . $basepath ?>skills">Browse skills</a></p>
			<?php else: ?>
				<p>No requests available for your offered skills yet.</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="mt-3"><a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath; ?>">Back to home</a></div>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>