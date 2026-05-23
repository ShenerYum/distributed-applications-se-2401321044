<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Model.php';

/**
 * UserModel is responsible for handling user-related operations such as registration and authentication.
 */
class UserModel extends Model
{
	public function __construct()
	{
		parent::__construct();
		$this->setTable('Users');
		$this->setPrimaryKey('id');
	}

	/**
	 * Register a new user. Returns the created user record (without password).
	 * 
	 * @param string $name
	 * @param string $email
	 * @param string $password
	 * 
	 * @return array The created user record without the password field.
	 * 
	 * @throws InvalidArgumentException if the email is invalid.
	 * @throws RuntimeException if the email is already registered.
	 */
	public function register(string $name, string $email, string $password): array
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException('Invalid email');
		}

		$existing = $this->findByEmail($email);
		if ($existing) {
			throw new RuntimeException('Email already registered');
		}

		$hash = password_hash($password, PASSWORD_DEFAULT);
		$id = $this->create([
			'name' => $name,
			'email' => $email,
			'password' => $hash,
		]);

		$user = $this->findById($id);
		if ($user && isset($user['password'])) {
			unset($user['password']);
		}

		return $user ?: [];
	}

	/**
	 * Verify credentials and return user (without password) on success, null otherwise.
	 * 
	 * @param string $email
	 * @param string $password
	 * @return array|null The authenticated user record without the password field, or null if authentication fails.
	 */
	public function login(string $email, string $password): ?array
	{
		$user = $this->findByEmail($email);
		if (!$user) {
			return null;
		}

		$stored = $user['password'] ?? null;
		if ($stored && password_verify($password, $stored)) {
			unset($user['password']);
			return $user;
		}

		return null;
	}

	/**
	 * Find a user by email. Returns associative array or null.
	 * 
	 * @param string $email
	 * @return array|null The user record matching the email, or null if not found.
	 */
	public function findByEmail(string $email): ?array
	{
		$rows = $this->findBy(['email' => $email], 1);
		return $rows[0] ?? null;
	}
}
