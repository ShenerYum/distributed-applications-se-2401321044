<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * UsersController placeholder. Not implemented yet; returns 501 for HTML/API endpoints.
 */
class UsersController extends Controller
{
	/** @var UserModel */
	private $userModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
		$this->userModel = $this->loadModel('UserModel');
	}

	/**
	 * Render all users view.
	 */
	public function index()
	{
		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
		$users = $this->userModel->findAll();

		foreach ($users as &$u) {
			if (isset($u['password'])) unset($u['password']);
		}

		if ($isApi) {
			$this->json(['users' => $users]);
		}

		$this->render('users/index', ['users' => $users]);
	}

	/**
	 * Show a single user's profile (users/{id}).
	 */
	public function show(string $id)
	{
		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'User not found'], 404);
		}

		$user = $this->userModel->findById($id);
		if (!$user) {
			$this->json(['error' => 'User not found'], 404);
		}
		if (isset($user['password'])) unset($user['password']);

		$current = $this->getCurrentUser();
		$isAdmin = !empty($current['is_admin']) || !empty($_SESSION['is_admin']);

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
		if ($isApi) $this->json(['user' => $user]);

		$this->render('users/show', ['user' => $user, 'is_admin' => $isAdmin]);
	}

	/**
	 * Show offers posted by a user: users/{id}/offers
	 */
	public function offers(string $id)
	{
		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'User not found'], 404);
		}

		$offerModel = $this->loadModel('OfferModel');
		$offers = $offerModel->getOffersByUser($id);

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
		if ($isApi) $this->json(['offers' => $offers]);

		$this->render('users/offers', ['offers' => $offers, 'user_id' => $id]);
	}

	/**
	 * Show requests posted by a user: users/{id}/requests
	 */
	public function requests(string $id)
	{
		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'User not found'], 404);
		}

		$requestModel = $this->loadModel('RequestModel');
		$requests = $requestModel->getRequestsByUser($id);

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
		if ($isApi) $this->json(['requests' => $requests]);

		$this->render('users/requests', ['requests' => $requests, 'user_id' => $id]);
	}

	/**
	 * Show reviews received by a user: users/{id}/reviews
	 */
	public function reviews(string $id)
	{
		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'User not found'], 404);
		}

		$reviewModel = $this->loadModel('ReviewModel');
		$reviews = $reviewModel->getReviewsByUser($id, 200, 0);

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
		if ($isApi) $this->json(['reviews' => $reviews]);

		$this->render('users/reviews', ['reviews' => $reviews, 'user_id' => $id]);
	}

	/**
	 * Edit a user (admin only). GET renders form, POST applies update and redirects.
	 */
	public function edit(string $id)
	{
		$current = $this->requireAdmin();

		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'User not found'], 404);
		}

		$user = $this->userModel->findById($id);
		if (!$user) {
			$this->json(['error' => 'User not found'], 404);
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			if (isset($user['password'])) unset($user['password']);
			$this->render('users/edit', ['user' => $user]);
			return;
		}

		// Handle update
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';
		$password_confirm = $_POST['password_confirm'] ?? '';

		$errors = [];
		if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
		if ($password !== '' && $password !== $password_confirm) $errors[] = 'Passwords do not match';

		$postIsAdmin = isset($_POST['is_admin']) ? 1 : 0;

		if (isset($current['id']) && $current['id'] === $id && $postIsAdmin === 0) {
			$postIsAdmin = 1;
		}

		if (!empty($errors)) {
			$this->render('users/edit', ['user' => $user, 'errors' => $errors]);
			return;
		}

		$data = [];
		if ($name !== '') $data['name'] = $name;
		if ($email !== '') $data['email'] = $email;
		if ($password !== '') $data['password'] = password_hash($password, PASSWORD_DEFAULT);

		if (isset($postIsAdmin)) {
			$data['is_admin'] = $postIsAdmin;
		}

		if (!empty($data)) {
			$ok = $this->userModel->update($id, $data);
			if (!$ok) {
				$this->render('users/edit', ['user' => $user, 'errors' => ['Failed to update user']]);
				return;
			}

			$updated = $this->userModel->findById($id);
			if (!empty($updated) && isset($current['id']) && $current['id'] === $id) {
				if (!empty($updated['name'])) $_SESSION['user_name'] = $updated['name'];

				$_SESSION['is_admin'] = !empty($updated['is_admin']) ? 1 : 0;
			}
		}

		$this->redirect('users/' . $id);
	}

	/**
	 * Delete a user (admin only). POST form or JSON supported.
	 */
	public function delete(string $id)
	{
		$current = $this->requireAdmin();

		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'User not found'], 404);
		}

		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$isApi) {
			$this->json(['error' => 'Method not allowed'], 405);
		}

		$ok = $this->userModel->delete($id);
		if (!$ok) {
			if ($isApi)
				$this->json(['error' => 'Could not delete user'], 500);
			else
				$this->json(['error' => 'Internal server error'], 500);
		}

		if (isset($current['id']) && $current['id'] === $id) {
			$_SESSION = [];
			session_destroy();

			if ($isApi) $this->json(['success' => true]);

			$this->redirect();
		}

		if ($isApi) $this->json(['success' => true]);

		$this->redirect('users');
	}
}
