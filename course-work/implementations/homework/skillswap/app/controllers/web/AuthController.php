<?php

namespace App\Controllers\Web;

use App\Core\WebController;
use App\Services\AuthService;

use App\Core\Response;

/**
 * AuthController is responsible for handling user authentication operations such as registration,
 * login, and logout. It interacts with the UserModel to perform database operations related to
 * user accounts. The controller ensures that user input is validated and that appropriate responses
 * are returned for each operation.
 */
class AuthController extends WebController
{

	public function __construct(private AuthService $authService)
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
	}

	public function registerPage(): Response
	{
		return $this->render('register');
	}

	public function register(): Response
	{
		$input = $_POST;
		try {
			$_SESSION['user_data'] = $this->authService->register([
				'name' => trim($input['name'] ?? ''),
				'email' => trim($input['email'] ?? ''),
				'password' => $input['password'] ?? '',
				'password_confirm' => $input['password_confirm'] ?? ''
			]);

			return $this->redirect('');
		} catch (\InvalidArgumentException $e) {
			return $this->retry('register', $e);
		}
	}

	public function loginPage(): Response
	{
		return $this->render('login');
	}

	public function login(): Response
	{
		$input = $_POST;

		try {
			$_SESSION['user_data'] = $this->authService->login(
				trim($input['email'] ?? ''),
				$input['password'] ?? ''
			);

			return $this->redirect('');
		} catch (\InvalidArgumentException $e) {
			return $this->retry('login', $e);
		}
	}

	public function logout(): Response
	{
		$this->authService->logout();
		return $this->redirect('');
	}
}
