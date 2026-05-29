<?php

$title = 'Register';
require __DIR__ . '/shared/header.php';
?>

<div class="container" style="max-width:520px;margin-top:60px">
	<div class="card shadow-sm">
		<div class="card-body p-4">
			<h1 class="card-title">Register</h1>

			<?php if (!empty($errors) && is_array($errors)): ?>
				<?php foreach ($errors as $err): ?>
					<div class="alert alert-danger alert-dismissible fade show" role="alert">
						<?= norm($err) ?>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

			<form method="post" action="<?= $baseUrl . $basepath; ?>register">
				<div class="mb-3">
					<label for="name" class="form-label">Full name</label>
					<input id="name" type="text" class="form-control" name="name" required>
				</div>

				<div class="mb-3">
					<label for="email" class="form-label">Email</label>
					<input id="email" type="email" class="form-control" name="email" required>
				</div>

				<div class="mb-3">
					<label for="password" class="form-label">Password</label>
					<input id="password" type="password" class="form-control" name="password" required>
				</div>

				<div class="mb-3">
					<label for="confirm" class="form-label">Confirm password</label>
					<input id="confirm" type="password" class="form-control" name="password_confirm" required>
				</div>

				<button type="submit" class="btn btn-primary w-100">Create account</button>
			</form>

			<p class="mt-3 text-center">Already registered? <a href="<?= $baseUrl . $basepath; ?>login">Login</a></p>
		</div>
	</div>
</div>

<?php require __DIR__ . '/shared/footer.php'; ?>