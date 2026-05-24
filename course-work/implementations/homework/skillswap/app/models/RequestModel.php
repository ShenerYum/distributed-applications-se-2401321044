<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Model.php';

/**
 * RequestModel is responsible for handling request-related operations such as creating new requests, filtering requests by skill and desired_level, and retrieving requests for specific users.
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
		$desiredLevel = trim($data['desired_level'] ?? '');

		if ($skillId === '') {
			throw new InvalidArgumentException('Request skill_id is required');
		}

		if ($desiredLevel === '') {
			throw new InvalidArgumentException('Request desired_level is required');
		}

		$payload = [
			'skill_id' => $skillId,
			'desired_level' => $desiredLevel,
			'title' => $data['title'] ?? null,
			'notes' => $data['notes'] ?? null,
			'max_hours' => isset($data['max_hours']) ? (int)$data['max_hours'] : null,
			'user_id' => isset($_SESSION['user_id']) ? trim($_SESSION['user_id']) : null,
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
	 * Filter requests by skill_id (UUID) or skill name and desired_level.
	 * 
	 * @param string|null $skillIdOrName
	 * @param string|null $desiredLevel
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function filterRequests(?string $skillIdOrName = null, ?string $desiredLevel = null, int $limit = 50, int $offset = 0): array
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

		if ($desiredLevel !== null) {
			$where[] = $this->quoteIdentifier('r') . '.' . $this->quoteIdentifier('desired_level') . ' = :desired_level';
			$params[':desired_level'] = trim($desiredLevel);
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
	public function getRequestsByUser(string $userId, ?int $limit = 50, ?int $offset = 0): array
	{
		// Return requests belonging to a user, joined with skill meta (name, category)
		$sql = 'SELECT r.*,'
			. ' s.name AS skill_name, s.category AS skill_category'
			. ' FROM ' . $this->quoteIdentifier($this->table) . ' r'
			. ' LEFT JOIN ' . $this->quoteIdentifier('Skills') . ' s ON r.' . $this->quoteIdentifier('skill_id') . ' = s.' . $this->quoteIdentifier('id')
			. ' WHERE r.' . $this->quoteIdentifier('user_id') . ' = :user'
			. ' ORDER BY r.' . $this->quoteIdentifier($this->primaryKey) . ' DESC'
			. ' LIMIT :limit OFFSET :offset';

		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':user', $userId, PDO::PARAM_STR);
		$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/**
	 * Get requests matching any of the provided skill ids.
	 *
	 * @param array $skillIds
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getRequestsBySkillIds(array $skillIds, int $limit = 50, int $offset = 0): array
	{
		if (empty($skillIds)) return [];

		$placeholders = [];
		$params = [];
		foreach ($skillIds as $i => $sid) {
			$ph = ':s' . $i;
			$placeholders[] = $ph;
			$params[$ph] = $sid;
		}

		$sql = 'SELECT r.*, s.name AS skill_name, s.category AS skill_category'
			. ' FROM ' . $this->quoteIdentifier($this->table) . ' r'
			. ' LEFT JOIN ' . $this->quoteIdentifier('Skills') . ' s ON r.' . $this->quoteIdentifier('skill_id') . ' = s.' . $this->quoteIdentifier('id')
			. ' WHERE r.' . $this->quoteIdentifier('skill_id') . ' IN (' . implode(',', $placeholders) . ')';

		// Exclude requests created by the current user (if available in session)
		$me = isset($_SESSION['user_id']) ? trim($_SESSION['user_id']) : null;
		if (!empty($me)) {
			$sql = rtrim($sql, ';') . ' AND r.' . $this->quoteIdentifier('user_id') . ' != :me';
			$params[':me'] = $me;
		}

		$sql .= ' ORDER BY r.' . $this->quoteIdentifier($this->primaryKey) . ' DESC';
		$sql .= ' LIMIT :limit OFFSET :offset';

		$stmt = $this->db->prepare($sql);
		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v, PDO::PARAM_STR);
		}
		$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}
}
