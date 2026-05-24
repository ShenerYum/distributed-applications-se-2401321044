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

	/**
	 * The RatingService instance for recalculating user ratings after review changes.
	 * @var RatingService
	 */
	private $ratingService;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$this->reviewModel = $this->loadModel('ReviewModel');
		require_once __ROOT__ . '/app/services/RatingService.php';
		$this->ratingService = new RatingService();
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
			$this->json(['error' => 'Valid user UUID is required'], 422);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		$reviews = $this->reviewModel->getReviewsByUser($userId, $limit, $offset);
		$this->json(['success' => true, 'data' => $reviews]);
	}

	/**
	 * Render profile reviews page for current user (HTML)
	 */
	public function profileReviews()
	{
		$user = $this->requireAuth();
		$reviews = $this->reviewModel->getReviewsByUser($user['id'], 100, 0);
		$this->render('profile/reviews', ['reviews' => $reviews]);
	}

	/**
	 * Admin index to list all reviews with optional sorting.
	 */
	public function index()
	{
		$this->requireAdmin();

		$sort = $_GET['sort'] ?? 'created_at';
		$reviews = $this->reviewModel->getAllReviews(200, 0, $sort);
		$this->render('reviews/index', ['reviews' => $reviews, 'sort' => $sort]);
	}

	/**
	 * Render create review form (GET) or forward POST to add().
	 */
	public function create()
	{
		$this->requireAuth();

		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$matchId = trim($_GET['match_id'] ?? '');
			$this->render('reviews/create', ['match_id' => $matchId]);
			return;
		}

		$this->add();
		return;
	}

	/**
	 * Render edit form for a review and handle POST to update.
	 * @param string $id
	 */
	public function edit(string $id)
	{
		$user = $this->requireAuth();
		$review = $this->reviewModel->findById($id);
		if (!$review) {
			$this->json(['error' => 'Review not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']);
		if (!$isAdmin && $review['reviewer_id'] !== $user['id']) {
			$this->json(['error' => 'Not authorized to edit this review'], 403);
		}

		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$this->render('reviews/edit', ['review' => $review]);
			return;
		}

		$_POST['id'] = $id;
		return $this->update();
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
			$this->json(['error' => 'Valid match UUID is required'], 422);
		}

		$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
		if ($rating < 1 || $rating > 5) {
			$this->json(['error' => 'Rating must be 1..5'], 422);
		}

		$feedback = trim($_POST['feedback'] ?? '');

		try {
			$review = $this->reviewModel->addReview([
				'reviewer_id' => $user['id'],
				'match_id' => $matchId,
				'rating' => $rating,
				'feedback' => $feedback,
			]);


			$info = $this->reviewModel->getReviewAndReviewee($review['id'] ?? ($reviewId ?? ''));
			if (!empty($info['reviewee_id'])) {
				$this->ratingService->recalcUserRating($info['reviewee_id']);
			}

			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
				$_SESSION['flash'][] = ['message' => 'Review created'];
				$this->redirect('profile/matches');
			}

			$this->json(['success' => true, 'data' => $review], 201);
		} catch (Exception $e) {
			$this->json(['error' => $e->getMessage()], 400);
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
			$this->json(['error' => 'Valid review id required'], 422);
		}

		$info = $this->reviewModel->getReviewAndReviewee($id);
		if (!$info || empty($info['review'])) {
			$this->json(['error' => 'Review not found'], 404);
		}

		$review = $info['review'];
		$reviewee = $info['reviewee_id'];

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && $review['reviewer_id'] !== $user['id']) {
			$this->json(['error' => 'Not authorized to update this review'], 403);
		}

		$data = [];
		if (isset($_POST['rating'])) {
			$r = (int)$_POST['rating'];
			if ($r < 1 || $r > 5) {
				$this->json(['error' => 'Rating must be 1..5'], 422);
			}

			$data['rating'] = $r;
		}

		if (isset($_POST['feedback'])) {
			$data['feedback'] = trim($_POST['feedback']);
		}

		if (empty($data)) {
			$this->json(['error' => 'No update data provided'], 422);
		}

		$ok = $this->reviewModel->update($id, $data);
		if (!$ok) {
			$this->json(['error' => 'Failed to update review'], 500);
		}

		if ($reviewee) {
			$this->ratingService->recalcUserRating($reviewee);
		}

		$updated = $this->reviewModel->findById($id);
		if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
			$_SESSION['flash'][] = ['message' => 'Review updated'];
			$this->redirect('profile/matches');
		}

		$this->json(['success' => true, 'data' => $updated]);
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
			$this->json(['error' => 'Valid review id required'], 422);
		}

		$info = $this->reviewModel->getReviewAndReviewee($id);
		if (!$info || empty($info['review'])) {
			$this->json(['error' => 'Review not found'], 404);
		}

		$review = $info['review'];
		$reviewee = $info['reviewee_id'];

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && $review['reviewer_id'] !== $user['id']) {
			$this->json(['error' => 'Not authorized to delete this review'], 403);
		}

		$ok = $this->reviewModel->delete($id);
		if (!$ok) {
			$this->json(['error' => 'Failed to delete review'], 500);
		}

		if ($reviewee) {
			$this->ratingService->recalcUserRating($reviewee);
		}

		if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
			$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
			$_SESSION['flash'][] = ['message' => 'Review deleted'];
			if ($isAdmin) {
				$this->redirect('reviews');
			} else {
				$this->redirect('profile/matches');
			}
		}

		$this->json(['success' => true]);
	}
}
