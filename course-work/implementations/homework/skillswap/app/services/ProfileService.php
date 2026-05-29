<?php

namespace App\Services;

use App\Core\Service;

use App\Models\UserModel;

class ProfileService extends Service
{
	public function __construct(private UserModel $users) {}

	/**
	 * Update current user's profile with name, email, and/or password.
	 * @param string $id User ID
	 * @param array $data Associative array with keys 'name', 'email', 'password', 'password_confirm'
	 * @return array Updated user data
	 * @throws \InvalidArgumentException if validation fails
	 */
	public function updateProfile(string $id, array $data): array
	{
		$this->validateRequiredFields($data, ['name', 'email']);

		if (!trim($data['password']))
			unset($data['password']);
		else $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

		unset($data['password_confirm']);

		if (!$this->users->update($id, $data)) {
			throw new \InvalidArgumentException('Failed to update profile', 500);
		}

		$user = $this->users->findById($id);
		if (!$user) {
			throw new \InvalidArgumentException('User not found', 404);
		}

		unset($user['password']);
		return $user;
	}

	/**
	 * Delete user account by ID.
	 * @param string $id User ID
	 */
	public function deleteProfile(string $id): void
	{
		if (!$this->users->delete($id)) {
			throw new \InvalidArgumentException('Failed to delete profile', 500);
		}
	}
}
