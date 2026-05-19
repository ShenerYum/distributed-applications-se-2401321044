<?php

// Front controller / bootstrap

session_start();

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__FILE__, 2));
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
	switch ($route) {
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
		echo '<h1>Application error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
	}
}
