<?php

namespace App\Controllers\Web;

use App\Core\WebController;
use App\Core\Response;
use App\Services\ProfileService;
use App\Services\AuthService;

/**
 * ProfileController handles rendering and updating the current user's profile.
 */
class ProfileController extends WebController
{
	public function __construct(
		private ProfileService $profileService,
		private AuthService $authService
	) {
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	}

	/**
	 * Render profile view for current authenticated user.
	 */
	public function index(): Response
	{
		return $this->render(
			'profile/index',
			['user' => $this->authService->getCurrentUser()]
		);
	}

	public function editPage(): Response
	{
		return $this->render(
			'profile/edit',
			['user' => $this->authService->getCurrentUser()]
		);
	}

	public function edit(): Response
	{
		$user = $this->authService->getCurrentUser();
		$input = $_POST;
		try {
			$_SESSION['user_data'] = $this->profileService->updateProfile($user['id'], [
				'name' => trim($input['name'] ?? ''),
				'email' => trim($input['email'] ?? ''),
				'password' => $input['password'] ?? '',
				'password_confirm' => $input['password_confirm'] ?? ''
			]);

			return $this->redirect('profile');
		} catch (\InvalidArgumentException $e) {
			return $this->retry('profile/edit', $e, ['user' => $user]);
		}
	}

	/**
	 * Delete current user's account.
	 */
	public function delete(): Response
	{
		try {
			$this->profileService->deleteProfile(
				$this->authService->getCurrentUser()['id']
			);
			$_SESSION = [];
			session_destroy();

			return $this->redirect('');
		} catch (\InvalidArgumentException $e) {
			return $this->fail($e);
		}
	}

	/**
	 * Load user's received reviews page.
	 */
	public function reviewsPage(): Response
	{
		return $this->render('profile/reviews');
	}
}
