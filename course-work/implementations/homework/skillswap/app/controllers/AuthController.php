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
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->render('register');
			return;
		}

		$input = $_POST;
		$name = trim($input['name'] ?? '');
		$email = trim($input['email'] ?? '');
		$password = $input['password'] ?? '';
		$password_confirm = $input['password_confirm'] ?? '';

		$errors = [];
		if (!$name) $errors[] = 'Name is required.';
		if (!$email) $errors[] = 'Email is required.';
		if (!$password) $errors[] = 'Password is required.';
		if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
			(isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

		if (!empty($errors)) {
			if ($isApi) $this->json(['error' => $errors], 422);

			$this->render('register', ['errors' => $errors]);
			return;
		}

		try {
			$user = $this->userModel->register($name, $email, $password);
			if (!empty($user['id'])) {
				$_SESSION['user_id'] = $user['id'];
				$_SESSION['user_name'] = $user['name'];
			}

			if ($isApi) {
				$this->json(['success' => true, 'user' => $user], 201);
			}

			$this->redirect();
		} catch (Exception $e) {
			if ($isApi) $this->json(['error' => $e->getMessage()], 400);

			$this->render('register', ['errors' => [$e->getMessage()]]);
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
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->render('login');
			return;
		}

		$input = $_POST;
		$email = trim($input['email'] ?? '');
		$password = $input['password'] ?? '';

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
			(isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

		if (!$email || !$password) {
			if ($isApi) $this->json(['error' => 'Missing email or password'], 422);

			$this->render('login', ['error' => 'Missing email or password']);
			return;
		}

		$user = $this->userModel->login($email, $password);
		if ($user) {
			$_SESSION['user_id'] = $user['id'] ?? null;
			$_SESSION['user_name'] = $user['name'] ?? null;
			$_SESSION['is_admin'] = $user['is_admin'] ?? null;

			if ($isApi) $this->json(['success' => true, 'user' => $user]);

			$this->redirect();
		}

		if ($isApi) $this->json(['error' => 'Invalid credentials'], 401);

		$this->render('login', ['error' => 'Invalid credentials']);
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

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
		if ($isApi) $this->json(['success' => true]);

		$this->redirect();
		exit;
	}
}
