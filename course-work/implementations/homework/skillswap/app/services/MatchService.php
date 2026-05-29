<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;

use App\Models\{
	MatchModel
};
use App\Services\{
	OfferService,
	RequestService,
	UserService
};

/**
 * MatchService is responsible for finding mutual matches between users based on their offers and requests.
 * It computes a score for each match based on availability and user ratings.
 */
class MatchService extends Service
{

	public function __construct(
		private MatchModel $matches,
		private OfferService $offerService,
		private RequestService $requestService,
		private UserService $userService,
		private AuthService $authService
	) {}



	public function getMatch(string $id): array
	{
		$this->validateUUID($id);

		$match = $this->matches->findById($id);
		if (!$match) {
			throw new \InvalidArgumentException('Match not found', 404);
		}

		return $match;
	}

	public function createMatch(array $data): array
	{
		$currentUser = $this->authService->getCurrentUser();
		$isAdmin = (bool)($currentUser['is_admin'] ?? false);

		if ($isAdmin) throw new \InvalidArgumentException('Unauthorized: Admins cannot match', 403);

		$this->validateRequiredFields($data, ['user_a_id', 'user_b_id', 'offer_id', 'request_id']);

		$this->validateUUID((string)$data['user_a_id']);
		$this->validateUUID((string)$data['user_b_id']);
		$this->validateUUID((string)$data['offer_id']);
		$this->validateUUID((string)$data['request_id']);

		$o = $this->offerService->getOffer((string)$data['offer_id']);
		$r = $this->requestService->getRequest((string)$data['request_id']);

		if ($r['skill_id'] !== $o['skill_id']) {
			throw new \InvalidArgumentException('Offer and request skill mismatch', 400);
		}

		$match = $this->matches->createMatch($data);
		if (!$match) {
			throw new \RuntimeException('Failed to create match', 500);
		}

		return $match;
	}


	public function updateMatch(string $id, array $data): array
	{
		$this->validateUUID($id);
		$this->validateRequiredFields($data, ['status']);

		$updated = $this->matches->updateMatch($id, $data);
		if (!$updated) {
			throw new \RuntimeException('Failed to update match', 500);
		}

		return $updated;
	}

	public function deleteMatch(string $id): void
	{
		$this->validateUUID($id);

		if (!$this->matches->deleteMatch($id)) {
			throw new \RuntimeException('Failed to delete match', 500);
		}
	}

	public function listMatches(array $filters = [], int $limit = 20, int $offset = 0): array
	{
		$matches = $this->matches->findAll($limit, $offset);

		$matches = is_array($matches) ? $matches : [];

		if (empty($filters) || !is_array($matches)) {
			return $matches;
		}

		$matches = array_filter($matches, function ($m) use ($filters) {
			$userA = $this->userService->getUser((string)$m['user_a_id']);
			$userB = $this->userService->getUser((string)$m['user_b_id']);
			$offer = $this->offerService->getOffer((string)$m['offer_id']);
			$request = $this->requestService->getRequest((string)$m['request_id']);

			if (!empty($filters['current_user_id'] ?? null)) {
				$currentUserId = (string)$filters['current_user_id'];
				return !((string)$m['user_a_id'] !== $currentUserId && (string)$m['user_b_id'] !== $currentUserId);
			}

			if (!empty($filters['user_name'] ?? null)) {
				$userName = strtolower((string)$filters['user_name']);

				$nameA = strtolower((string)($userA['name'] ?? ''));
				$nameB = strtolower((string)($userB['name'] ?? ''));

				return !(strpos($nameA, $userName) === false && strpos($nameB, $userName) === false);
			}

			if (!empty($filters['skill_name'] ?? null)) {
				$skillName = strtolower((string)$filters['skill_name']);

				$offerSkillName = strtolower((string)($offer['skill_name'] ?? ''));
				$requestSkillName = strtolower((string)($request['skill_name'] ?? ''));

				return !(strpos($offerSkillName, $skillName) === false && strpos($requestSkillName, $skillName) === false);
			}

			return true;
		});

		foreach ($matches as &$m) {
			$userA = $this->userService->getUser((string)$m['user_a_id']);
			$userB = $this->userService->getUser((string)$m['user_b_id']);
			$offer = $this->offerService->getOffer((string)$m['offer_id']);
			$request = $this->requestService->getRequest((string)$m['request_id']);

			$m['user_a_name'] = $userA['name'] ?? null;
			$m['user_a_email'] = $userA['email'] ?? null;

			$m['user_b_name'] = $userB['name'] ?? null;
			$m['user_b_email'] = $userB['email'] ?? null;

			$m['offer_title'] = $offer['title'] ?? null;
			$m['request_title'] = $request['title'] ?? null;
		}


		return array_values($matches);
	}
}
