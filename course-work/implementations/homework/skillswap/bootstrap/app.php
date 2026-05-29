<?php

declare(strict_types=1);

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

use App\Core\Container;
use App\Core\Router;

// Create container
$container = new Container();

// Load config
$config = require __ROOT__ . '/config/config.php';

// Register services
$register = require __ROOT__ . '/bootstrap/container.php';
$register($container);

// Create router with DI container
$router = new Router($container);

// Register routes
require __ROOT__ . '/routes/web.php';
require __ROOT__ . '/routes/api.php';

// Return app instance
return new class($router) {
	public function __construct(private Router $router) {}

	public function run(): void
	{
		$this->router->dispatch();
	}
};
