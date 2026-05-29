<?php

declare(strict_types=1);

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

error_reporting(E_ALL);
ini_set('display_errors', '1');


require __ROOT__ . '/vendor/autoload.php';


use App\Core\View;
use App\Core\Response;
use App\Exceptions\NotFoundException;

session_start();



$config = require __ROOT__ . '/config/config.php';

try {
	$app = require __ROOT__ . '/bootstrap/app.php';

	$app->run();
} catch (Throwable $e) {
	$status = is_int($e->getCode()) && !($e->getCode() < 400 || $e->getCode() > 599)
		? $e->getCode()
		: 500;

	$response = $config['app_env'] === 'development'
		? (new Response())->error($e->getMessage(), $status)
		: (new Response())->error('Internal Server Error', 500);

	$response->send();
}

// $app = require __ROOT__ . '/bootstrap/app.php';

// $app->run();
