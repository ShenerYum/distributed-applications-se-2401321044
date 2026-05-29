<?php

namespace App\Models;

use App\Core\Model;

class MatchModel extends Model
{
	public function __construct(private \PDO $db)
	{
		parent::__construct($db);
		$this->setTable('Matches');
		$this->setPrimaryKey('id');
	}


	public function createMatch(array $data): ?array
	{
		return $this->findById($this->create($data));
	}

	public function updateMatch(string $id, array $data): array|false
	{
		return $this->update($id, $data) ? $this->findById($id) : false;
	}

	public function deleteMatch(string $id): bool
	{
		return $this->delete($id);
	}
}
