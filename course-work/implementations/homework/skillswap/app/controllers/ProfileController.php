<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * ProfileController handles rendering and updating the current user's profile.
 */
class ProfileController extends Controller
{
	/**
	 * UserModel instance for database operations related to user accounts.
	 * @var UserModel
	 */
	private $userModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
		$this->userModel = $this->loadModel('UserModel');
	}

	/**
	 * Render profile view for current authenticated user.
	 */
	public function index()
	{
		$user = $this->requireAuth();

		if (isset($user['password'])) unset($user['password']);
		$this->render('profile/index', ['user' => $user]);
	}

	/**
	 * Edit profile: GET renders edit form, POST applies changes and redirects to profile.
	 */
	public function edit()
	{
		$user = $this->requireAuth();

		$id = $user['id'];

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			if (isset($user['password'])) unset($user['password']);
			$this->render('profile/edit', ['user' => $user]);
			return;
		}

		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';
		$password_confirm = $_POST['password_confirm'] ?? '';

		$errors = [];
		if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
		if ($password !== '' && $password !== $password_confirm) $errors[] = 'Passwords do not match';

		if (!empty($errors)) {
			$this->render('profile/edit', ['user' => $user, 'errors' => $errors]);
			return;
		}

		$data = [];
		if ($name !== '') $data['name'] = $name;
		if ($email !== '') $data['email'] = $email;
		if ($password !== '') $data['password'] = password_hash($password, PASSWORD_DEFAULT);

		if (!empty($data)) {
			$ok = $this->userModel->update($id, $data);
			if (!$ok) {
				$this->render('profile/edit', ['user' => $user, 'errors' => ['Failed to update profile']]);
				return;
			}
		}

		$updated = $this->userModel->findById($id);
		if (!empty($updated['name'])) $_SESSION['user_name'] = $updated['name'];

		$this->redirect('profile');
	}

	/**
	 * Delete current user's account. Supports POST (form) and JSON API.
	 */
	public function delete()
	{
		$user = $this->requireAuth();
		$id = $user['id'];

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$isApi) {
			$this->json(['error' => 'Method not allowed'], 405);
		}

		$ok = $this->userModel->delete($id);
		if (!$ok) {
			if ($isApi) $this->json(['error' => 'Could not delete account'], 500);

			$this->render('profile/index', ['user' => $user, 'errors' => ['Could not delete account']]);
			return;
		}

		$_SESSION = [];
		session_destroy();

		if ($isApi) $this->json(['success' => true]);

		$this->redirect();
	}
}
