<?php

declare(strict_types=1);

namespace App\Core;

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

use App\Core\Response;

/**
 * Controller for API endpoints.
 */
class ApiController extends Controller
{
	protected function json(array $data, int $status = 200): Response
	{
		return (new Response())
			->setStatus($status)
			->json($data);
	}
}
