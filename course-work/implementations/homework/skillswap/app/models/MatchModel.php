<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Model.php';

/**
 * MatchModel is responsible for handling match-related operations such as creating matches, retrieving matches for users, and updating match status.
 */
class MatchModel extends Model
{
	public function __construct()
	{
		parent::__construct();
		$this->setTable('Matches');
		$this->setPrimaryKey('id');
	}

	/**
	 * Create a new match.
	 * 
	 * @param array $data
	 * @return array Created match record
	 * 
	 * @throws InvalidArgumentException if required fields are missing or invalid.
	 * @throws RuntimeException if database operations fail.
	 */
	public function createMatch(array $data): array
	{
		$payload = [
			'user_a_id' => $data['user_a_id'] ?? null,
			'user_b_id' => $data['user_b_id'] ?? null,
			'offer_a_id' => $data['offer_a_id'] ?? null,
			'request_a_id' => $data['request_a_id'] ?? null,
			'offer_b_id' => $data['offer_b_id'] ?? null,
			'request_b_id' => $data['request_b_id'] ?? null,
			'score' => $data['score'] ?? 0,
			'status' => $data['status'] ?? 'pending',
			'created_at' => date('Y-m-d H:i:s'),
		];

		$id = $this->create($payload);
		return $this->findById($id) ?: [];
	}

	/**
	 * Get match by id.
	 * 
	 * @param string $id
	 * @return array|null
	 */
	public function findById(string $id): ?array
	{
		return parent::findById($id);
	}

	/**
	 * Get matches for a specific user.
	 * 
	 * @param string $userId
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function findForUser(string $userId, ?int $limit = null, ?int $offset = null): array
	{
		$sql = 'SELECT * FROM ' . $this->quoteIdentifier($this->table) . ' WHERE ' . $this->quoteIdentifier('user_a_id') . ' = :id OR ' . $this->quoteIdentifier('user_b_id') . ' = :id ORDER BY ' . $this->quoteIdentifier('created_at') . ' DESC';
		if ($limit !== null) {
			$sql .= ' LIMIT :limit OFFSET :offset';
		}

		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':id', $userId, PDO::PARAM_STR);
		if ($limit !== null) {
			$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
			$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/**
	 * Accept a match by id.
	 * 
	 * @param string $matchId
	 * @param string $userId
	 * @return bool True if the match was successfully accepted, false otherwise.
	 */
	public function acceptMatch(string $matchId, string $userId): bool
	{
		$match = $this->findById($matchId);
		if (!$match) {
			return false;
		}

		if ($match['user_a_id'] !== $userId && $match['user_b_id'] !== $userId) {
			return false;
		}

		if ($match['status'] !== 'pending') {
			return false;
		}

		$sql = 'UPDATE ' . $this->quoteIdentifier($this->table) . ' SET ' . $this->quoteIdentifier('status') . ' = :st, ' . $this->quoteIdentifier('accepted_at') . ' = :ts WHERE ' . $this->quoteIdentifier($this->primaryKey) . ' = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':st' => 'accepted', ':ts' => date('Y-m-d H:i:s'), ':id' => $matchId]);
	}

	/**
	 * Complete a match by id.
	 * 
	 * @param string $matchId
	 * @param string $userId
	 * @return bool True if the match was successfully completed, false otherwise.
	 */
	public function completeMatch(string $matchId, string $userId): bool
	{
		$match = $this->findById($matchId);
		if (!$match) {
			return false;
		}

		if ($match['user_a_id'] !== $userId && $match['user_b_id'] !== $userId) {
			return false;
		}

		if ($match['status'] !== 'accepted') {
			return false;
		}

		$sql = 'UPDATE ' . $this->quoteIdentifier($this->table) . ' SET ' . $this->quoteIdentifier('status') . ' = :st, ' . $this->quoteIdentifier('completed_at') . ' = :ts WHERE ' . $this->quoteIdentifier($this->primaryKey) . ' = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':st' => 'completed', ':ts' => date('Y-m-d H:i:s'), ':id' => $matchId]);
	}
}
