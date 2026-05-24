<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

/**
 * Base Controller class providing common functionality for all controllers.
 * Includes methods for rendering views, loading models, sending JSON responses, and handling authentication.
 */
class Controller
{
	/**
	 * Render a view file from app/views with the provided data.
	 *
	 * @param string $view The name of the view file (without .php)
	 * @param array $data An associative array of data to extract for the view
	 */
	public function render(string $view, array $data = [])
	{
		$viewFile = __ROOT__ . '/app/views/' . $view . '.php';

		if (!file_exists($viewFile)) {
			throw new RuntimeException("View not found: $viewFile");
		}

		extract($data, EXTR_SKIP);
		require $viewFile;
	}

	/**
	 * Load and return a model instance from app/models.
	 *
	 * @param string $modelName Class/file name (without .php)
	 * @return object
	 */
	public function loadModel(string $modelName)
	{
		$modelFile = __ROOT__ . '/app/models/' . $modelName . '.php';
		if (!file_exists($modelFile)) {
			throw new RuntimeException("Model file not found: $modelFile");
		}

		require_once $modelFile;

		if (!class_exists($modelName)) {
			throw new RuntimeException("Model class not found: $modelName");
		}

		return new $modelName();
	}

	/**
	 * Load a view file. If $return is true the output is returned as string.
	 *
	 * @param string $view
	 * @param array $data
	 * @param bool $return
	 * @return void|string
	 */
	public function loadView(string $view, array $data = [], bool $return = false)
	{
		$viewFile = __ROOT__ . '/app/views/' . $view . '.php';
		if (!file_exists($viewFile)) {
			throw new RuntimeException("View not found: $viewFile");
		}

		extract($data, EXTR_SKIP);

		if ($return) {
			ob_start();
			require $viewFile;
			return ob_get_clean();
		}

		require $viewFile;
	}

	/**
	 * Send a JSON response with the given data and HTTP status code, then exit.
	 * 
	 * @param array $data The data to encode as JSON and send in the response body.
	 * @param int $status The HTTP status code to send (default 200).
	 */
	protected function json(array $data, int $status = 200)
	{
		http_response_code($status);
		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
		$isApi = strpos($accept, 'application/json') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

		// If client does not request JSON and this is an error response,
		// render the appropriate HTML error page instead of returning raw JSON.
		if (!$isApi && $status >= 400 && isset($data['error'])) {
			// provide the error message to the view as $errorMessage
			$errorMessage = $data['error'];

			$this->render('errors/' . ($status ?? 500), ['errorMessage' => $errorMessage]);
			exit;
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data);
		exit;
	}

	/**
	 * Return current authenticated user or null.
	 * Uses `$_SESSION['user_id']`.
	 * 
	 * @return array|null
	 */
	protected function getCurrentUser(): ?array
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$uid = $_SESSION['user_id'] ?? null;
		if (!$uid) {
			return null;
		}

		// lazy-load user model
		try {
			$userModel = $this->loadModel('UserModel');
			$user = $userModel->findById($uid);
			if ($user && isset($user['password'])) {
				unset($user['password']);
			}
			return $user ?: null;
		} catch (Throwable $e) {
			return null;
		}
	}

	/**
	 * Require authentication and return user; sends 401 JSON when not authenticated.
	 * 
	 * @return array
	 */
	protected function requireAuth(): array
	{
		$user = $this->getCurrentUser();

		if (!$user) {
			$this->redirect('auth/login');
			// $this->json(['error' => 'Authentication required'], 401);
		}
		return $user;
	}


	/**
	 * Require admin privileges; sends 403 JSON when not an admin.
	 * Checks `is_admin` field in user record or session.
	 * 
	 * @return array
	 */
	protected function requireAdmin(): array
	{
		$user = $this->requireAuth();
		$isAdmin = (isset($user['is_admin']) && $user['is_admin']);

		if (!$isAdmin) {
			$this->json(['error' => 'Admin privileges required'], 403);
		}

		return $user;
	}

	/**
	 * Validate UUID format (36 char including hyphens).
	 * 
	 * @param string $uuid
	 * @return bool
	 */
	protected function isValidUUID(string $uuid): bool
	{
		$uuid = trim($uuid);
		return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
	}

	/**
	 * Redirect to a given URL path.
	 * 
	 * @param string $url The URL path to redirect to (default is home '/').
	 */
	protected function redirect(string $url = '')
	{
		header('Location: /' . $url);
		exit;
	}
}
