<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Models\OfferModel;
use App\Models\RequestModel;

class OfferService extends Service
{
	public function __construct(
		private OfferModel $offers,
		private RequestModel $requests,
		private AuthService $authService,
		private SkillService $skillService,
		private UserService $userService
	) {}


	public function getOffer(string $id): array
	{
		$this->validateUUID($id);

		$offer = $this->offers->findById($id);
		if (!$offer) {
			throw new \InvalidArgumentException('Offer not found', 404);
		}

		return $offer;
	}

	public function createOffer(array $data): array
	{
		$this->validateRequiredFields($data, ['title']);

		$offer = $this->offers->createOffer($data);
		if (!$offer) {
			throw new \RuntimeException('Failed to create offer', 500);
		}

		return $offer;
	}

	public function updateOffer(string $id, array $data): array
	{
		$this->validateRequiredFields($data, ['title']);

		$updated = $this->offers->updateOffer($id, $data);
		if (!$updated) {
			throw new \RuntimeException('Failed to update offer', 500);
		}

		return $updated;
	}

	public function deleteOffer(string $id): void
	{
		if (!$this->offers->deleteOffer($id)) {
			throw new \RuntimeException('Failed to delete offer', 500);
		}
	}

	private function getOffersBySkill(string $skillId): array
	{
		return $this->offers->findBy(['skill_id' => $skillId]);
	}

	public function personalize(array $currentUser): array
	{
		$offers = [];

		$requests = $this->requests->findBy(['user_id' => $currentUser['id']]);
		if (!is_array($requests)) {
			return [];
		}

		// Get all offers related to the user's requests
		$skillIds = array_unique(array_column($requests, 'skill_id'));
		foreach ($skillIds as $skillId) {
			$rows = $this->getOffersBySkill((string)$skillId);
			if (!is_array($rows)) {
				continue;
			}
			$offers = array_merge($offers, $rows);
		}
		return $offers;
	}


	public function listOffers(array $filters, int $limit = 20, int $offset = 0): array
	{
		if (empty($filters)) {
			return $this->offers->findAll($limit, $offset);
		}

		$currentUser = $this->authService->getCurrentUser();
		$filters['is_active'] = ($currentUser['is_admin'] ?? false) ? null : 1;

		$offers = [];

		if (!($currentUser['is_admin'] ?? false)) {
			$offers = $this->personalize($currentUser);


			$seenIds = [];
			$uniqueOffers = [];
			foreach ($offers as $offer) {
				if (!in_array($offer['id'], $seenIds)) {
					$seenIds[] = $offer['id'];
					$uniqueOffers[] = $offer;
				}
			}
			$offers = $uniqueOffers;
		}

		// Filter by title if provided
		if (!empty($filters['title'])) {
			$offers = array_filter($offers, function ($offer) use ($filters) {
				return stripos($offer['title'] ?? '', $filters['title']) !== false;
			});
		}

		// Intersect offers with offers matching skill name
		if (!empty($filters['skill_name'])) {
			$skills = $this->skillService->getSkillsByName(trim($filters['skill_name']));

			if (empty($skills)) return [];

			$offersBySkills = [];
			foreach ($skills as $skill) {
				$offersBySkills[] = $this->getOffersBySkill($skill['id']);
			}

			$skillOfferIds = array_column(array_merge(...$offersBySkills), 'id');
			$offers = array_filter($offers, function ($offer) use ($skillOfferIds) {
				return in_array($offer['id'], $skillOfferIds);
			});
		}

		// Intersect offers with offers made by users matching user name
		if (!empty($filters['user_name'])) {
			$users = $this->userService->getByName(trim($filters['user_name']));

			if (empty($users)) return [];

			$offersByUsers = [];
			foreach ($users as $user) {
				$offersByUsers[] = $this->getOffersByUser($user['id']);
			}

			$userOfferIds = array_column(array_merge(...$offersByUsers), 'id');
			$offers = array_filter($offers, function ($offer) use ($userOfferIds) {
				return in_array($offer['id'], $userOfferIds);
			});

			$offers = array_values($offers);
		}

		// Exclude offers made by the current user
		$offers = array_filter($offers, function ($offer) use ($currentUser) {
			return ($offer['user_id'] ?? null) !== $currentUser['id']
				&& ($offer['is_active'] ?? 1) === 1;
		});

		$offers = array_values($offers);

		$seenIds = [];
		$uniqueOffers = [];
		foreach ($offers as $offer) {
			if (!in_array($offer['id'], $seenIds)) {
				$seenIds[] = $offer['id'];
				$uniqueOffers[] = $offer;
			}
		}

		return array_slice($uniqueOffers, $offset, $limit);
	}


	public function getOffersByUser(string $userId, int $limit = 20, int $offset = 0): array
	{
		$offers = $this->offers->findBy(['user_id' => $userId]);
		$offers = is_array($offers) ? $offers : [];

		$currentUser = $this->authService->getCurrentUser();
		if (!($currentUser['is_admin'] ?? false)) {
			$personal = $this->personalize($currentUser);
			if (is_array($personal) && !empty($personal)) {
				$offers = array_merge($offers, $personal);
			}
		}

		$seenIds = [];
		$uniqueOffers = [];
		foreach ($offers as $offer) {
			if (!is_array($offer) || !isset($offer['id'])) {
				continue;
			}
			if (!in_array($offer['id'], $seenIds)) {
				$seenIds[] = $offer['id'];
				$uniqueOffers[] = $offer;
			}
		}

		return array_slice($uniqueOffers, $offset, $limit);
	}
}
