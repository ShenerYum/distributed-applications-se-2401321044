<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * UserController is responsible for handling user-related operations such as retrieving
 * the authenticated user's profile, updating the user's profile, deleting the user's account,
 * and admin operations for listing and retrieving users.
 */
class UserController extends Controller
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
	 * GET the profile of the currently authenticated user.
	 * 
	 * @return array JSON response containing the user profile or an error message.
	 */
	public function profile()
	{
		$user = $this->requireAuth();
		return $this->json(['success' => true, 'data' => $user]);
	}

	/**
	 * PUT update the profile of the currently authenticated user. Accepts name, email, and password fields.
	 * 
	 * @return array JSON response containing the updated user profile or an error message.
	 */
	public function update()
	{
		$user = $this->requireAuth();
		$id = $user['id'];

		$data = [];

		if (isset($_POST['name'])) {
			$data['name'] = trim($_POST['name']);
		}

		if (isset($_POST['email'])) {
			$email = trim($_POST['email']);
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				return $this->json(['error' => 'Invalid email'], 422);
			}

			$existing = $this->userModel->findByEmail($email);
			if ($existing && $existing['id'] !== $id) {
				return $this->json(['error' => 'Email already in use'], 409);
			}
			$data['email'] = $email;
		}

		if (isset($_POST['password']) && $_POST['password'] !== '') {
			$data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
		}

		if (empty($data)) {
			return $this->json(['error' => 'No update data provided'], 422);
		}

		$ok = $this->userModel->update($id, $data);
		if (!$ok) {
			return $this->json(['error' => 'Failed to update profile'], 500);
		}

		$updated = $this->userModel->findById($id);
		if ($updated && isset($updated['password'])) unset($updated['password']);
		return $this->json(['success' => true, 'data' => $updated]);
	}

	/**
	 * DELETE delete current user's account.
	 * 
	 * @return array JSON response indicating success or an error message.
	 */
	public function delete()
	{
		$user = $this->requireAuth();
		$id = $user['id'];

		$ok = $this->userModel->delete($id);
		if (!$ok) {
			return $this->json(['error' => 'Could not delete account'], 500);
		}

		$_SESSION = [];
		session_destroy();

		return $this->json(['success' => true]);
	}

	/**
	 * Admin: list users.
	 * 
	 * @return array JSON response containing the list of users or an error message.
	 */
	public function list()
	{
		$user = $this->requireAuth();
		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin) {
			return $this->json(['error' => 'Admin privileges required'], 403);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		$users = $this->userModel->findAll($limit, $offset);
		// strip passwords
		foreach ($users as &$u) {
			if (isset($u['password'])) unset($u['password']);
		}

		return $this->json(['success' => true, 'data' => $users]);
	}

	/**
	 * Admin: get user by id.
	 * 
	 * @return array JSON response containing the user data or an error message.
	 */
	public function get()
	{
		$user = $this->requireAuth();
		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin) {
			return $this->json(['error' => 'Admin privileges required'], 403);
		}

		$id = trim($_GET['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid user id required'], 422);
		}

		$u = $this->userModel->findById($id);
		if (!$u) return $this->json(['error' => 'User not found'], 404);
		if (isset($u['password'])) unset($u['password']);
		return $this->json(['success' => true, 'data' => $u]);
	}
}
