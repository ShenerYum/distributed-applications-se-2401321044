<?php

declare(strict_types=1);

namespace App\Core;

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

use App\Core\Response;

/**
 * Controller for web (HTML) endpoints.
 */
class WebController extends Controller
{
	protected function redirect(string $url, int $status = 303): Response
	{
		return (new Response())->redirect($url, $status);
	}

	protected function fail(\Exception $e): Response
	{
		return (new Response())->error(
			$e->getMessage(),
			$e->getCode()
		);
	}

	protected function render(string $view, array $data = [], int $status = 200): Response
	{
		try {
			return (new Response())
				->setStatus($status)
				->html(View::render($view, $data));
		} catch (\RuntimeException $e) {
			return $this->fail($e);
		}
	}

	protected function retry(string $view, \Exception $e, array $data = []): Response
	{
		return $this->render($view, array_merge(['errors' => $e->getMessage()], $data), $e->getCode());
	}
}
