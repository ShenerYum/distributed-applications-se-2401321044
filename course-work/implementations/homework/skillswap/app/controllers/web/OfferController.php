<?php

namespace App\Controllers\Web;

use App\Core\WebController;
use App\Core\Response;

use App\Services\{
	AuthService,
	UserService,
	OfferService,
	RequestService,
	SkillService
};

class OfferController extends WebController
{
	public function __construct(
		private AuthService $authService,
		private UserService $userService,
		private OfferService $offerService,
		private RequestService $requestService
	) {
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	}

	public function index(): Response
	{
		if (!$this->offerService->listOffers([])) {
			return $this->render('offers/index', ['empty' => true]);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
		$filters = [
			'title' => !empty($_GET['title']) ? trim($_GET['title']) : null,
			'skill_name' => !empty($_GET['skill_name']) ? trim($_GET['skill_name']) : null,
			'user_name' => !empty($_GET['user_name']) ? trim($_GET['user_name']) : null
		];

		try {
			$offers = $this->offerService->listOffers($filters, $limit, $offset);

			$hasRequests = !empty($this->requestService->listRequests(['is_fulfilled' => 0]));

			return $this->render('offers/index', ['offers' => $offers, 'hasFilters' => !empty(array_filter($filters)), 'hasRequests' => $hasRequests]);
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
		return $this->render('offers/create', ['skill_id' => $skillId]);
	}

	public function create(): Response
	{
		$user = $this->authService->getCurrentUser();

		$input = $_POST;
		try {
			$this->offerService->createOffer([
				'skill_id' => trim($input['skill_id'] ?? ''),
				'title' => trim($input['title'] ?? ''),
				'description' => $input['description'] ?? '',
				'availability' => $input['availability'] ?? '',
				'user_id' => $user['id']
			]);

			return $this->redirect('profile/offers');
		} catch (\Exception $e) {
			return $this->retry('offers/create?skill=' . urlencode($input['skill_id'] ?? ''), $e);
		}
	}

	public function editPage(string $id): Response
	{
		try {
			$this->offerService->validateUUID($id);

			$offer = $this->offerService->getOffer($id);
		} catch (\InvalidArgumentException $e) {
			return $this->fail($e);
		}

		return $this->render('offers/edit', ['offer' => $offer]);
	}

	public function edit(string $id): Response
	{
		try {
			$this->offerService->validateUUID($id);

			$offer = $this->offerService->getOffer($id);
		} catch (\InvalidArgumentException $e) {
			return $this->fail($e);
		}

		$input = $_POST;
		try {
			$this->offerService->updateOffer($id, [
				'title' => trim($input['title'] ?? ''),
				'description' => $input['description'] ?? '',
				'availability' => $input['availability'] ?? ''
			]);

			return $this->redirect('profile/offers');
		} catch (\Exception $e) {
			return $this->retry('offers/edit', $e, ['offer' => $offer]);
		}
	}

	public function delete(string $id)
	{
		try {
			$this->offerService->validateUUID($id);
			$this->offerService->deleteOffer($id);

			return $this->redirect('profile/offers');
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	public function myOffers(): Response
	{
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		$user = $this->authService->getCurrentUser();
		$offers = $this->offerService->getOffersByUser($user['id'], $limit, $offset);

		return $this->render('profile/offers', ['offers' => $offers]);
	}

	public function userOffers(string $id)
	{
		try {
			$this->userService->getUser($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		$offers = $this->offerService->getOffersByUser($id, $limit, $offset);

		return $this->render('users/offers', ['offers' => $offers, 'user_id' => $id]);
	}
}
