<?php

declare(strict_types=1);

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

if (!function_exists('loadEnvFile')) {
	function loadEnvFile(string $path): array
	{
		if (!file_exists($path)) {
			return [];
		}

		$env = [];
		foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#')) {
				continue;
			}

			$parts = explode('=', $line, 2);
			if (count($parts) !== 2) {
				continue;
			}

			$name = trim($parts[0]);
			$value = trim($parts[1]);
			$value = trim($value, "\"'");
			$env[$name] = $value;
		}

		return $env;
	}
}

$baseConfig = [
	'db' => require __ROOT__ . '/config/database.php',
	'app_base_url' => ''
];

$env = loadEnvFile(__ROOT__ . '/.env');
if (!empty($env)) {
	$map = [
		'DB_HOST' => 'host',
		'DB_DATABASE' => 'database',
		'DB_USERNAME' => 'username',
		'DB_PASSWORD' => 'password',
		'DB_CHARSET' => 'charset',
	];

	foreach ($map as $envKey => $configKey) {
		if (isset($env[$envKey])) {
			$baseConfig['db'][$configKey] = $env[$envKey];
		}
	}

	if (isset($env['APP_BASE_URL'])) {
		$baseConfig['app_base_url'] = $env['APP_BASE_URL'];
	}
}

$baseConfig['app_env'] = $env['APP_ENV'] ?? 'production';

return $baseConfig;
