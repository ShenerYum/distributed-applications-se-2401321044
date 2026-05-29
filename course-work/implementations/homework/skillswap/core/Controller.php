<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller class with common
 * methods for both WebController and ApiController.
 */
abstract class Controller
{
	/**
	 * Validate if a string is a valid UUID (version 4).
	 * This is used for validating IDs in routes and requests.
	 * @param string $uuid The string to validate as a UUID.
	 * @return bool True if the string is a valid UUID, false otherwise.
	 */
	protected function isValidUUID(string $uuid): bool
	{
		return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($uuid));
	}
}
