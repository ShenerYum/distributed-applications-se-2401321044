<?php

declare(strict_types=1);
if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

use App\Core\Container;
use App\Core\Database;

return function (Container $container) {

	$config = require __ROOT__ . '/config/config.php';

	$container->singleton(Database::class, function () use ($config) {
		return new Database($config);
	});

	$container->singleton(PDO::class, function ($container) {
		return $container->make(Database::class)->pdo();
	});
};
