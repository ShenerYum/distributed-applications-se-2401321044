<?php

namespace App\Core;

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

/**
 * View class for rendering HTML views. Provides a static method to render a view file
 * with optional data passed as an associative array.
 */
class View
{
	protected static string $basePath = __ROOT__ . '/app/views/';

	/**
	 * Render a view file with optional data.
	 * 
	 * @param string $view The name of the view file (relative to the views directory, without .php extension).
	 * @param array $data An associative array of data to extract and make available to the view.
	 * @return string The rendered HTML content of the view.
	 * @throws \RuntimeException If the specified view file does not exist.
	 */
	public static function render(string $view, array $data = []): string
	{
		$viewFile = self::$basePath . $view . '.php';

		if (!file_exists($viewFile)) {
			throw new \RuntimeException("View not found: {$viewFile}", 404);
		}

		extract($data, EXTR_SKIP);

		ob_start();
		require $viewFile;

		return ob_get_clean();
	}
}
