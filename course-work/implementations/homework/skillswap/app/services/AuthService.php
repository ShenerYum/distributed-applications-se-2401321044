<?php

namespace App\Services;

use App\Core\Service;

use App\Models\UserModel;

class AuthService extends Service
{
	public function __construct(private UserModel $users) {}

	/**
	 * Get the currently authenticated user's data from the session.
	 * @return array|null The user data array if authenticated, or null if not authenticated.
	 */
	public function getCurrentUser(): ?array
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$user = $_SESSION['user_data'] ?? null;

		return is_array($user) ? $user : null;
	}

	/**
	 * Ensure the user is authenticated. If not, throws an exception.
	 * @return array The authenticated user's data.
	 * @throws \RuntimeException if the user is not authenticated.
	 */
	public function requireAuth(): array
	{
		$user = $this->getCurrentUser();
		if (!$user) {
			throw new \RuntimeException('Unauthenticated', 401);
		}
		return $user;
	}

	/**
	 * Ensure the user is an admin. If not, throws an exception.
	 * @return array The authenticated admin user's data.
	 * @throws \RuntimeException if the user is not authenticated or not an admin.
	 */
	public function requireAdmin(): array
	{
		$user = $this->getCurrentUser();
		$isAdmin = (isset($user['is_admin']) && $user['is_admin']);
		if (!$isAdmin) {
			throw new \RuntimeException('Admin privileges required', 403);
		}
		return $user;
	}

	/**
	 * Authenticate a user by email and password. On success, initializes the session with user data.
	 * @param string $email
	 * @param string $password
	 * @return array The authenticated user's data.
	 * @throws \InvalidArgumentException if credentials are invalid.
	 */
	public function login(string $email, string $password): array
	{
		$user = $this->users->findByEmail($email);

		if (!$user) throw new \InvalidArgumentException('Invalid email', 400);

		if (!password_verify($password, $user['password']))
			throw new \InvalidArgumentException('Invalid password', 400);

		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		unset($user['password']);

		return $user;
	}

	/**
	 * Logs out the current user by clearing the session data and destroying the session.
	 */
	public function logout(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$_SESSION = [];
		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params['path'],
				$params['domain'],
				$params['secure'],
				$params['httponly']
			);
		}

		session_destroy();
	}


	/**
	 * Registers a new user with the provided name, email, and password. On success, logs in the new user.
	 * @param array $data An associative array containing 'name', 'email', 'password', and 'password_confirm' keys.
	 * @return array The newly registered user's data.
	 * @throws \InvalidArgumentException if input is invalid or email is already registered.
	 */
	public function register(array $data): array
	{
		$this->validateRequiredFields($data, ['name', 'email', 'password', 'password_confirm']);

		if ($this->users->findByEmail($data['email'])) {
			throw new \InvalidArgumentException('Email already registered', 409);
		}

		unset($data['password_confirm']);
		$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
		$this->users->create($data);

		return $this->login($data['email'], $data['password']);
	}
}
