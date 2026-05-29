<?php

namespace App\Middleware;

use App\Core\Response;
use App\Services\AuthService;

class AuthMiddleware
{
	public function __construct(private AuthService $authService) {}

	public function handle(callable $next): Response
	{
		try {
			$this->authService->requireAuth();
		} catch (\Exception $e) {
			return (new Response())->redirect('login');
		}

		return $next();
	}
}
