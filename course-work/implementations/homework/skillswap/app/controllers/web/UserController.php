<?php

namespace App\Controllers\Web;

use App\Core\Response;
use App\Core\WebController;

use App\Services\{AuthService, UserService};


class UserController extends WebController
{

	public function __construct(
		private AuthService $authService,
		private UserService $userService
	) {
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	}

	/**
	 * Render all users view.
	 */
	public function index()
	{
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
		$filters = [
			'name' => !empty($_GET['name']) ? trim($_GET['name']) : null,
			'email' => !empty($_GET['email']) ? trim($_GET['email']) : null
		];

		try {
			$currentUser = $this->authService->getCurrentUser();

			$users = $this->userService->listUsers($filters, $limit, $offset);
			$users = array_filter($users, static fn($u) => $u['id'] !== $currentUser['id']);

			return $this->render('users/index', ['users' => $users]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	/**
	 * Show a single user's profile (users/{id}).
	 */
	public function userPage(string $id)
	{
		try {
			$user = $this->userService->getUser($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		if (isset($user['password'])) unset($user['password']);

		$currentUser = $this->authService->getCurrentUser();
		$isAdmin = (bool)$currentUser['is_admin'] ?? false;

		if (isset($currentUser['id']) && $currentUser['id'] === $id) {
			return $this->redirect('profile/index');
		}

		return $this->render('users/show', ['user' => $user, 'is_admin' => $isAdmin]);
	}

	public function editPage(string $id): Response
	{
		try {
			$user = $this->userService->getUser($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		if (isset($user['password'])) unset($user['password']);

		return $this->render('user/edit', ['user' => $user]);
	}

	public function edit(string $id): Response
	{
		try {
			$user = $this->userService->getUser($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$input = $_POST;
		try {
			$user = $this->userService->updateUser($user['id'], [
				'name' => trim($input['name'] ?? ''),
				'email' => trim($input['email'] ?? ''),
				'password' => $input['password'] ?? '',
				'password_confirm' => $input['password_confirm'] ?? ''
			]);

			return $this->render('users/show', ['user' => $user]);
		} catch (\InvalidArgumentException $e) {
			return $this->retry('user/edit', $e, ['user' => $user]);
		}
	}

	public function delete(string $id): Response
	{
		try {
			$this->userService->deleteUser($id);

			return $this->redirect('users');
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}
}
