<?php

namespace App\Controllers\Web;

use App\Core\WebController;
use App\Core\Response;

use App\Services\{
	AuthService,
	UserService,
	RequestService,
	OfferService,
	SkillService
};

class RequestController extends WebController
{
	public function __construct(
		private AuthService $authService,
		private UserService $userService,
		private RequestService $requestService,
		private OfferService $offerService
	) {
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	}

	public function index(): Response
	{
		if (!$this->requestService->listRequests([])) {
			return $this->render('requests/index', ['empty' => true]);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
		$filters = [
			'title' => !empty($_GET['title']) ? trim($_GET['title']) : null,
			'skill_name' => !empty($_GET['skill_name']) ? trim($_GET['skill_name']) : null,
			'user_name' => !empty($_GET['user_name']) ? trim($_GET['user_name']) : null
		];

		try {
			$requests = $this->requestService->listRequests($filters, $limit, $offset);

			$hasOffers = !empty($this->offerService->listOffers(['is_active' => 0]));

			return $this->render('requests/index', ['requests' => $requests, 'hasFilters' => !empty(array_filter($filters)), 'hasOffers' => $hasOffers]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	public function createPage(): Response
	{
		$skillId = $_GET['skill'] ?? $_GET['skill_id'] ?? null;
		if (!$skillId) {
			return $this->fail(new \Exception('The selected skill is missing.', 404));
		}
		return $this->render('requests/create', ['skill_id' => $skillId]);
	}

	public function create(): Response
	{
		$user = $this->authService->getCurrentUser();

		$input = $_POST;
		try {
			$this->requestService->createRequest([
				'skill_id' => trim($input['skill_id'] ?? ''),
				'title' => trim($input['title'] ?? ''),
				'desired_level' => $input['desired_level'] ?? '',
				'notes' => $input['notes'] ?? '',
				'max_hours' => $input['max_hours'] ?? '',
				'user_id' => $user['id'],
			]);

			return $this->redirect('profile/requests');
		} catch (\Exception $e) {
			return $this->retry('requests/create?skill=' . urlencode($input['skill_id'] ?? ''), $e);
		}
	}

	public function editPage(string $id): Response
	{
		try {
			$this->requestService->validateUUID($id);

			$request = $this->requestService->getRequest($id);
		} catch (\InvalidArgumentException $e) {
			return $this->fail($e);
		}

		return $this->render('requests/edit', ['request' => $request]);
	}

	public function edit(string $id): Response
	{
		try {
			$this->requestService->validateUUID($id);

			$request = $this->requestService->getRequest($id);
		} catch (\InvalidArgumentException $e) {
			return $this->fail($e);
		}

		$input = $_POST;
		try {
			$this->requestService->updateRequest($id, [
				'title' => trim($input['title'] ?? ''),
				'desired_level' => $input['desired_level'] ?? '',
				'notes' => $input['notes'] ?? '',
				'max_hours' => $input['max_hours'] ?? ''
			]);

			return $this->redirect('profile/requests');
		} catch (\Exception $e) {
			return $this->retry('requests/edit', $e, ['request' => $request]);
		}
	}

	public function delete(string $id): Response
	{
		try {
			$this->requestService->validateUUID($id);
			$this->requestService->deleteRequest($id);

			return $this->redirect('profile/requests');
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	public function myRequests(): Response
	{
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		$user = $this->authService->getCurrentUser();
		$requests = $this->requestService->getRequestsByUser($user['id'], $limit, $offset);

		return $this->render('profile/requests', ['requests' => $requests]);
	}

	public function userRequests(string $id)
	{
		try {
			$this->userService->getUser($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		$requests = $this->requestService->getRequestsByUser($id, $limit, $offset);

		return $this->render('users/requests', ['requests' => $requests, 'user_id' => $id]);
	}
}
