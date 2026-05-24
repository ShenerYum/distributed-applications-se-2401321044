<?php

// Front controller / bootstrap

session_start();

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

$config = require_once __ROOT__ . '/config/config.php';

require_once __ROOT__ . '/core/Database.php';
require_once __ROOT__ . '/core/Model.php';
require_once __ROOT__ . '/core/Controller.php';

// Initialize database singleton
Database::getInstance($config);

// Basic routing

$route = $_GET['route'] ?? 'home';

try {
	if (empty($_SESSION['user_id'])) {
		$publicRoutes = ['home', 'login', 'register'];
		if (!in_array($route, $publicRoutes, true)) {
			require_once __ROOT__ . '/app/controllers/AuthController.php';
			$controller = new AuthController();
			$controller->login();
			exit;
		}
	}
	// quick matching for users routes (supports dynamic ids)
	if (strpos($route, 'users') === 0) {
		$usersControllerFile = __ROOT__ . '/app/controllers/UsersController.php';
		if (!file_exists($usersControllerFile)) {
			http_response_code(501);
			require __ROOT__ . '/app/views/errors/501.php';
			return;
		}

		require_once $usersControllerFile;
		$uc = new UsersController();

		if ($route === 'users') {
			// admin-only users listing
			if (empty($_SESSION['is_admin'])) {
				http_response_code(403);
				require __ROOT__ . '/app/views/errors/403.php';
				return;
			}
			$uc->index();
			return;
		}

		// users/{id}/edit
		if (preg_match('#^users/([^/]+)/edit$#', $route, $m)) {
			$uc->edit($m[1]);
			return;
		}

		// users/{id}/delete
		if (preg_match('#^users/([^/]+)/delete$#', $route, $m)) {
			$uc->delete($m[1]);
			return;
		}

		// users/details/{id} (legacy) -> show
		if (
			preg_match('#^users/details/([^/]+)$#', $route, $m)
			|| preg_match('#^users/([^/]+)$#', $route, $m)
		) {
			$uc->show($m[1]);
			return;
		}

		// users/{id}/offers
		if (preg_match('#^users/([^/]+)/offers$#', $route, $m)) {
			$uc->offers($m[1]);
			return;
		}

		// users/{id}/requests
		if (preg_match('#^users/([^/]+)/requests$#', $route, $m)) {
			$uc->requests($m[1]);
			return;
		}

		// users/{id}/reviews
		if (preg_match('#^users/([^/]+)/reviews$#', $route, $m)) {
			$uc->reviews($m[1]);
			return;
		}

			http_response_code(404);
			require __ROOT__ . '/app/views/errors/404.php';
			return;
	}

	// quick matching for skills routes (supports dynamic ids)
	if (strpos($route, 'skills') === 0) {
		$skillsControllerFile = __ROOT__ . '/app/controllers/SkillController.php';
		if (!file_exists($skillsControllerFile)) {
			http_response_code(501);
			require __ROOT__ . '/app/views/errors/501.php';
			return;
		}

		require_once $skillsControllerFile;
		$sc = new SkillController();

		if ($route === 'skills') {
			$sc->index();
			return;
		}

		if (preg_match('#^skills/([^/]+)/edit$#', $route, $m)) {
			$sc->edit($m[1]);
			return;
		}

		if (preg_match('#^skills/([^/]+)/delete$#', $route, $m)) {
			// forward delete via POST
			$sc->delete($m[1]);
			return;
		}

		if ($route === 'skills/create') {
			$sc->create();
			return;
		}

			http_response_code(404);
			require __ROOT__ . '/app/views/errors/404.php';
			return;
	}

	// offers routes
	if (strpos($route, 'offers') === 0) {
		$offersControllerFile = __ROOT__ . '/app/controllers/OfferController.php';
		if (!file_exists($offersControllerFile)) {
			http_response_code(501);
			require __ROOT__ . '/app/views/errors/501.php';
			return;
		}

		require_once $offersControllerFile;
		$oc = new OfferController();

		if ($route === 'offers') {
			$oc->index();
			return;
		}

		if ($route === 'offers/create') {
			$oc->create();
			return;
		}

		if (preg_match('#^offers/([^/]+)/edit$#', $route, $m)) {
			$oc->edit($m[1]);
			return;
		}

		if (preg_match('#^offers/([^/]+)/delete$#', $route, $m)) {
			$oc->deleteById($m[1]);
			return;
		}

			http_response_code(404);
			require __ROOT__ . '/app/views/errors/404.php';
			return;
	}

	// requests routes
	if (strpos($route, 'requests') === 0) {
		$requestsControllerFile = __ROOT__ . '/app/controllers/RequestController.php';
		if (!file_exists($requestsControllerFile)) {
			http_response_code(501);
			require __ROOT__ . '/app/views/errors/501.php';
			return;
		}

		require_once $requestsControllerFile;
		$rc = new RequestController();

		if ($route === 'requests') {
			$rc->index();
			return;
		}

		if ($route === 'requests/create') {
			$rc->create();
			return;
		}

		if (preg_match('#^requests/([^/]+)/edit$#', $route, $m)) {
			$rc->edit($m[1]);
			return;
		}

		if (preg_match('#^requests/([^/]+)/delete$#', $route, $m)) {
			$rc->deleteById($m[1]);
			return;
		}

			http_response_code(404);
			require __ROOT__ . '/app/views/errors/404.php';
			return;
	}

	// matches routes
	if (strpos($route, 'matches') === 0) {
		$matchesControllerFile = __ROOT__ . '/app/controllers/MatchController.php';
		if (!file_exists($matchesControllerFile)) {
			http_response_code(501);
			require __ROOT__ . '/app/views/errors/501.php';
			return;
		}

		require_once $matchesControllerFile;
		$mc = new MatchController();

		if ($route === 'matches/create') {
			$mc->create();
			return;
		}

		if ($route === 'matches') {
			// admin-only index
			if (empty($_SESSION['is_admin'])) {
				http_response_code(403);
				require __ROOT__ . '/app/views/errors/403.php';
				return;
			}

			$mc->index();
			return;
		}

		if (preg_match('#^matches/([^/]+)/delete$#', $route, $m)) {
			// forward id to POST and call delete
			$_POST['id'] = $m[1];
			$mc->delete();
			return;
		}

		if ($route === 'matches/accept') {
			$mc->accept();
			return;
		}

		if ($route === 'matches/reject') {
			$mc->reject();
			return;
		}

		if ($route === 'matches/complete') {
			$mc->complete();
			return;
		}

			http_response_code(404);
			require __ROOT__ . '/app/views/errors/404.php';
			return;
	}

	// reviews routes
	if (strpos($route, 'reviews') === 0) {
		$reviewsControllerFile = __ROOT__ . '/app/controllers/ReviewController.php';
		if (!file_exists($reviewsControllerFile)) {
			http_response_code(501);
			require __ROOT__ . '/app/views/errors/501.php';
			return;
		}

		require_once $reviewsControllerFile;
		$rc = new ReviewController();

		if ($route === 'reviews') {
			// admin-only reviews listing
			if (empty($_SESSION['is_admin'])) {
				http_response_code(403);
				require __ROOT__ . '/app/views/errors/403.php';
				return;
			}
			$rc->index();
			return;
		}

		if ($route === 'reviews/create') {
			$rc->create();
			return;
		}

		if (preg_match('#^reviews/([^/]+)/edit$#', $route, $m)) {
			$rc->edit($m[1]);
			return;
		}

		if (preg_match('#^reviews/([^/]+)/delete$#', $route, $m)) {
			// forward id to POST and call delete
			$_POST['id'] = $m[1];
			$rc->delete();
			return;
		}

		http_response_code(404);
		require __ROOT__ . '/app/views/errors/404.php';
		return;
	}

	switch ($route) {
		case 'login':
			require_once __ROOT__ . '/app/controllers/AuthController.php';
			$controller = new AuthController();
			$controller->login();
			break;
		case 'register':
			require_once __ROOT__ . '/app/controllers/AuthController.php';
			$controller = new AuthController();
			$controller->register();
			break;
		case 'logout':
			require_once __ROOT__ . '/app/controllers/AuthController.php';
			$controller = new AuthController();
			$controller->logout();
			break;

		case 'profile':
			require_once __ROOT__ . '/app/controllers/ProfileController.php';
			$controller = new ProfileController();
			$controller->index();
			break;
		case 'profile/edit':
			require_once __ROOT__ . '/app/controllers/ProfileController.php';
			$controller = new ProfileController();
			$controller->edit();
			break;
		case 'profile/delete':
			require_once __ROOT__ . '/app/controllers/ProfileController.php';
			$controller = new ProfileController();
			$controller->delete();
			break;
		case 'profile/offers':
			require_once __ROOT__ . '/app/controllers/OfferController.php';
			$controller = new OfferController();
			$controller->myOffers();
			break;
		case 'profile/requests':
			require_once __ROOT__ . '/app/controllers/RequestController.php';
			$controller = new RequestController();
			$controller->myRequests();
			break;
		case 'profile/matches':
			require_once __ROOT__ . '/app/controllers/MatchController.php';
			$controller = new MatchController();
			$controller->myMatches();
			break;
		case 'profile/reviews':
			require_once __ROOT__ . '/app/controllers/ReviewController.php';
			$controller = new ReviewController();
			$controller->profileReviews();
			break;

		case 'api':
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['status' => 'ok', 'route' => 'api']);
			break;

		case 'home':
		default:
			$controller = new Controller();
			$controller->render('home');
			break;
	}
} catch (Exception $e) {
	http_response_code(500);
 	if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
 		echo json_encode(['error' => $e->getMessage()]);
 	} else {
 		$errorMessage = $e->getMessage();
 		require __ROOT__ . '/app/views/errors/500.php';
 	}
}
