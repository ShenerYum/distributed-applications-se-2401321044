<?php

$title = 'Login';
require __DIR__ . '/shared/header.php';
?>

<div class="auth" style="max-width:420px;margin:48px auto;padding:18px;border:1px solid #ddd;border-radius:6px">
	<h1>Login</h1>
	<?php if (!empty($error)): ?>
		<p class="error"><?= norm($error) ?></p>
	<?php endif; ?>
	<form method="post" action="<?= $baseUrl . $basepath; ?>login">
		<label for="email">Email</label>
		<input id="email" type="email" name="email" required>

		<label for="password">Password</label>
		<input id="password" type="password" name="password" required>

		<button type="submit">Login</button>
	</form>
	<p>Don't have an account? <a href="<?= $baseUrl . $basepath; ?>register">Register</a></p>
</div>

<?php require __DIR__ . '/shared/footer.php'; ?>