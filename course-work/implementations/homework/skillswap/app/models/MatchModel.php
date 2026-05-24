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
	 * Get all matches (admin)
	 * @param int|null $limit
	 * @param int|null $offset
	 * @param string $orderBy
	 * @return array
	 */
	public function getAllMatches(?int $limit = 200, ?int $offset = 0, string $orderBy = 'created_at'): array
	{
		$orderCol = $this->quoteIdentifier('created_at');
		switch ($orderBy) {
			case 'score':
				$orderCol = $this->quoteIdentifier('score');
				break;
			case 'status':
				$orderCol = $this->quoteIdentifier('status');
				break;
		}

		$sql = 'SELECT * FROM ' . $this->quoteIdentifier($this->table) . ' ORDER BY ' . $orderCol . ' DESC';
		if ($limit !== null) {
			$sql .= ' LIMIT :limit OFFSET :offset';
		}
		$stmt = $this->db->prepare($sql);
		if ($limit !== null) {
			$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
			$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/**
	 * Delete a match and cascade-delete related reviews. Runs inside a transaction.
	 * @param string $matchId
	 * @return bool
	 */
	public function deleteMatch(string $matchId): bool
	{
		try {
			$this->db->beginTransaction();

			// delete reviews for the match
			$stmt = $this->db->prepare('DELETE FROM ' . $this->quoteIdentifier('Reviews') . ' WHERE match_id = :mid');
			$stmt->execute([':mid' => $matchId]);

			// delete the match itself
			$stmt2 = $this->db->prepare('DELETE FROM ' . $this->quoteIdentifier($this->table) . ' WHERE ' . $this->quoteIdentifier($this->primaryKey) . ' = :id');
			$stmt2->execute([':id' => $matchId]);

			$this->db->commit();
			return true;
		} catch (Exception $e) {
			$this->db->rollBack();
			return false;
		}
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
			'offer_id' => $data['offer_id'] ?? null,
			'request_id' => $data['request_id'] ?? null,
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
		// Use two distinct placeholders to avoid drivers that don't support reusing the same named parameter
		$sql = 'SELECT * FROM ' . $this->quoteIdentifier($this->table)
			. ' WHERE ' . $this->quoteIdentifier('user_a_id') . ' = :id1 OR '
			. $this->quoteIdentifier('user_b_id') . ' = :id2 ORDER BY '
			. $this->quoteIdentifier('created_at') . ' DESC';
		if ($limit !== null) {
			$sql .= ' LIMIT :limit OFFSET :offset';
		}

		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':id1', $userId, PDO::PARAM_STR);
		$stmt->bindValue(':id2', $userId, PDO::PARAM_STR);
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

		// Only the receiver (user_b_id) may accept a pending match
		if (($match['user_b_id'] ?? null) !== $userId) {
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

		if (($match['user_a_id'] ?? null) !== $userId && ($match['user_b_id'] ?? null) !== $userId) {
			return false;
		}

		if ($match['status'] !== 'accepted') {
			return false;
		}

		$sql = 'UPDATE ' . $this->quoteIdentifier($this->table) . ' SET ' . $this->quoteIdentifier('status') . ' = :st, ' . $this->quoteIdentifier('completed_at') . ' = :ts WHERE ' . $this->quoteIdentifier($this->primaryKey) . ' = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':st' => 'completed', ':ts' => date('Y-m-d H:i:s'), ':id' => $matchId]);
	}

	/**
	 * Soft-reject a match (mark as rejected) if the acting user is a participant and the match is pending.
	 *
	 * @param string $matchId
	 * @param string $userId
	 * @return bool
	 */
	public function rejectMatch(string $matchId, string $userId): bool
	{
		$match = $this->findById($matchId);
		if (!$match) return false;
		if (($match['user_a_id'] ?? null) !== $userId && ($match['user_b_id'] ?? null) !== $userId) return false;
		if ($match['status'] !== 'pending') return false;
		$sql = 'UPDATE ' . $this->quoteIdentifier($this->table) . ' SET ' . $this->quoteIdentifier('status') . ' = :st WHERE ' . $this->quoteIdentifier($this->primaryKey) . ' = :id';
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':st' => 'rejected', ':id' => $matchId]);
	}
}
