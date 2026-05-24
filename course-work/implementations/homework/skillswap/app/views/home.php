<?php

// $isAdmin = isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;

$title = 'Home';
require __DIR__ . '/shared/header.php';
?>

<h1>Welcome to Skill Swap</h1>
<p>Tralala.</p>

<?php if (!empty($_SESSION['user_id'])): ?>
	<ul>
		<li><a href="<?= $baseUrl . $basepath; ?>profile">Your Profile</a></li>
		<li><a href="<?= $baseUrl . $basepath; ?>users">Browse Users</a></li>
		<li><a href="<?= $baseUrl . $basepath; ?>skills">Browse Skills</a></li>
		<li><a href="<?= $baseUrl . $basepath; ?>offers">Browse Offers</a></li>
		<li><a href="<?= $baseUrl . $basepath; ?>requests">Browse Requests</a></li>
	</ul>
<?php else: ?>
	<a href="<?= $baseUrl . $basepath; ?>login">Login</a>
	<a href="<?= $baseUrl . $basepath; ?>register">Register</a>
<?php endif; ?>

<?php require __DIR__ . '/shared/footer.php'; ?>