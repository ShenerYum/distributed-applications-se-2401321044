<?php

$title = 'Login';
require __DIR__ . '/shared/header.php';
?>

<div class="container" style="max-width:420px;margin-top:60px">
	<div class="card shadow-sm">
		<div class="card-body p-4">
			<h1 class="card-title">Login</h1>
			<?php if (!empty($error)): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<?= norm($error) ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>
			<?php endif; ?>
			<form method="post" action="<?= $baseUrl . $basepath; ?>login">
				<div class="mb-3">
					<label for="email" class="form-label">Email</label>
					<input id="email" type="email" class="form-control" name="email" required>
				</div>

				<div class="mb-3">
					<label for="password" class="form-label">Password</label>
					<input id="password" type="password" class="form-control" name="password" required>
				</div>

				<button type="submit" class="btn btn-primary w-100">Login</button>
			</form>
			<p class="mt-3 text-center">Don't have an account? <a href="<?= $baseUrl . $basepath; ?>register">Register</a></p>
		</div>
	</div>
</div>

<?php require __DIR__ . '/shared/footer.php'; ?>