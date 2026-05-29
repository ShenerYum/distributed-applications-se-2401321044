<?php

namespace App\Controllers\Web;

use App\Core\WebController;
use App\Core\Response;

use App\Services\{
	AuthService,
	MatchService,
	OfferService,
	RequestService,
	// ReviewService
};


/**
 * MatchController is responsible for handling match-related operations such as finding mutual matches,
 * accepting a proposed match, completing a match, and listing matches for the current user. Authentication
 * is required for all operations.
 */
class MatchController extends WebController
{
	public function __construct(
		private AuthService $authService,
		private MatchService $matchService,
		private OfferService $offerService,
		private RequestService $requestService,
		// private ReviewService $reviewService,
	) {
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	}

	// Admin matches page
	public function index(): Response
	{
		try {
			$this->authService->requireAdmin();
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
		$filters = [
			'user_name' => !empty($_GET['user_name']) ? trim($_GET['user_name']) : null,
			'skill_name' => !empty($_GET['skill_name']) ? trim($_GET['skill_name']) : null,
		];

		try {
			$matches = $this->matchService->listMatches($filters, $limit, $offset);

			return $this->render('matches/index', ['matches' => $matches, 'hasFilters' => !empty($filters), 'empty' => empty(array_filter($matches))]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	// Profile matches page
	public function myMatches(): Response
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
			'current_user_id' => $user['id'],
			'user_name' => !empty($_GET['user_name']) ? trim($_GET['user_name']) : null,
			'skill_name' => !empty($_GET['skill_name']) ? trim($_GET['skill_name']) : null,
		];

		try {
			$matches = $this->matchService->listMatches($filters, $limit, $offset);

			return $this->render('profile/matches', ['matches' => $matches, 'empty' => empty(array_filter($matches))]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	// Create match page
	public function matchPage(string $id): Response
	{
		try {
			$this->authService->requireAuth();
			$this->authService->validateUUID($id);
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$user = $this->authService->getCurrentUser();

		try {
			$offer = $this->offerService->getOffer($id);
			if ($offer) {
				$requests = $this->requestService->getRequestsByUser($user['id']);
				$requests = array_filter($requests, function ($r) use ($offer) {
					return $r['skill_id'] === $offer['skill_id'];
				});

				return $this->render('matches/create', ['offer' => $offer, 'requests' => $requests]);
			}

			$request = $this->requestService->getRequest($id);
			if ($request) {
				$offers = $this->offerService->getOffersByUser($user['id']);
				$offers = array_filter($offers, function ($o) use ($request) {
					return $o['skill_id'] === $request['skill_id'];
				});

				return $this->render('matches/create', ['request' => $request, 'offers' => $offers]);
			}

			return $this->fail(new \Exception('Offer or request not found', 404));
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	public function match(): Response
	{
		try {
			$this->authService->requireAuth();
		} catch (\Exception $e) {
			return $this->fail($e);
		}

		$input = $_POST;
		try {
			$this->matchService->createMatch([
				'user_a_id' => $input['user_a_id'] ?? null,
				'user_b_id' => $input['user_b_id'] ?? null,
				'offer_id' => $input['offer_a_id'] ?? null,
				'request_id' => $input['request_a_id'] ?? null,
				'status' => 'pending',
			]);

			return $this->redirect('profile/matches');
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	private function update(array $data): Response
	{
		$input = $_POST;
		try {
			$this->authService->requireAuth();

			$this->matchService->updateMatch($input['match_id'] ?? '', $data);

			return $this->redirect('profile/matches');
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	public function accept(): Response
	{
		try {
			return $this->update([
				'status' => 'accepted',
				'accepted_at' => date('Y-m-d H:i:s'),
			]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	public function reject(): Response
	{
		try {
			return $this->update(['status' => 'rejected']);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	public function complete(): Response
	{
		try {
			return $this->update([
				'status' => 'completed',
				'completed_at' => date('Y-m-d H:i:s'),
			]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}

	public function delete(): Response
	{
		try {
			$m = $this->matchService->getMatch($_POST['match_id'] ?? '');

			[$userA, $userB] = [$m['user_a_id'], $m['user_b_id']];
			$this->matchService->deleteMatch($_POST['match_id'] ?? '');

			// $this->reviewService->recalculateUserRating($userA);
			// $this->reviewService->recalculateUserRating($userB);

			return $this->redirect('profile/matches');
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}
}
