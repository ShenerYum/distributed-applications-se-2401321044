<?php

$title = 'Register';
require __DIR__ . '/shared/header.php';
?>

<div class="auth" style="max-width:520px;margin:48px auto;padding:18px;border:1px solid #ddd;border-radius:6px">
	<h1>Register</h1>

	<?php if (!empty($errors) && is_array($errors)): ?>
		<?php foreach ($errors as $err): ?>
			<p class="error"><?= norm($err) ?></p>
		<?php endforeach; ?>
	<?php endif; ?>

	<form method="post" action="<?= $baseUrl . $basepath; ?>register">
		<label for="name">Full name</label>
		<input id="name" type="text" name="name" required>

		<label for="email">Email</label>
		<input id="email" type="email" name="email" required>

		<label for="password">Password</label>
		<input id="password" type="password" name="password" required>

		<label for="confirm">Confirm password</label>
		<input id="confirm" type="password" name="password_confirm" required>

		<button type="submit">Create account</button>
	</form>

	<p>Already registered? <a href="<?= $baseUrl . $basepath; ?>login">Login</a></p>
</div>

<?php require __DIR__ . '/shared/footer.php'; ?>