<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Model.php';

/**
 * OfferModel is responsible for handling offer-related operations such as creating new offers, filtering offers by skill and availability, and retrieving offers for specific users.
 */
class OfferModel extends Model
{
	public function __construct()
	{
		parent::__construct();
		$this->setTable('Offers');
		$this->setPrimaryKey('id');
	}

	/**
	 * Create a new offer.
	 * 
	 * @param array $data
	 * 
	 * @return array Created offer record
	 * 
	 * @throws InvalidArgumentException if required fields are missing or invalid.
	 * @throws RuntimeException if database operations fail.
	 */
	public function createOffer(array $data): array
	{
		$skillId = trim($data['skill_id'] ?? $data['skill'] ?? '');
		$availability = trim($data['availability'] ?? '');

		if ($skillId === '') {
			throw new InvalidArgumentException('Offer skill_id is required');
		}

		if ($availability === '') {
			throw new InvalidArgumentException('Offer availability is required');
		}

		$payload = [
			'skill_id' => $skillId,
			'availability' => $availability,
			'title' => $data['title'] ?? null,
			'description' => $data['description'] ?? null,
			'user_id' => isset($data['user_id']) ? trim($data['user_id']) : null,
		];

		foreach ($data as $key => $value) {
			if (!array_key_exists($key, $payload)) {
				$payload[$key] = $value;
			}
		}

		$id = $this->create($payload);
		$offer = $this->findById($id);
		return $offer ?: [];
	}

	/**
	 * Get all offers with pagination.
	 * 
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getAllOffers(int $limit = 50, int $offset = 0): array
	{
		return $this->findAll($limit, $offset);
	}

	/**
	 * Get offers for a specific user.
	 * 
	 * @param string $userId
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function getOffersByUser(string $userId, ?int $limit = null, ?int $offset = null): array
	{
		return $this->findBy(['user_id' => $userId], $limit, $offset);
	}

	/**
	 * Filter offers by skill_id (UUID) or skill name and availability.
	 * 
	 * @param string|null $skillIdOrName
	 * @param string|null $availability
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function filterOffers(?string $skillIdOrName = null, ?string $availability = null, int $limit = 50, int $offset = 0): array
	{
		$where = [];
		$params = [];

		$sql = 'SELECT o.* FROM ' . $this->quoteIdentifier($this->table) . ' o';

		if ($skillIdOrName !== null && $skillIdOrName !== '') {
			if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $skillIdOrName)) {
				$where[] = $this->quoteIdentifier('o') . '.' . $this->quoteIdentifier('skill_id') . ' = :skill';
				$params[':skill'] = $skillIdOrName;
			} else {
				$sql .= ' JOIN ' . $this->quoteIdentifier('Skills') . ' s ON o.' . $this->quoteIdentifier('skill_id') . ' = s.' . $this->quoteIdentifier('id');
				$where[] = 's.' . $this->quoteIdentifier('name') . ' LIKE :skill';
				$params[':skill'] = '%' . trim($skillIdOrName) . '%';
			}
		}

		if ($availability !== null) {
			$where[] = $this->quoteIdentifier('o') . '.' . $this->quoteIdentifier('availability') . ' = :availability';
			$params[':availability'] = trim($availability);
		}

		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		$sql .= ' ORDER BY ' . $this->quoteIdentifier('o') . '.' . $this->quoteIdentifier($this->primaryKey) . ' DESC';
		$sql .= ' LIMIT :limit OFFSET :offset';

		$stmt = $this->db->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, PDO::PARAM_STR);
		}
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}
}
