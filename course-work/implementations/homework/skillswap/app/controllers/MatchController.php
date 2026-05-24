<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * MatchController is responsible for handling match-related operations such as finding mutual matches,
 * accepting a proposed match, completing a match, and listing matches for the current user. Authentication
 * is required for all operations.
 */
class MatchController extends Controller
{
	/**
	 * The MatchingService instance for handling match finding and scoring logic.
	 * @var MatchingService
	 */
	private $matchingService;

	/**
	 * The MatchModel instance for handling match-related database operations.
	 * @var MatchModel
	 */
	private $matchModel;

	/**
	 * RatingService instance for recalculating user ratings when reviews change.
	 * @var RatingService
	 */
	private $ratingService;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		require_once __ROOT__ . '/app/services/MatchingService.php';
		require_once __ROOT__ . '/app/services/RatingService.php';

		$this->matchingService = new MatchingService();
		$this->ratingService = new RatingService();

		$this->matchModel = $this->loadModel('MatchModel');
	}

	/**
	 * Admin index to list all matches. Only accessible to admin users.
	 */
	public function index()
	{
		$user = $this->requireAdmin();

		$sort = $_GET['sort'] ?? 'created_at';
		$matches = $this->matchModel->getAllMatches(200, 0, $sort);
		$this->render('matches/index', ['matches' => $matches, 'sort' => $sort]);
	}

	/**
	 * DELETE a match. Only admin may delete matches. Cascade-deletes reviews and recalculates affected ratings.
	 */
	public function delete()
	{
		$user = $this->requireAdmin();

		$id = trim($_POST['id'] ?? ($_GET['id'] ?? ''));
		if (!$this->isValidUUID($id)) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'Valid match id required'], 422);

			$_SESSION['flash'][] = ['message' => 'Valid match id required'];
			$this->redirect('matches');
		}

		$match = $this->matchModel->findById($id);
		if (!$match) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'Match not found'], 404);

			$_SESSION['flash'][] = ['message' => 'Match not found'];
			$this->redirect('matches');
		}

		$uids = [];
		if (!empty($match['user_a_id'])) $uids[] = $match['user_a_id'];
		if (!empty($match['user_b_id'])) $uids[] = $match['user_b_id'];

		$ok = $this->matchModel->deleteMatch($id);
		if (!$ok) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'Failed to delete match'], 500);

			$_SESSION['flash'][] = ['message' => 'Failed to delete match'];
			$this->redirect('matches');
		}

		foreach (array_unique($uids) as $uid) {
			if ($uid) $this->ratingService->recalcUserRating($uid);
		}

		if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) $this->json(['success' => true]);
		$_SESSION['flash'][] = ['message' => 'Match deleted'];

		$this->redirect('matches');
	}

	/**
	 * Render create match form (GET) and handle creation (POST).
	 * Expects either ?offer_id=... (to match an offer) or ?request_id=... (to match a request)
	 */
	public function create()
	{
		$user = $this->requireAuth();
		$uid = $user['id'];

		$offerModel = $this->loadModel('OfferModel');
		$requestModel = $this->loadModel('RequestModel');

		$targetOfferId = trim($_GET['offer_id'] ?? $_POST['offer_id'] ?? '');
		$targetRequestId = trim($_GET['request_id'] ?? $_POST['request_id'] ?? '');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			if ($targetOfferId) {
				$target = $offerModel->findById($targetOfferId);
				if (!$target) $this->json(['error' => 'Offer not found'], 404);

				if (($target['user_id'] ?? null) === $uid) {
					$_SESSION['flash'][] = ['message' => 'Cannot create a match against your own offer'];
					$this->redirect('offers');
				}
				$candidates = $requestModel->getRequestsByUser($uid);
				$candidates = array_filter($candidates, function ($r) use ($target) {
					return ($r['skill_id'] ?? null) === ($target['skill_id'] ?? null);
				});

				$this->render('matches/create', ['target' => $target, 'target_type' => 'offer', 'candidates' => $candidates]);
				return;
			} elseif ($targetRequestId) {
				$target = $requestModel->findById($targetRequestId);
				if (!$target) $this->json(['error' => 'Request not found'], 404);

				if (($target['user_id'] ?? null) === $uid) {
					$_SESSION['flash'][] = ['message' => 'Cannot create a match against your own request'];
					$this->redirect('requests');
				}

				$candidates = $offerModel->getOffersByUser($uid);
				$candidates = array_filter($candidates, function ($o) use ($target) {
					return ($o['skill_id'] ?? null) === ($target['skill_id'] ?? null);
				});

				$this->render('matches/create', ['target' => $target, 'target_type' => 'request', 'candidates' => $candidates]);
				return;
			}

			$this->json(['error' => 'offer_id or request_id required to create match'], 422);
		}

		$selected = trim($_POST['my_selection'] ?? '');
		if (!$selected) {
			$this->render('matches/create', ['errors' => ['Select one of your items']]);
			return;
		}

		$payload = ['score' => isset($_POST['score']) ? (int)$_POST['score'] : 0, 'status' => 'pending'];
		if ($targetOfferId) {
			$target = $offerModel->findById($targetOfferId);
			$payload['offer_id'] = $targetOfferId;
			$payload['request_id'] = $selected;
			$payload['user_a_id'] = $uid;
			$payload['user_b_id'] = $target['user_id'];
		} else {
			$target = $requestModel->findById($targetRequestId);
			$payload['request_id'] = $targetRequestId;
			$payload['offer_id'] = $selected;
			$payload['user_a_id'] = $uid;
			$payload['user_b_id'] = $target['user_id'];
		}

		try {
			$match = $this->matchModel->createMatch($payload);

			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
				$_SESSION['flash'][] = ['message' => 'Match created and is pending'];
				$this->redirect('profile/matches');
			}

			$this->json(['success' => true, 'data' => $match], 201);
		} catch (Exception $e) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
				$this->render('matches/create', ['errors' => [$e->getMessage()]]);
			} else {
				$this->json(['error' => $e->getMessage()], 400);
			}
		}
	}

	/**
	 * Accept an existing match. POST: match_id
	 */
	public function accept()
	{
		$user = $this->requireAuth();

		$matchId = trim($_POST['match_id'] ?? '');
		if (!$matchId) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'match_id required'], 422);

			$_SESSION['flash'][] = ['message' => 'match_id required'];
			$this->redirect('profile/matches');
		}

		$ok = $this->matchModel->acceptMatch($matchId, $user['id']);
		if (!$ok) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'Could not accept match or unauthorized'], 400);

			$_SESSION['flash'][] = ['message' => 'Could not accept match or unauthorized'];
			$this->redirect('profile/matches');
		}

		if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) $this->json(['success' => true]);

		$_SESSION['flash'][] = ['message' => 'Match accepted'];
		$this->redirect('profile/matches');
	}

	/**
	 * Reject (soft-delete) a match. POST: match_id
	 */
	public function reject()
	{
		$user = $this->requireAuth();

		$matchId = trim($_POST['match_id'] ?? '');
		if (!$matchId) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'match_id required'], 422);

			$_SESSION['flash'][] = ['message' => 'match_id required'];
			$this->redirect('profile/matches');
		}

		$ok = $this->matchModel->rejectMatch($matchId, $user['id']);
		if (!$ok) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'Could not reject match or unauthorized'], 400);

			$_SESSION['flash'][] = ['message' => 'Could not reject match or unauthorized'];
			$this->redirect('profile/matches');
		}

		if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) $this->json(['success' => true]);

		$_SESSION['flash'][] = ['message' => 'Match rejected'];
		$this->redirect('profile/matches');
	}

	/**
	 * Complete an existing match. POST: match_id
	 */
	public function complete()
	{
		$user = $this->requireAuth();

		$matchId = trim($_POST['match_id'] ?? '');
		if (!$matchId) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'match_id required'], 422);

			$_SESSION['flash'][] = ['message' => 'match_id required'];
			$this->redirect('profile/matches');
		}

		$ok = $this->matchModel->completeMatch($matchId, $user['id']);
		if (!$ok) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
				$this->json(['error' => 'Could not complete match or unauthorized'], 400);

			$_SESSION['flash'][] = ['message' => 'Could not complete match or unauthorized'];
			$this->redirect('profile/matches');
		}

		if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) $this->json(['success' => true]);

		$_SESSION['flash'][] = ['message' => 'Match marked completed'];
		$this->redirect('profile/matches');
	}

	/**
	 * Render profile matches for the current user (HTML view)
	 */
	public function myMatches()
	{
		$user = $this->requireAuth();

		$matches = $this->matchModel->findForUser($user['id']);

		$offerModel = $this->loadModel('OfferModel');
		$requestModel = $this->loadModel('RequestModel');
		$userModel = $this->loadModel('UserModel');
		$reviewModel = $this->loadModel('ReviewModel');
		$enriched = [];
		foreach ($matches as $m) {
			$m['offer'] = $m['offer_id'] ? $offerModel->findById($m['offer_id']) : null;
			$m['request'] = $m['request_id'] ? $requestModel->findById($m['request_id']) : null;
			$m['user_a'] = ($m['user_a_id'] ?? null) ? $userModel->findById($m['user_a_id']) : null;
			$m['user_b'] = ($m['user_b_id'] ?? null) ? $userModel->findById($m['user_b_id']) : null;

			$m['my_review'] = $reviewModel->findByMatchAndReviewer($m['id'], $user['id']);
			$enriched[] = $m;
		}

		$this->render('profile/matches', ['matches' => $enriched]);
	}

	/**
	 * Find mutual matches for the current user. Expects GET: limit (optional).
	 * 
	 * @return array JSON response containing the list of mutual matches.
	 */
	public function findMatches()
	{
		$user = $this->requireAuth();
		$userId = $user['id'];

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
		$matches = $this->matchingService->findMutualMatches($userId, $limit);

		$this->json(['success' => true, 'data' => $matches]);
	}

	/**
	 * Accept a proposed match (create Match record and mark accepted).
	 * Expects POST: matched_user_id, my_offer_id, my_request_id, their_offer_id, their_request_id, score
	 * 
	 * @return array JSON response containing the created match or an error message.
	 */
	public function acceptMatch()
	{
		$user = $this->requireAuth();
		$userId = $user['id'];

		$matchedUser = trim($_POST['matched_user_id'] ?? '');
		if (!$this->isValidUUID($matchedUser)) {
			$this->json(['error' => 'Valid matched_user_id required'], 422);
		}

		$myOffer = trim($_POST['my_offer_id'] ?? '');
		$myRequest = trim($_POST['my_request_id'] ?? '');
		$theirOffer = trim($_POST['their_offer_id'] ?? '');
		$theirRequest = trim($_POST['their_request_id'] ?? '');

		$data = [
			'user_a_id' => $userId,
			'user_b_id' => $matchedUser,
			'offer_a_id' => $myOffer ?: null,
			'request_a_id' => $myRequest ?: null,
			'offer_b_id' => $theirOffer ?: null,
			'request_b_id' => $theirRequest ?: null,
			'score' => isset($_POST['score']) ? (int)$_POST['score'] : 0,
			'status' => 'accepted',
		];

		try {
			$match = $this->matchModel->createMatch($data);
			$this->json(['success' => true, 'data' => $match], 201);
		} catch (Exception $e) {
			$this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * Mark a match as completed. Expects POST: match_id
	 * 
	 * @return array JSON response indicating success or failure of completing the match.
	 */
	public function completeMatch()
	{
		$user = $this->requireAuth();
		$userId = $user['id'];

		$matchId = trim($_POST['match_id'] ?? '');
		if (!$this->isValidUUID($matchId)) {
			$this->json(['error' => 'Valid match_id required'], 422);
		}

		$ok = $this->matchModel->completeMatch($matchId, $userId);
		if (!$ok) $this->json(['error' => 'Could not complete match or unauthorized'], 400);

		$this->json(['success' => true]);
	}

	/**
	 * GET list matches for the current user with optional pagination. Expects GET: limit (optional), offset (optional).
	 * 
	 * @return array JSON response containing the list of matches for the user.
	 */
	public function list()
	{
		$user = $this->requireAuth();
		$userId = $user['id'];

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : null;

		$matches = $this->matchModel->findForUser($userId, $limit, $offset);
		$this->json(['success' => true, 'data' => $matches]);
	}
}
