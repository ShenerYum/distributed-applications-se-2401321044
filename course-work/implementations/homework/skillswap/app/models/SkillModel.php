<?php

namespace App\Models;

use App\Core\Model;

class SkillModel extends Model
{
	public function __construct(private \PDO $db)
	{
		parent::__construct($db);
		$this->setTable('Skills');
		$this->setPrimaryKey('id');
	}

	public function createSkill(array $data): ?array
	{
		return $this->findById($this->create($data));
	}

	public function updateSkill(string $id, array $data): array|false
	{
		return $this->update($id, $data) ? $this->findById($id) : false;
	}

	public function deleteSkill(string $id): bool
	{
		return $this->delete($id);
	}
}
