<?php

namespace App\Models;

use App\Core\Model;

class ReviewModel extends Model
{
	public function __construct(private \PDO $db)
	{
		parent::__construct($db);
		$this->setTable('Reviews');
		$this->setPrimaryKey('id');
	}

	public function createReview(array $data): ?array
	{
		return $this->findById($this->create($data));
	}

	public function updateReview(string $id, array $data): array|false
	{
		return $this->update($id, $data) ? $this->findById($id) : false;
	}

	public function deleteReview(string $id): bool
	{
		return $this->delete($id);
	}
}
