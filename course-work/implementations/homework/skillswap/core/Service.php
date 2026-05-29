<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Response;

/**
 * Base service class with common
 * methods for both WebService and ApiService.
 */
abstract class Service
{
	public function validateUUID(string $uuid): void
	{
		if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($uuid))) {
			throw new \InvalidArgumentException('Invalid UUID format', 422);
		}
	}

	public function validateRequiredFields(array $data, array $fields): void
	{
		$data = array_filter($data, static fn($v) => ($v !== null && $v !== ''));

		if (empty($data)) {
			throw new \InvalidArgumentException('Input data cannot be empty', 400);
		}

		foreach ($fields as $f) {
			if (!(array_key_exists($f, $data) && trim((string)$data[$f]))) {
				throw new \InvalidArgumentException("Field '$f' is required and cannot be empty", 400);
			}

			switch ($f) {
				case 'email':
					if (!filter_var($data[$f], FILTER_VALIDATE_EMAIL))
						throw new \InvalidArgumentException('Invalid email', 400);
					break;
				case 'password':
					if (strlen((string)$data[$f]) < 6)
						throw new \InvalidArgumentException('Password must be at least 6 characters long', 400);
					break;
				case 'password_confirm':
					if (!isset($data['password']) || $data['password'] !== $data[$f])
						throw new \InvalidArgumentException('Passwords do not match', 422);
					break;
				case 'difficulty':
					if (!is_numeric($data[$f]) || (int)$data[$f] < 1 || (int)$data[$f] > 5)
						throw new \InvalidArgumentException('Difficulty must be an integer between 1 and 5', 400);
					break;
			}
		}
	}

	public function validateFilters(array $filters, array $allowed): void
	{
		foreach ($filters as $key => $value) {
			if (!in_array($key, $allowed, true)) {
				throw new \InvalidArgumentException("Invalid key: '$key'", 400);
			}
		}
	}
}
