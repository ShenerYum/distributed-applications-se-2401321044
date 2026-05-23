<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Model.php';

/**
 * RequestModel is responsible for handling request-related operations such as creating new requests, filtering requests by skill and availability, and retrieving requests for specific users.
 */
class RequestModel extends Model
{
	public function __construct()
	{
		parent::__construct();
		$this->setTable('Requests');
		$this->setPrimaryKey('id');
	}

	/**
	 * Create a new request.
	 * 
	 * @param array $data
	 * @return array Created request record
	 * 
	 * @throws InvalidArgumentException if required fields are missing or invalid.
	 * @throws RuntimeException if database operations fail.
	 */
	public function createRequest(array $data): array
	{
		$skillId = trim($data['skill_id'] ?? $data['skill'] ?? '');
		$availability = trim($data['availability'] ?? '');

		if ($skillId === '') {
			throw new InvalidArgumentException('Request skill_id is required');
		}

		if ($availability === '') {
			throw new InvalidArgumentException('Request availability is required');
		}

		$payload = [
			'skill_id' => $skillId,
			'availability' => $availability,
			'title' => $data['title'] ?? null,
			'description' => $data['description'] ?? null,
			'user_id' => isset($data['user_id']) ? trim($data['user_id']) : null,
		];

		$id = $this->create($payload);
		$request = $this->findById($id);
		return $request ?: [];
	}

	/**
	 * Get all requests with pagination.
	 * 
	 * @param int $limit
	 * @param int $offset
	 * 
	 * @return array
	 */
	public function getAllRequests(int $limit = 50, int $offset = 0): array
	{
		return $this->findAll($limit, $offset);
	}

	/**
	 * Filter requests by skill_id (UUID) or skill name and availability.
	 * 
	 * @param string|null $skillIdOrName
	 * @param string|null $availability
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function filterRequests(?string $skillIdOrName = null, ?string $availability = null, int $limit = 50, int $offset = 0): array
	{
		$where = [];
		$params = [];

		$sql = 'SELECT r.* FROM ' . $this->quoteIdentifier($this->table) . ' r';

		if ($skillIdOrName !== null && $skillIdOrName !== '') {
			if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $skillIdOrName)) {
				$where[] = $this->quoteIdentifier('r') . '.' . $this->quoteIdentifier('skill_id') . ' = :skill';
				$params[':skill'] = $skillIdOrName;
			} else {
				$sql .= ' JOIN ' . $this->quoteIdentifier('Skills') . ' s ON r.' . $this->quoteIdentifier('skill_id') . ' = s.' . $this->quoteIdentifier('id');
				$where[] = 's.' . $this->quoteIdentifier('name') . ' LIKE :skill';
				$params[':skill'] = '%' . trim($skillIdOrName) . '%';
			}
		}

		if ($availability !== null) {
			$where[] = $this->quoteIdentifier('r') . '.' . $this->quoteIdentifier('availability') . ' = :availability';
			$params[':availability'] = trim($availability);
		}

		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		$sql .= ' ORDER BY ' . $this->quoteIdentifier('r') . '.' . $this->quoteIdentifier($this->primaryKey) . ' DESC';
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

	/**
	 * Get requests for a specific user.
	 * 
	 * @param string $userId
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function getRequestsByUser(string $userId, ?int $limit = null, ?int $offset = null): array
	{
		return $this->findBy(['user_id' => $userId], $limit, $offset);
	}
}
