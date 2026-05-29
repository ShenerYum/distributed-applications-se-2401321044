<?php

namespace App\Middleware;

use App\Core\Response;
use App\Services\AuthService;

class AdminMiddleware
{
	public function __construct(private AuthService $authService) {}

	public function handle(callable $next): Response
	{
		try {
			$this->authService->requireAdmin();
		} catch (\Exception $e) {
			return (new Response())->error(
				$e->getCode(),
				$e->getMessage()
			);
		}

		return $next();
	}
}
