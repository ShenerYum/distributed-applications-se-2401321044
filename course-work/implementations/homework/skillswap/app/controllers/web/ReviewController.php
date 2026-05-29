<?php

namespace App\Controllers\Web;

use App\Core\WebController;
use App\Core\Response;

use App\Services\{
	AuthService,
	ReviewService,
	MatchService
};

/**
 * ReviewController is responsible for handling review-related operations such as listing,
 * creating, editing, and deleting reviews on completed matches. Authentication is required
 * for all operations. Only reviewers or admins can edit/delete their own reviews.
 */
class ReviewController extends WebController
{
	public function __construct(
		private AuthService $authService,
		private ReviewService $reviewService,
		private MatchService $matchService
	) {
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	}

	/**
	 * List all reviews received by a specific user.
	 * Accessible to anyone. Supports pagination and filtering by reviewer name and rating.
	 *
	 * @param string $userId User ID to get reviews for
	 * @return Response
	 */
	public function userReviews(string $userId): Response
	{
		try {
			$this->authService->validateUUID($userId);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
		$filters = [
			'reviewer_name' => !empty($_GET['reviewer_name']) ? trim($_GET['reviewer_name']) : null,
			'rating' => !empty($_GET['rating']) ? (int)$_GET['rating'] : null,
		];

		try {
			$reviews = $this->reviewService->getReviewsForUser($userId, $filters, $limit, $offset);
			$reviews = $this->reviewService->enrichReviews($reviews);

			return $this->render('reviews/user_reviews', [
				'reviews' => $reviews,
				'userId' => $userId,
				'hasFilters' => !empty(array_filter($filters)),
				'empty' => empty($reviews)
			]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	/**
	 * List all reviews received by the logged-in user (profile page).
	 * Requires authentication. Supports pagination and filtering.
	 *
	 * @return Response
	 */
	public function myReviews(): Response
	{
		try {
			$this->authService->requireAuth();
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$user = $this->authService->getCurrentUser();

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
		$filters = [
			'reviewer_name' => !empty($_GET['reviewer_name']) ? trim($_GET['reviewer_name']) : null,
			'rating' => !empty($_GET['rating']) ? (int)$_GET['rating'] : null,
		];

		try {
			$reviews = $this->reviewService->getMyReviews($filters, $limit, $offset);
			$reviews = $this->reviewService->enrichReviews($reviews);

			return $this->render('profile/reviews', [
				'reviews' => $reviews,
				'hasFilters' => !empty(array_filter($filters)),
				'empty' => empty($reviews)
			]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	/**
	 * Admin page to list all reviews in the system.
	 * Admin only. Supports sorting by created_at, rating, match, or reviewer.
	 * Requires authentication and admin privileges.
	 *
	 * @return Response
	 */
	public function index(): Response
	{
		try {
			$this->authService->requireAdmin();
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$sortBy = !empty($_GET['sort']) ? trim($_GET['sort']) : 'created_at';
		$validSortFields = ['created_at', 'rating', 'match', 'reviewer'];
		if (!in_array($sortBy, $validSortFields)) {
			$sortBy = 'created_at';
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		try {
			$reviews = $this->reviewService->listAllReviews($sortBy, $limit, $offset);
			$reviews = $this->reviewService->enrichReviews($reviews);

			return $this->render('reviews/index', [
				'reviews' => $reviews,
				'sortBy' => $sortBy,
				'empty' => empty($reviews)
			]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	/**
	 * Render the form to create a new review.
	 * Requires authentication. Only shows if:
	 * - The logged-in user has a completed match with the target user
	 * - The logged-in user hasn't already reviewed this user in this match
	 *
	 * @param string $matchId Match ID to create review for
	 * @return Response
	 */
	public function createPage(string $matchId): Response
	{
		try {
			$this->authService->requireAuth();
			$this->authService->validateUUID($matchId);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		try {
			$match = $this->matchService->getMatch($matchId);

			// Validate match is completed
			if (($match['status'] ?? null) !== 'completed') {
				throw new \InvalidArgumentException('This match has not been completed yet', 400);
			}

			$currentUser = $this->authService->getCurrentUser();

			// Validate current user is part of match
			if ($currentUser['id'] !== $match['user_a_id'] && $currentUser['id'] !== $match['user_b_id']) {
				throw new \InvalidArgumentException('You are not part of this match', 403);
			}

			// Determine target user
			$targetUserId = $currentUser['id'] === $match['user_a_id'] ? $match['user_b_id'] : $match['user_a_id'];

			// Check if already reviewed
			if (!$this->reviewService->canReview($matchId, $targetUserId)) {
				throw new \InvalidArgumentException('You have already reviewed this user for this match', 400);
			}

			return $this->render('reviews/create', [
				'match_id' => $matchId,
				'target_user_id' => $targetUserId
			]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	/**
	 * Handle the form submission to create a new review.
	 * Requires authentication and valid completed match.
	 * Delegates actual creation to ReviewService.
	 *
	 * @return Response
	 */
	public function create(): Response
	{
		try {
			$this->authService->requireAuth();
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$input = $_POST;
		try {
			$this->reviewService->createReview([
				'match_id' => trim($input['match_id'] ?? ''),
				'rating' => (int)($input['rating'] ?? 0),
				'feedback' => trim($input['feedback'] ?? '')
			]);

			return $this->redirect('profile/reviews');
		} catch (\Exception $e) {
			return $this->retry('reviews/create?match=' . urlencode($input['match_id'] ?? ''), $e);
		}
	}

	/**
	 * Render the form to edit an existing review.
	 * Only the reviewer or an admin can edit a review.
	 *
	 * @param string $id Review ID to edit
	 * @return Response
	 */
	public function editPage(string $id): Response
	{
		try {
			$this->authService->requireAuth();
			$this->authService->validateUUID($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		try {
			$review = $this->reviewService->getReview($id);

			$currentUser = $this->authService->getCurrentUser();
			$isReviewer = $currentUser['id'] === $review['author_id'];
			$isAdmin = (bool)($currentUser['is_admin'] ?? false);

			if (!$isReviewer && !$isAdmin) {
				throw new \InvalidArgumentException('You cannot edit this review', 403);
			}

			return $this->render('reviews/edit', ['review' => $review]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	/**
	 * Handle the form submission to edit an existing review.
	 * Only the reviewer or an admin can edit a review.
	 * Delegates actual update to ReviewService.
	 *
	 * @param string $id Review ID to edit
	 * @return Response
	 */
	public function edit(string $id): Response
	{
		try {
			$this->authService->requireAuth();
			$this->authService->validateUUID($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		try {
			$review = $this->reviewService->getReview($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$input = $_POST;
		try {
			$this->reviewService->updateReview($id, [
				'rating' => !empty($input['rating']) ? (int)$input['rating'] : null,
				'feedback' => !empty($input['feedback']) ? trim($input['feedback']) : null
			]);

			return $this->redirect('profile/reviews');
		} catch (\Exception $e) {
			return $this->retry('reviews/' . urlencode($id) . '/edit', $e, ['review' => $review]);
		}
	}

	/**
	 * Handle the deletion of a review.
	 * Only the reviewer or an admin can delete a review.
	 * This should be a POST request to prevent CSRF attacks.
	 *
	 * @param string $id Review ID to delete
	 * @return Response
	 */
	public function delete(string $id): Response
	{
		try {
			$this->authService->requireAuth();
			$this->authService->validateUUID($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		try {
			$this->reviewService->deleteReview($id);

			return $this->redirect('profile/reviews');
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}
}
