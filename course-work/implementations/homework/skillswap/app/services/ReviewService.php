<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Models\ReviewModel;
use App\Models\MatchModel;

/**
 * ReviewService is responsible for managing reviews on completed matches.
 * Reviews are tied to matches and allow users to rate and provide feedback to their match partners.
 */
class ReviewService extends Service
{
	public function __construct(
		private ReviewModel $reviews,
		private MatchModel $matches,
		private AuthService $authService,
		private UserService $userService
	) {}

	/**
	 * Get a single review by ID.
	 * @param string $id Review ID
	 * @return array The review data
	 * @throws \InvalidArgumentException if review not found
	 */
	public function getReview(string $id): array
	{
		$this->validateUUID($id);

		$review = $this->reviews->findById($id);
		if (!$review) {
			throw new \InvalidArgumentException('Review not found', 404);
		}

		return $review;
	}

	/**
	 * Create a new review for a match.
	 * Validates that:
	 * - The match exists and is completed
	 * - The current user is one of the match participants
	 * - The current user hasn't already reviewed the other user in this match
	 *
	 * @param array $data Review data with match_id, rating, and optional feedback (text)
	 * @return array The created review
	 * @throws \InvalidArgumentException if validation fails
	 * @throws \RuntimeException if creation fails
	 */
	public function createReview(array $data): array
	{
		$this->validateRequiredFields($data, ['match_id', 'rating']);

		$this->validateUUID((string)$data['match_id']);

		// Validate rating
		$rating = (int)$data['rating'];
		if ($rating < 1 || $rating > 5) {
			throw new \InvalidArgumentException('Rating must be between 1 and 5', 400);
		}

		// Get current user
		$currentUser = $this->authService->requireAuth();

		// Get the match
		$match = $this->matches->findById((string)$data['match_id']);
		if (!$match) {
			throw new \InvalidArgumentException('Match not found', 404);
		}

		// Validate match is completed
		if (($match['status'] ?? null) !== 'completed') {
			throw new \InvalidArgumentException('Cannot review an incomplete match', 400);
		}

		// Validate current user is part of the match
		$isUserA = $currentUser['id'] === $match['user_a_id'];
		$isUserB = $currentUser['id'] === $match['user_b_id'];

		if (!$isUserA && !$isUserB) {
			throw new \InvalidArgumentException('You are not part of this match', 403);
		}

		// Determine target user (the other person in the match)
		$targetUserId = $isUserA ? $match['user_b_id'] : $match['user_a_id'];

		// Check if review already exists
		$existingReview = $this->reviews->findBy([
			'match_id' => $match['id'],
			'author_id' => $currentUser['id'],
			'target_user_id' => $targetUserId
		]);

		if (!empty($existingReview)) {
			throw new \InvalidArgumentException('You have already reviewed this user for this match', 400);
		}

		// Create the review
		$reviewData = [
			'match_id' => $match['id'],
			'author_id' => $currentUser['id'],
			'target_user_id' => $targetUserId,
			'rating' => $rating,
			'text' => $data['feedback'] ?? null,
		];

		$review = $this->reviews->createReview($reviewData);
		if (!$review) {
			throw new \RuntimeException('Failed to create review', 500);
		}

		// Recalculate user rating
		$this->recalculateUserRating($targetUserId);

		return $review;
	}

	/**
	 * Update an existing review.
	 * Only the reviewer or an admin can update a review.
	 *
	 * @param string $id Review ID
	 * @param array $data Review data with rating and/or feedback (text)
	 * @return array The updated review
	 * @throws \InvalidArgumentException if validation fails
	 * @throws \RuntimeException if update fails
	 */
	public function updateReview(string $id, array $data): array
	{
		$this->validateUUID($id);

		$review = $this->reviews->findById($id);
		if (!$review) {
			throw new \InvalidArgumentException('Review not found', 404);
		}

		$currentUser = $this->authService->requireAuth();

		// Check authorization: only reviewer or admin can update
		$isReviewer = $currentUser['id'] === $review['author_id'];
		$isAdmin = (bool)($currentUser['is_admin'] ?? false);

		if (!$isReviewer && !$isAdmin) {
			throw new \InvalidArgumentException('Unauthorized: You cannot update this review', 403);
		}

		// Validate rating if provided
		if (isset($data['rating'])) {
			$rating = (int)$data['rating'];
			if ($rating < 1 || $rating > 5) {
				throw new \InvalidArgumentException('Rating must be between 1 and 5', 400);
			}
		}

		// Prepare update data
		$updateData = [];
		if (isset($data['rating'])) {
			$updateData['rating'] = $data['rating'];
		}
		if (isset($data['feedback'])) {
			$updateData['text'] = $data['feedback'];
		}

		if (empty($updateData)) {
			throw new \InvalidArgumentException('No fields to update', 400);
		}

		$updated = $this->reviews->updateReview($id, $updateData);
		if (!$updated) {
			throw new \RuntimeException('Failed to update review', 500);
		}

		// Recalculate user rating if rating changed
		if (isset($updateData['rating'])) {
			$this->recalculateUserRating((string)$updated['target_user_id']);
		}

		return $updated;
	}

	/**
	 * Delete a review.
	 * Only the reviewer or an admin can delete a review.
	 *
	 * @param string $id Review ID
	 * @throws \InvalidArgumentException if authorization fails
	 * @throws \RuntimeException if deletion fails
	 */
	public function deleteReview(string $id): void
	{
		$this->validateUUID($id);

		$review = $this->reviews->findById($id);
		if (!$review) {
			throw new \InvalidArgumentException('Review not found', 404);
		}

		$currentUser = $this->authService->requireAuth();

		// Check authorization: only reviewer or admin can delete
		$isReviewer = $currentUser['id'] === $review['author_id'];
		$isAdmin = (bool)($currentUser['is_admin'] ?? false);

		if (!$isReviewer && !$isAdmin) {
			throw new \InvalidArgumentException('Unauthorized: You cannot delete this review', 403);
		}

		$targetUserId = (string)$review['target_user_id'];

		if (!$this->reviews->deleteReview($id)) {
			throw new \RuntimeException('Failed to delete review', 500);
		}

		// Recalculate user rating after deletion
		$this->recalculateUserRating($targetUserId);
	}

	/**
	 * List reviews for a specific user (received by them).
	 * Includes pagination and filtering by reviewer name and rating.
	 *
	 * @param string $userId The user to get reviews for
	 * @param array $filters Optional filters: reviewer_name, rating
	 * @param int $limit
	 * @param int $offset
	 * @return array List of reviews
	 */
	public function getReviewsForUser(string $userId, array $filters = [], int $limit = 20, int $offset = 0): array
	{
		$this->validateUUID($userId);

		$reviews = $this->reviews->findBy(['target_user_id' => $userId]);
		$reviews = is_array($reviews) ? $reviews : [];

		// Apply filters
		if (!empty($filters['reviewer_name'])) {
			$reviewerName = strtolower((string)$filters['reviewer_name']);
			$reviews = array_filter($reviews, function ($review) use ($reviewerName) {
				// Note: You may need to join with Users table to get reviewer name
				// For now, filtering by author_id match (would need to enhance this)
				return true; // Placeholder
			});
		}

		if (!empty($filters['rating'])) {
			$rating = (int)$filters['rating'];
			$reviews = array_filter($reviews, function ($review) use ($rating) {
				return (int)($review['rating'] ?? 0) === $rating;
			});
		}

		$reviews = array_values($reviews);
		return array_slice($reviews, $offset, $limit);
	}

	/**
	 * List all reviews for the current user (received by them).
	 * Used in the profile reviews page.
	 *
	 * @param array $filters Optional filters: reviewer_name, rating
	 * @param int $limit
	 * @param int $offset
	 * @return array List of reviews
	 */
	public function getMyReviews(array $filters = [], int $limit = 20, int $offset = 0): array
	{
		$currentUser = $this->authService->requireAuth();
		return $this->getReviewsForUser($currentUser['id'], $filters, $limit, $offset);
	}

	/**
	 * List all reviews in the system.
	 * Admin only. Supports sorting by created_at, rating, match_id, author_id.
	 *
	 * @param string $sortBy Field to sort by: created_at, rating, match, reviewer
	 * @param int $limit
	 * @param int $offset
	 * @return array List of all reviews
	 * @throws \RuntimeException if not admin
	 */
	public function listAllReviews(string $sortBy = 'created_at', int $limit = 20, int $offset = 0): array
	{
		$currentUser = $this->authService->requireAdmin();

		$reviews = $this->reviews->findAll($limit, $offset);
		$reviews = is_array($reviews) ? $reviews : [];

		// Sort results
		usort($reviews, function ($a, $b) use ($sortBy) {
			switch ($sortBy) {
				case 'rating':
					return ((int)($b['rating'] ?? 0)) - ((int)($a['rating'] ?? 0));
				case 'match':
					return strcmp((string)($a['match_id'] ?? ''), (string)($b['match_id'] ?? ''));
				case 'reviewer':
					return strcmp((string)($a['author_id'] ?? ''), (string)($b['author_id'] ?? ''));
				case 'created_at':
				default:
					return strtotime((string)($b['created_at'] ?? '0')) - strtotime((string)($a['created_at'] ?? '0'));
			}
		});

		return $reviews;
	}

	/**
	 * Get reviews enriched with related data for display in views.
	 * Adds reviewer name, target user name, match status, etc.
	 *
	 * @param array $reviews Array of review records
	 * @return array Reviews with enriched data
	 */
	public function enrichReviews(array $reviews): array
	{
		foreach ($reviews as &$review) {
			try {
				// Get reviewer info
				if (!empty($review['author_id'])) {
					$reviewer = $this->userService->getUser((string)$review['author_id']);
					$review['reviewer_name'] = $reviewer['name'] ?? null;
					$review['reviewer_email'] = $reviewer['email'] ?? null;
				}

				// Get target user info
				if (!empty($review['target_user_id'])) {
					$targetUser = $this->userService->getUser((string)$review['target_user_id']);
					$review['target_user_name'] = $targetUser['name'] ?? null;
					$review['target_user_email'] = $targetUser['email'] ?? null;
				}

				// Get match info
				if (!empty($review['match_id'])) {
					$match = $this->matches->findById((string)$review['match_id']);
					$review['match_status'] = $match['status'] ?? null;
				}
			} catch (\Exception $e) {
				// If enrichment fails, continue with what we have
				continue;
			}
		}

		return $reviews;
	}

	/**
	 * Recalculate and update a user's rating based on all reviews they've received.
	 * Updates the Users table with the average rating.
	 *
	 * @param string $userId User ID to recalculate rating for
	 */
	public function recalculateUserRating(string $userId): void
	{
		try {
			$this->validateUUID($userId);

			$reviews = $this->reviews->findBy(['target_user_id' => $userId]);
			$reviews = is_array($reviews) ? $reviews : [];

			if (empty($reviews)) {
				// No reviews, set rating to null
				$this->userService->updateUser($userId, ['rating' => null]);
			} else {
				$ratings = array_column($reviews, 'rating');
				$ratings = array_map('intval', $ratings);
				$averageRating = array_sum($ratings) / count($ratings);

				$this->userService->updateUser($userId, ['rating' => round($averageRating, 2)]);
			}
		} catch (\Exception $e) {
			// If recalculation fails, log but don't throw
			error_log("Failed to recalculate rating for user $userId: " . $e->getMessage());
		}
	}

	/**
	 * Check if a user can review another user in a specific match.
	 * User can review if:
	 * - They are part of the match
	 * - The match is completed
	 * - They haven't already reviewed this user in this match
	 *
	 * @param string $matchId Match ID
	 * @param string $targetUserId User to review
	 * @return bool True if can review
	 */
	public function canReview(string $matchId, string $targetUserId): bool
	{
		try {
			$this->validateUUID($matchId);
			$this->validateUUID($targetUserId);

			$currentUser = $this->authService->requireAuth();
			$match = $this->matches->findById($matchId);

			if (!$match) {
				return false;
			}

			// Must be completed
			if (($match['status'] ?? null) !== 'completed') {
				return false;
			}

			// Must be part of match
			if ($currentUser['id'] !== $match['user_a_id'] && $currentUser['id'] !== $match['user_b_id']) {
				return false;
			}

			// Must not have already reviewed
			$existing = $this->reviews->findBy([
				'match_id' => $matchId,
				'author_id' => $currentUser['id'],
				'target_user_id' => $targetUserId
			]);

			return empty($existing);
		} catch (\Exception $e) {
			return false;
		}
	}
}
