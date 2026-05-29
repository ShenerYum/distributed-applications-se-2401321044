<?php

$isAdmin = isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;

$title = 'Home';
require __DIR__ . '/shared/header.php';
?>

<div class="container my-4">
	<h1>Welcome to Skill Swap</h1>
	<p class="lead">Share your skills, learn new ones.</p>

	<?php if (!empty($_SESSION['user_data'])): ?>
		<div class="list-group mt-4" style="max-width:400px">
			<a href="<?= $baseUrl . $basepath; ?>profile" class="list-group-item list-group-item-action">Your Profile</a>
			<a href="<?= $baseUrl . $basepath; ?>users" class="list-group-item list-group-item-action">Browse Users</a>
			<a href="<?= $baseUrl . $basepath; ?>skills" class="list-group-item list-group-item-action">Browse Skills</a>
			<a href="<?= $baseUrl . $basepath; ?>offers" class="list-group-item list-group-item-action">Browse Offers</a>
			<a href="<?= $baseUrl . $basepath; ?>requests" class="list-group-item list-group-item-action">Browse Requests</a>

			<?php if ($isAdmin): ?>
				<a href="<?= $baseUrl . $basepath; ?>matches" class="list-group-item list-group-item-action">Manage Matches</a>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<div class="mt-4">
			<a class="btn btn-primary btn-lg" href="<?= $baseUrl . $basepath; ?>login">Login</a>
			<a class="btn btn-secondary btn-lg" href="<?= $baseUrl . $basepath; ?>register">Register</a>
		</div>
	<?php endif; ?>
</div>

<?php require __DIR__ . '/shared/footer.php'; ?>