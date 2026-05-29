<?php
$title = 'Users';
require __DIR__ . '/../shared/header.php';
?>

<div class="container my-4">
	<h1>Users</h1>

	<form method="get" action="<?= $baseUrl . $basepath ?>users" class="mb-4 p-3 bg-light rounded">
		<div class="row g-3 align-items-end">
			<div class="col-auto">
				<label class="form-label">Search by name:</label>
				<input type="text" class="form-control" name="name" value="<?= norm($_GET['name'] ?? '') ?>" placeholder="e.g. John Doe" />
			</div>
			<div class="col-auto">
				<label class="form-label">Search by email:</label>
				<input type="text" class="form-control" name="email" value="<?= norm($_GET['email'] ?? '') ?>" placeholder="e.g. user@example.com" />
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
				<a class="btn btn-secondary" href="<?= $baseUrl . $basepath ?>users">Clear</a>
			</div>
		</div>
	</form>

	<?php if (!empty($users) && is_array($users)): ?>
		<table class="table table-striped table-hover">
			<thead class="table-light">
				<tr>
					<th>Name</th>
					<th>Email</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($users as $u): ?>
					<tr>
						<td><?= norm($u['name'] ?? '') ?></td>
						<td><?= norm($u['email'] ?? '') ?></td>
						<td>
							<a class="btn btn-sm btn-info" href="<?= $baseUrl . $basepath ?>users/<?= norm($u['id']) ?>">View</a>
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
				$name = !empty($_GET['name']) ? (string)$_GET['name'] : '';
				$email = !empty($_GET['email']) ? (string)$_GET['email'] : '';
				$nameQuery = !empty($name) ? '&name=' . urlencode($name) : '';
				$emailQuery = !empty($email) ? '&email=' . urlencode($email) : '';
				?>

				<?php if ($offset > 0): ?>
					<li class="page-item">
						<a class="page-link" href="<?= $baseUrl . $basepath ?>users?limit=<?= $limit ?>&offset=<?= max(0, $offset - $limit) ?><?= $nameQuery ?><?= $emailQuery ?>">← Previous</a>
					</li>
				<?php endif; ?>

				<li class="page-item disabled">
					<span class="page-link">Page <?= ($offset / $limit) + 1 ?> (<?= count($users) ?> results)</span>
				</li>

				<?php if (count($users) >= $limit): ?>
					<li class="page-item">
						<a class="page-link" href="<?= $baseUrl . $basepath ?>users?limit=<?= $limit ?>&offset=<?= $offset + $limit ?><?= $nameQuery ?><?= $emailQuery ?>">Next →</a>
					</li>
				<?php endif; ?>
			</ul>
		</nav>
	<?php else: ?>
		<div class="alert alert-info">No users found.</div>
	<?php endif; ?>

	<div class="mt-3"><a class="btn btn-outline-secondary" href="<?= $baseUrl . $basepath; ?>">Back to home</a></div>
</div>

<?php require __DIR__ . '/../shared/footer.php'; ?>