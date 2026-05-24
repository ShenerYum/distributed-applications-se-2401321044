<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Model.php';

/**
 * ReviewModel is responsible for handling review-related operations such as adding reviews for completed matches and retrieving reviews for users.
 */
class ReviewModel extends Model
{
	public function __construct()
	{
		parent::__construct();
		$this->setTable('Reviews');
		$this->setPrimaryKey('id');
	}

	/**
	 * Add a review for a completed match and update the reviewee's aggregate rating.
	 * Expects keys: reviewer_id, match_id, rating (1-5), feedback (optional)
	 * 
	 * @param array $data
	 * 
	 * @return array Created review record
	 * 
	 * @throws InvalidArgumentException if required fields are missing or invalid.
	 * @throws RuntimeException if the match is not completed or the reviewer is not a participant.
	 * @throws InvalidArgumentException if the reviewer has already reviewed this match.
	 * @throws RuntimeException if database operations fail.
	 */
	public function addReview(array $data): array
	{
		$reviewer = trim($data['reviewer_id'] ?? '');
		$matchId = trim($data['match_id'] ?? '');
		$rating = isset($data['rating']) ? (int)$data['rating'] : 0;

		if ($reviewer === '' || $matchId === '') {
			throw new InvalidArgumentException('Reviewer and match IDs are required');
		}

		if ($rating < 1 || $rating > 5) {
			throw new InvalidArgumentException('Rating must be an integer between 1 and 5');
		}

		$stmt = $this->db->prepare('SELECT * FROM ' . $this->quoteIdentifier('Matches') . ' WHERE id = :mid');
		$stmt->execute([':mid' => $matchId]);
		$match = $stmt->fetch();

		if (!$match) {
			throw new InvalidArgumentException('Match not found');
		}

		if (strtolower($match['status']) !== 'completed') {
			throw new InvalidArgumentException('Reviews can only be created for completed matches');
		}

		if ($match['user_a_id'] === $reviewer) {
			$reviewee = $match['user_b_id'];
		} elseif ($match['user_b_id'] === $reviewer) {
			$reviewee = $match['user_a_id'];
		} else {
			throw new InvalidArgumentException('Reviewer is not a participant of the match');
		}

		$stmtCheck = $this->db->prepare('SELECT COUNT(*) AS cnt FROM ' . $this->quoteIdentifier($this->table) . ' WHERE match_id = :mid AND reviewer_id = :rid');
		$stmtCheck->execute([':mid' => $matchId, ':rid' => $reviewer]);
		$exists = (int)$stmtCheck->fetchColumn();
		if ($exists > 0) {
			throw new InvalidArgumentException('You have already reviewed this match');
		}

		$payload = [
			'match_id' => $matchId,
			'reviewer_id' => $reviewer,
			'rating' => $rating,
			'feedback' => $data['feedback'] ?? null,
			'created_at' => date('Y-m-d H:i:s'),
		];

		$id = $this->create($payload);
		$review = $this->findById($id);

		$sql = 'SELECT AVG(r.rating) AS avg_rating FROM ' . $this->quoteIdentifier($this->table) . ' r JOIN ' . $this->quoteIdentifier('Matches') . ' m ON r.match_id = m.id WHERE ((m.user_a_id = :uid1 AND r.reviewer_id = m.user_b_id) OR (m.user_b_id = :uid2 AND r.reviewer_id = m.user_a_id))';
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid1' => $reviewee, ':uid2' => $reviewee]);
		$row = $stmt->fetch();
		$avg = $row && $row['avg_rating'] !== null ? (float)$row['avg_rating'] : 0.0;

		$userTable = $this->quoteIdentifier('Users');
		$ratingCol = $this->quoteIdentifier('rating');
		$idCol = $this->quoteIdentifier('id');

		$sql2 = sprintf('UPDATE %s SET %s = :avg WHERE %s = :id', $userTable, $ratingCol, $idCol);
		$stmt2 = $this->db->prepare($sql2);
		$stmt2->execute([':avg' => $avg, ':id' => $reviewee]);

		return $review ?: [];
	}

	/**
	 * Get reviews for a given user (reviewee).
	 * 
	 * @param string $userId
	 * @param int $limit
	 * @param int $offset
	 * 
	 * @return array
	 */
	public function getReviewsByUser(string $userId, int $limit = 50, int $offset = 0): array
	{
		// Join Matches to derive the reviewee as the other participant in the match
		$sql = 'SELECT r.* FROM ' . $this->quoteIdentifier($this->table) . ' r JOIN ' . $this->quoteIdentifier('Matches') . ' m ON r.match_id = m.id WHERE ((m.user_a_id = :uid1 AND r.reviewer_id = m.user_b_id) OR (m.user_b_id = :uid2 AND r.reviewer_id = m.user_a_id)) ORDER BY r.' . $this->quoteIdentifier('created_at') . ' DESC';

		if ($limit !== null) {
			$sql .= ' LIMIT :limit OFFSET :offset';
		}

		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':uid1', $userId, PDO::PARAM_STR);
		$stmt->bindValue(':uid2', $userId, PDO::PARAM_STR);
		if ($limit !== null) {
			$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
			$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
		}
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/**
	 * Get all reviews (admin) with optional ordering.
	 * @param int|null $limit
	 * @param int|null $offset
	 * @param string $orderBy
	 * @return array
	 */
	public function getAllReviews(?int $limit = 200, ?int $offset = 0, string $orderBy = 'created_at'): array
	{
		$validOrders = ['created_at', 'rating', 'match', 'reviewer'];
		$orderBySql = 'r.' . $this->quoteIdentifier('created_at');
		switch ($orderBy) {
			case 'rating':
				$orderBySql = 'r.' . $this->quoteIdentifier('rating');
				break;
			case 'match':
				$orderBySql = 'm.' . $this->quoteIdentifier('id');
				break;
			case 'reviewer':
				$orderBySql = 'r.' . $this->quoteIdentifier('reviewer_id');
				break;
		}

		$sql = 'SELECT r.*, m.user_a_id, m.user_b_id FROM ' . $this->quoteIdentifier($this->table) . ' r JOIN ' . $this->quoteIdentifier('Matches') . ' m ON r.match_id = m.id ORDER BY ' . $orderBySql . ' DESC';

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
	 * Recalculate and store aggregate rating for a user based on match-based reviews.
	 * 
	 * @param string $userId
	 * @return float New average rating
	 */
	public function recalcUserRating(string $userId): float
	{
		$sql = 'SELECT AVG(r.rating) AS avg_rating FROM ' . $this->quoteIdentifier($this->table) . ' r JOIN ' . $this->quoteIdentifier('Matches') . ' m ON r.match_id = m.id WHERE ((m.user_a_id = :uid1 AND r.reviewer_id = m.user_b_id) OR (m.user_b_id = :uid2 AND r.reviewer_id = m.user_a_id))';
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':uid1' => $userId, ':uid2' => $userId]);
		$row = $stmt->fetch();
		$avg = $row && $row['avg_rating'] !== null ? (float)$row['avg_rating'] : 0.0;

		$userTable = $this->quoteIdentifier('Users');
		$ratingCol = $this->quoteIdentifier('rating');
		$idCol = $this->quoteIdentifier('id');
		$sql2 = sprintf('UPDATE %s SET %s = :avg WHERE %s = :id', $userTable, $ratingCol, $idCol);
		$stmt2 = $this->db->prepare($sql2);
		$stmt2->execute([':avg' => $avg, ':id' => $userId]);

		return $avg;
	}

	/**
	 * Get a review row and determine the reviewee (the other match participant).
	 * 
	 * @param string $reviewId
	 * @return array|null ['review' => array, 'reviewee_id' => string]
	 */
	public function getReviewAndReviewee(string $reviewId): ?array
	{
		$review = $this->findById($reviewId);
		if (!$review) return null;

		$stmt = $this->db->prepare('SELECT user_a_id, user_b_id FROM ' . $this->quoteIdentifier('Matches') . ' WHERE id = :mid');
		$stmt->execute([':mid' => $review['match_id']]);
		$match = $stmt->fetch();
		if (!$match) return ['review' => $review, 'reviewee_id' => null];

		$reviewer = $review['reviewer_id'];
		if ($match['user_a_id'] === $reviewer) {
			$reviewee = $match['user_b_id'];
		} elseif ($match['user_b_id'] === $reviewer) {
			$reviewee = $match['user_a_id'];
		} else {
			$reviewee = null;
		}

		return ['review' => $review, 'reviewee_id' => $reviewee];
	}

	/**
	 * Find a review by match and reviewer.
	 * @param string $matchId
	 * @param string $reviewerId
	 * @return array|null
	 */
	public function findByMatchAndReviewer(string $matchId, string $reviewerId): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM ' . $this->quoteIdentifier($this->table) . ' WHERE match_id = :mid AND reviewer_id = :rid LIMIT 1');
		$stmt->execute([':mid' => $matchId, ':rid' => $reviewerId]);
		$row = $stmt->fetch();
		return $row ?: null;
	}
}
