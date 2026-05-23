<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * ReviewController is responsible for handling review-related operations such as listing
 * reviews for a user, adding a new review for a match, updating an existing review, and 
 * deleting a review. Authentication is required for adding, updating, and deleting reviews.
 * Admin privileges or being the reviewer is required for updating and deleting reviews.
 */
class ReviewController extends Controller
{
	/**
	 * The ReviewModel instance for handling review-related database operations.
	 * @var ReviewModel
	 */
	private $reviewModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$this->reviewModel = $this->loadModel('ReviewModel');
	}

	/**
	 * GET list reviews for a user. Requires user_id, optional limit and offset.
	 * 
	 * @return array JSON response containing the list of reviews or an error message.
	 */
	public function listByUser()
	{
		$userId = trim($_GET['user_id'] ?? '');
		if (!$this->isValidUUID($userId)) {
			return $this->json(['error' => 'Valid user UUID is required'], 422);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		$reviews = $this->reviewModel->getReviewsByUser($userId, $limit, $offset);
		return $this->json(['success' => true, 'data' => $reviews]);
	}

	/**
	 * POST create a new review for a match. Requires reviewer_id, match_id, rating (1..5), and optional feedback.
	 * 
	 * @return array JSON response containing the created review or an error message.
	 */
	public function add()
	{
		$user = $this->requireAuth();
		$matchId = trim($_POST['match_id'] ?? '');
		if (!$this->isValidUUID($matchId)) {
			return $this->json(['error' => 'Valid match UUID is required'], 422);
		}

		$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
		if ($rating < 1 || $rating > 5) {
			return $this->json(['error' => 'Rating must be 1..5'], 422);
		}

		$feedback = trim($_POST['feedback'] ?? '');

		try {
			$review = $this->reviewModel->addReview([
				'reviewer_id' => $user['id'],
				'match_id' => $matchId,
				'rating' => $rating,
				'feedback' => $feedback,
			]);

			return $this->json(['success' => true, 'data' => $review], 201);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * PUT update an existing review. Requires review id and at least one of rating or feedback.
	 * 
	 * @return array JSON response containing the updated review or an error message.
	 */
	public function update()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid review id required'], 422);
		}

		$info = $this->reviewModel->getReviewAndReviewee($id);
		if (!$info || empty($info['review'])) {
			return $this->json(['error' => 'Review not found'], 404);
		}

		$review = $info['review'];
		$reviewee = $info['reviewee_id'];

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && $review['reviewer_id'] !== $user['id']) {
			return $this->json(['error' => 'Not authorized to update this review'], 403);
		}

		$data = [];
		if (isset($_POST['rating'])) {
			$r = (int)$_POST['rating'];
			if ($r < 1 || $r > 5) return $this->json(['error' => 'Rating must be 1..5'], 422);
			$data['rating'] = $r;
		}
		if (isset($_POST['feedback'])) {
			$data['feedback'] = trim($_POST['feedback']);
		}

		if (empty($data)) {
			return $this->json(['error' => 'No update data provided'], 422);
		}

		$ok = $this->reviewModel->update($id, $data);
		if (!$ok) return $this->json(['error' => 'Failed to update review'], 500);

		if ($reviewee) {
			$this->reviewModel->recalcUserRating($reviewee);
		}

		$updated = $this->reviewModel->findById($id);
		return $this->json(['success' => true, 'data' => $updated]);
	}

	/**
	 * DELETE delete a review by id. Admin or reviewer only.
	 * 
	 * @return array JSON response indicating success or an error message.
	 */
	public function delete()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid review id required'], 422);
		}

		$info = $this->reviewModel->getReviewAndReviewee($id);
		if (!$info || empty($info['review'])) {
			return $this->json(['error' => 'Review not found'], 404);
		}

		$review = $info['review'];
		$reviewee = $info['reviewee_id'];

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && $review['reviewer_id'] !== $user['id']) {
			return $this->json(['error' => 'Not authorized to delete this review'], 403);
		}

		$ok = $this->reviewModel->delete($id);
		if (!$ok) return $this->json(['error' => 'Failed to delete review'], 500);

		if ($reviewee) {
			$this->reviewModel->recalcUserRating($reviewee);
		}

		return $this->json(['success' => true]);
	}
}
