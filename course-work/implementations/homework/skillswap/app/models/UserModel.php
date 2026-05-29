<?php

namespace App\Models;

use App\Core\Model;

class UserModel extends Model
{
	public function __construct(private \PDO $db)
	{
		parent::__construct($db);
		$this->setTable('Users');
		$this->setPrimaryKey('id');
	}

	public function findByName(string $name, ?int $limit = null, ?int $offset = null): array
	{
		return $this->findBy(['name' => $name], $limit, $offset);
	}

	public function findByEmail(string $email): ?array
	{
		$rows = $this->findBy(['email' => $email], 1);
		return $rows[0] ?? null;
	}

	public function createUser(array $data): ?array
	{
		return $this->findById($this->create($data));
	}

	public function updateUser(string $id, array $data): array|false
	{
		return $this->update($id, $data) ? $this->findById($id) : false;
	}

	public function deleteUser(string $id): bool
	{
		return $this->delete($id);
	}
}
