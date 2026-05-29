<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Models\RequestModel;
use App\Models\OfferModel;

class RequestService extends Service
{
	public function __construct(
		private RequestModel $requests,
		private OfferModel $offers,
		private AuthService $authService,
		private SkillService $skillService,
		private UserService $userService
	) {}


	public function getRequest(string $id): array
	{
		$this->validateUUID($id);

		$request = $this->requests->findById($id);
		if (!$request) {
			throw new \InvalidArgumentException('Request not found', 404);
		}

		return $request;
	}

	public function createRequest(array $data): array
	{
		$this->validateRequiredFields($data, ['title']);

		$request = $this->requests->createRequest($data);
		if (!$request) {
			throw new \RuntimeException('Failed to create request', 500);
		}

		return $request;
	}

	public function updateRequest(string $id, array $data): array
	{
		$this->validateRequiredFields($data, ['title']);

		$updated = $this->requests->updateRequest($id, $data);
		if (!$updated) {
			throw new \RuntimeException('Failed to update request', 500);
		}

		return $updated;
	}

	public function deleteRequest(string $id): void
	{
		if (!$this->requests->deleteRequest($id)) {
			throw new \RuntimeException('Failed to delete request', 500);
		}
	}

	private function getRequestsBySkill(string $skillId): array
	{
		return $this->requests->findBy(['skill_id' => $skillId]);
	}

	public function personalize(array $currentUser): array
	{
		$requests = [];

		$offers = $this->offers->findBy(['user_id' => $currentUser['id']]);
		if (!is_array($offers)) {
			return [];
		}

		// Get all requests related to the user's offers
		$skillIds = array_unique(array_column($offers, 'skill_id'));
		foreach ($skillIds as $skillId) {
			$rows = $this->getRequestsBySkill((string)$skillId);
			if (!is_array($rows)) {
				continue;
			}
			$requests = array_merge($requests, $rows);
		}
		return $requests;
	}


	public function listRequests(array $filters, int $limit = 20, int $offset = 0): array
	{
		if (empty($filters)) {
			return $this->requests->findAll($limit, $offset);
		}

		$currentUser = $this->authService->getCurrentUser();
		$filters['is_active'] = ($currentUser['is_admin'] ?? false) ? null : 1;

		$requests = [];

		if (!($currentUser['is_admin'] ?? false)) {
			$requests = $this->personalize($currentUser);


			$seenIds = [];
			$uniqueRequests = [];
			foreach ($requests as $request) {
				if (!in_array($request['id'], $seenIds)) {
					$seenIds[] = $request['id'];
					$uniqueRequests[] = $request;
				}
			}
			$requests = $uniqueRequests;
		}

		// Filter by title if provided
		if (!empty($filters['title'])) {
			$requests = array_filter($requests, function ($request) use ($filters) {
				return stripos($request['title'] ?? '', $filters['title']) !== false;
			});
		}

		// Intersect requests with requests matching skill name
		if (!empty($filters['skill_name'])) {
			$skills = $this->skillService->getSkillsByName(trim($filters['skill_name']));

			if (empty($skills)) return [];

			$requestsBySkills = [];
			foreach ($skills as $skill) {
				$requestsBySkills[] = $this->getRequestsBySkill($skill['id']);
			}

			$skillRequestIds = array_column(array_merge(...$requestsBySkills), 'id');
			$requests = array_filter($requests, function ($request) use ($skillRequestIds) {
				return in_array($request['id'], $skillRequestIds);
			});
		}

		// Intersect requests with requests made by users matching user name
		if (!empty($filters['user_name'])) {
			$users = $this->userService->getByName(trim($filters['user_name']));

			if (empty($users)) return [];

			$requestsByUsers = [];
			foreach ($users as $user) {
				$requestsByUsers[] = $this->getRequestsByUser($user['id']);
			}

			$userRequestIds = array_column(array_merge(...$requestsByUsers), 'id');
			$requests = array_filter($requests, function ($request) use ($userRequestIds) {
				return in_array($request['id'], $userRequestIds);
			});

			$requests = array_values($requests);
		}

		// Exclude requests made by the current user
		$requests = array_filter($requests, function ($request) use ($currentUser) {
			return ($request['user_id'] ?? null) !== $currentUser['id']
				&& ($request['is_active'] ?? 1) === 1;
		});

		$requests = array_values($requests);

		$seenIds = [];
		$uniqueRequests = [];
		foreach ($requests as $request) {
			if (!in_array($request['id'], $seenIds)) {
				$seenIds[] = $request['id'];
				$uniqueRequests[] = $request;
			}
		}

		return array_slice($uniqueRequests, $offset, $limit);
	}


	public function getRequestsByUser(string $userId, int $limit = 20, int $offset = 0): array
	{
		$requests = $this->requests->findBy(['user_id' => $userId]);
		$requests = is_array($requests) ? $requests : [];

		$currentUser = $this->authService->getCurrentUser();
		if (!($currentUser['is_admin'] ?? false)) {
			$personal = $this->personalize($currentUser);
			if (is_array($personal) && !empty($personal)) {
				$requests = array_merge($requests, $personal);
			}
		}

		$seenIds = [];
		$uniqueRequests = [];
		foreach ($requests as $request) {
			if (!is_array($request) || !isset($request['id'])) {
				continue;
			}
			if (!in_array($request['id'], $seenIds)) {
				$seenIds[] = $request['id'];
				$uniqueRequests[] = $request;
			}
		}

		return array_slice($uniqueRequests, $offset, $limit);
	}
}
