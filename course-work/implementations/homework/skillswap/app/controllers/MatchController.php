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

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		require_once __ROOT__ . '/app/services/MatchingService.php';
		$this->matchingService = new MatchingService();
		$this->matchModel = $this->loadModel('MatchModel');
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

		return $this->json(['success' => true, 'data' => $matches]);
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
			return $this->json(['error' => 'Valid matched_user_id required'], 422);
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
			return $this->json(['success' => true, 'data' => $match], 201);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
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
			return $this->json(['error' => 'Valid match_id required'], 422);
		}

		$ok = $this->matchModel->completeMatch($matchId, $userId);
		if (!$ok) {
			return $this->json(['error' => 'Could not complete match or unauthorized'], 400);
		}

		return $this->json(['success' => true]);
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
		return $this->json(['success' => true, 'data' => $matches]);
	}
}
