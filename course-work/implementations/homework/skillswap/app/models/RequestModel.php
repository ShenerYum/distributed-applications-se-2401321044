<?php

namespace App\Models;

use App\Core\Model;

class RequestModel extends Model
{
	public function __construct(private \PDO $db)
	{
		parent::__construct($db);
		$this->setTable('Requests');
		$this->setPrimaryKey('id');
	}


	public function createRequest(array $data): ?array
	{
		return $this->findById($this->create($data));
	}

	public function updateRequest(string $id, array $data): array|false
	{
		return $this->update($id, $data) ? $this->findById($id) : false;
	}

	public function deleteRequest(string $id): bool
	{
		return $this->delete($id);
	}
}
