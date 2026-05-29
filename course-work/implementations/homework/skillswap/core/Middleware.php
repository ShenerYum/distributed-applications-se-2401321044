<?php

namespace App\Core;

interface Middleware
{
	/**
	 * Handle the incoming request.
	 *
	 * @return Response|null Return a Response to short-circuit the request, or null to continue processing.
	 */
	public function handle(): ?Response;
}
