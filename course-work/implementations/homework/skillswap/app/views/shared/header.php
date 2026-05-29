<?php
if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

require __DIR__ . '/helper.php';

$pageTitle = $title ?? 'Skill Swap';

$isAdmin = isset($_SESSION['user_data']) && (int)($_SESSION['user_data']['is_admin'] ?? 0) === 1;
?>
<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?= norm($pageTitle) ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="<?= $baseUrl . $basepath; ?>assets/css/style.css">
</head>

<body class="d-flex flex-column min-vh-100 m-auto">
	<header>
		<nav class="site-nav navbar navbar-expand-lg navbar-light bg-light border-bottom">
			<div class="container-fluid">
				<div class="brand"><a class="navbar-brand" href="<?= $baseUrl . $basepath; ?>">Skill Swap</a></div>
				<div class="nav-right ms-auto align-items-center text-center d-flex">
					<?php if (!empty($_SESSION['user_data'])): ?>
						<span class="me-3 text-center">
							<a class="btn btn-link nav-link d-inline" href="<?= $baseUrl . $basepath; ?>profile">
								Hello,&nbsp;<?= norm($_SESSION['user_data']['name'] ?? 'User') ?>!
							</a>
						</span>

						<form method="POST" action="<?= $baseUrl . $basepath; ?>logout" style="display:inline">
							<button type="submit" class="btn btn-link nav-link" style="padding:0">Logout</button>
						</form>
					<?php else: ?>
						<a class="nav-link mx-2" href="<?= $baseUrl . $basepath; ?>login">Login</a>
						<a class="nav-link mx-2" href="<?= $baseUrl . $basepath; ?>register">Register</a>
					<?php endif; ?>
				</div>
			</div>
		</nav>
	</header>


	<main class="container-fluid flex-grow-1">
		<!-- <?php if (isset($_GET)) echo '<pre>_GET: ' . json_encode($_GET, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</pre>'; ?> -->
		<!-- <?php if (!empty($_SESSION)) echo '<pre>Session: ' . json_encode($_SESSION, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</pre>'; ?> -->
		<!-- <?php if (isset($data)) echo '<pre>Data: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</pre>'; ?> -->
		<?php if (!empty($errors)): ?>
			<div style="max-width:1200px;margin:12px auto">
				<?php foreach ((array)$errors as $e): ?>
					<div class="alert alert-danger alert-dismissible fade show" role="alert">
						<?= norm($e['message'] ?? (string)$e) ?>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if (!empty($messages)): ?>
			<div style="max-width:1200px;margin:12px auto">
				<?php foreach ((array)$messages as $m): ?>
					<div class="alert alert-info alert-dismissible fade show" role="alert">
						<?= norm($m['message'] ?? (string)$m) ?>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>