<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * AuthController is responsible for handling user authentication operations such as registration,
 * login, and logout. It interacts with the UserModel to perform database operations related to
 * user accounts. The controller ensures that user input is validated and that appropriate responses
 * are returned for each operation.
 */
class AuthController extends Controller
{
	/**
	 * The UserModel instance for handling user-related database operations.
	 * @var UserModel
	 */
	private $userModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$this->userModel = $this->loadModel('UserModel');
	}

	/**
	 * Register a new user.
	 * Expects POST: name, email, password
	 * 
	 * @return array JSON response containing the created user or an error message.
	 */
	public function register()
	{
		$input = $_POST;
		$name = trim($input['name'] ?? '');
		$email = trim($input['email'] ?? '');
		$password = $input['password'] ?? '';

		if (!$name || !$email || !$password) {
			return $this->json(['error' => 'Missing required fields'], 422);
		}

		try {
			$user = $this->userModel->register($name, $email, $password);

			if (!empty($user['id'])) {
				$_SESSION['user_id'] = $user['id'];
			}

			return $this->json(['success' => true, 'user' => $user], 201);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * Login existing user.
	 * Expects POST: email, password
	 * 
	 * @return array JSON response containing the logged-in user or an error message.
	 */
	public function login()
	{
		$input = $_POST;
		$email = trim($input['email'] ?? '');
		$password = $input['password'] ?? '';

		if (!$email || !$password) {
			return $this->json(['error' => 'Missing email or password'], 422);
		}

		$user = $this->userModel->login($email, $password);
		if ($user) {
			$_SESSION['user_id'] = $user['id'] ?? null;
			return $this->json(['success' => true, 'user' => $user]);
		}

		return $this->json(['error' => 'Invalid credentials'], 401);
	}

	/**
	 * Logout current user.
	 */
	public function logout()
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

		return $this->json(['success' => true]);
	}
}
