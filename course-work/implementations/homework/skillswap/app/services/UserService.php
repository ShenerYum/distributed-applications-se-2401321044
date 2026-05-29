<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Models\UserModel;

class UserService extends Service
{
	public function __construct(
		private UserModel $users
	) {}


	public function getUser(string $id): array
	{
		$this->validateUUID($id);

		$user = $this->users->findById($id);
		if (!$user) {
			throw new \InvalidArgumentException('User not found', 404);
		}

		return $user;
	}

	public function createUser(array $data): array
	{
		$this->validateRequiredFields($data, ['name', 'email', 'password', 'password_confirm']);

		$user = $this->users->createUser($data);
		if (!$user) {
			throw new \RuntimeException('Failed to create user', 500);
		}

		return $user;
	}

	public function updateUser(string $id, array $data): array
	{
		$this->validateRequiredFields($data, ['name', 'email']);

		$updated = $this->users->updateUser($id, $data);
		if (!$updated) {
			throw new \RuntimeException('Failed to update user', 500);
		}

		return $updated;
	}

	public function deleteUser(string $id): void
	{
		if (!$this->users->deleteUser($id)) {
			throw new \RuntimeException('Failed to delete user', 500);
		}
	}


	public function listUsers(array $filters, int $limit = 20, int $offset = 0): array
	{
		if (empty($filters)) {
			return $this->users->findAll($limit, $offset);
		}

		$this->validateFilters($filters, ['id', 'name', 'email']);

		return $this->users->findBy($filters, $limit, $offset);
	}


	public function getByName(string $name, int $limit = 20, int $offset = 0): array
	{
		return $this->users->findByName($name, $limit, $offset);
	}
}
