<?php
if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

require __DIR__ . '/helper.php';

$pageTitle = $title ?? 'Skill Swap';
?>
<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?= norm($pageTitle) ?></title>
	<link rel="stylesheet" href="<?= $baseUrl . $basepath; ?>assets/css/style.css">
	<style>
		.site-nav {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 12px 18px;
			background: #fafafa;
			border-bottom: 1px solid #eee
		}

		.site-nav a {
			margin-left: 12px;
			text-decoration: none;
			color: #0366d6
		}

		.site-nav .brand {
			font-weight: 700;
			font-size: 18px
		}

		.site-nav .nav-right {
			display: flex;
			align-items: center
		}
	</style>
</head>

<body>
	<nav class="site-nav">
		<div class="brand"><a href="<?= $baseUrl . $basepath; ?>">Skill Swap</a></div>
		<div class="nav-right">
			<?php if (!empty($_SESSION['user_id'])): ?>
				<span style="margin-right:8px">Hello, <?= norm($_SESSION['user_name'] ?? 'User') ?>!</span>
				<a href="<?= $baseUrl . $basepath; ?>profile">Profile</a>
				<a href="<?= $baseUrl . $basepath; ?>logout">Logout</a>
			<?php else: ?>
				<a href="<?= $baseUrl . $basepath; ?>login">Login</a>
				<a href="<?= $baseUrl . $basepath; ?>register">Register</a>
			<?php endif; ?>
		</div>
	</nav>

	<main>
		<?php if (!empty($_SESSION['flash'])): ?>
			<div style="max-width:920px;margin:12px auto">
				<?php foreach ((array)$_SESSION['flash'] as $f): ?>
					<div style="padding:10px;border-radius:4px;margin-bottom:8px;background:#f0f8ff;border:1px solid #d0e4ff"><?= htmlspecialchars($f['message'] ?? (string)$f) ?></div>
				<?php endforeach;
				unset($_SESSION['flash']); ?>
			</div>
		<?php endif; ?>