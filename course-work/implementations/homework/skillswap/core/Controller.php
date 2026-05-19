<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__FILE__, 2));
}

class Controller
{
	public function render(string $view, array $data = [])
	{
		$viewFile = __ROOT__ . '/app/views/' . $view . '.php';

		if (!file_exists($viewFile)) {
			throw new RuntimeException("View not found: $viewFile");
		}

		extract($data, EXTR_SKIP);
		require $viewFile;
	}

	protected function json($data, int $status = 200)
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data);
		exit;
	}
}
