<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * OfferController is responsible for handling offer-related operations such as listing offers,
 * creating a new offer, updating an existing offer, and deleting an offer. Authentication is
 * required for creating, updating, and deleting offers. Admin privileges or being the owner of the
 * offer is required for updating and deleting offers.
 */
class OfferController extends Controller
{
	/**
	 * The OfferModel instance for handling offer-related database operations.
	 * @var OfferModel
	 */
	private $offerModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$this->offerModel = $this->loadModel('OfferModel');
	}

	/**
	 * GET list offers with optional filtering by skill and availability, and pagination.
	 * 
	 * @return array JSON response containing the list of offers or an error message.
	 */
	public function list()
	{
		$skill = isset($_GET['skill_id']) ? trim($_GET['skill_id']) : (isset($_GET['skill']) ? trim($_GET['skill']) : null);
		$availability = isset($_GET['availability']) ? trim($_GET['availability']) : null;

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		if ($skill !== null || $availability !== null) {
			$offers = $this->offerModel->filterOffers($skill, $availability, $limit, $offset);
		} else {
			$offers = $this->offerModel->getAllOffers($limit, $offset);
		}

		return $this->json(['success' => true, 'data' => $offers]);
	}

	/**
	 * POST create a new offer. Requires user authentication.
	 * 
	 * @return array JSON response containing the created offer or an error message.
	 */
	public function create()
	{
		$user = $this->requireAuth();

		$input = $_POST;
		try {
			$payload = $input;
			$payload['user_id'] = $user['id'] ?? null;

			$offer = $this->offerModel->createOffer($payload);
			return $this->json(['success' => true, 'data' => $offer], 201);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * PUT update an existing offer. Requires user authentication and ownership.
	 * 
	 * @return array JSON response containing the updated offer or an error message.
	 */
	public function update()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid offer UUID is required'], 422);
		}

		$existing = $this->offerModel->findById($id);
		if (!$existing) {
			return $this->json(['error' => 'Offer not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && (!isset($user['id']) || $existing['user_id'] !== $user['id'])) {
			return $this->json(['error' => 'Not authorized to update this offer'], 403);
		}

		$data = [];
		if (isset($_POST['skill_id']) || isset($_POST['skill'])) {
			$data['skill_id'] = trim($_POST['skill_id'] ?? $_POST['skill']);
		}

		if (isset($_POST['availability'])) {
			$data['availability'] = trim($_POST['availability']);
		}

		if (isset($_POST['title'])) {
			$data['title'] = trim($_POST['title']);
		}

		if (isset($_POST['description'])) {
			$data['description'] = trim($_POST['description']);
		}

		if (empty($data)) {
			return $this->json(['error' => 'No update data provided'], 422);
		}

		try {
			$ok = $this->offerModel->update($id, $data);
			if (!$ok) {
				return $this->json(['error' => 'Failed to update offer'], 500);
			}
			$updated = $this->offerModel->findById($id);
			return $this->json(['success' => true, 'data' => $updated]);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * DELETE delete an existing offer. Requires user authentication and ownership.
	 * 
	 * @return array JSON response indicating success or an error message.
	 */
	public function delete()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid offer UUID is required'], 422);
		}

		$existing = $this->offerModel->findById($id);
		if (!$existing) {
			return $this->json(['error' => 'Offer not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && (!isset($user['id']) || $existing['user_id'] !== $user['id'])) {
			return $this->json(['error' => 'Not authorized to delete this offer'], 403);
		}

		$hard = isset($_POST['hard']) && ($_POST['hard'] === '1' || $_POST['hard'] === 1 || $_POST['hard'] === true);
		if ($hard && $isAdmin) {
			$removed = $this->offerModel->delete($id);
			if (!$removed) {
				return $this->json(['error' => 'Could not delete offer'], 500);
			}
			return $this->json(['success' => true]);
		}

		$ok = $this->offerModel->soft_delete($id);
		if (!$ok) {
			return $this->json(['error' => 'Could not soft-delete offer'], 500);
		}

		return $this->json(['success' => true]);
	}
}
