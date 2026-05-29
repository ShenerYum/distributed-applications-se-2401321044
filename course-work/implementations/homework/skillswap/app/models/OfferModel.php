<?php

namespace App\Models;

use App\Core\Model;

class OfferModel extends Model
{
	public function __construct(private \PDO $db)
	{
		parent::__construct($db);
		$this->setTable('Offers');
		$this->setPrimaryKey('id');
	}


	public function createOffer(array $data): ?array
	{
		return $this->findById($this->create($data));
	}

	public function updateOffer(string $id, array $data): array|false
	{
		return $this->update($id, $data) ? $this->findById($id) : false;
	}

	public function deleteOffer(string $id): bool
	{
		return $this->delete($id);
	}
}
