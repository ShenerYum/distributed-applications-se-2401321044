<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * OfferController handles offers browsing, creation and management (HTML + API).
 */
class OfferController extends Controller
{
	/** @var OfferModel */
	private $offerModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
		$this->offerModel = $this->loadModel('OfferModel');
	}

	/**
	 * API: list offers (JSON) with optional filtering.
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

		$this->json(['success' => true, 'data' => $offers]);
	}

	/**
	 * Render browse offers page (HTML) or return JSON list.
	 */
	public function index()
	{
		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

		$skill = isset($_GET['skill_id']) ? trim($_GET['skill_id']) : (isset($_GET['skill']) ? trim($_GET['skill']) : null);
		$availability = isset($_GET['availability']) ? trim($_GET['availability']) : null;

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		$user = $this->getCurrentUser();
		$needs_skill = false;
		if ($user) {
			$requestModel = $this->loadModel('RequestModel');
			$userRequests = $requestModel->getRequestsByUser($user['id']);
			if (!empty($userRequests)) {
				$skillIds = array_values(array_unique(array_filter(array_column($userRequests, 'skill_id'))));

				$offers = !empty($skillIds) ? $this->offerModel->getOffersBySkillIds($skillIds, $limit, $offset) : [];
			} else {
				$needs_skill = true;
				$offers = [];
			}
		} else {
			if ($skill !== null || $availability !== null) {
				$offers = $this->offerModel->filterOffers($skill, $availability, $limit, $offset);
			} else {
				$offers = $this->offerModel->getAllOffers($limit, $offset);
			}
		}

		if ($isApi) $this->json(['success' => true, 'data' => $offers]);

		$this->render('offers/index', ['offers' => $offers, 'needs_skill' => $needs_skill]);
	}

	/**
	 * Render create offer form (GET) and handle creation (POST).
	 */
	public function create()
	{
		$user = $this->requireAuth();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$skillId = $_GET['skill'] ?? $_GET['skill_id'] ?? null;
			$this->render('offers/create', ['skill_id' => $skillId]);
			return;
		}

		$skillId = trim($_POST['skill_id'] ?? $_POST['skill'] ?? '');
		$title = trim($_POST['title'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$availability = trim($_POST['availability'] ?? '');

		try {
			$offer = $this->offerModel->createOffer([
				'skill_id' => $skillId,
				'title' => $title,
				'description' => $description,
				'availability' => $availability,
				'user_id' => $user['id'] ?? null,
			]);

			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
				$this->json(['success' => true, 'data' => $offer], 201);
			}

			$this->redirect('profile/offers');
		} catch (Exception $e) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
				$this->json(['error' => $e->getMessage()], 400);
			}
			$this->render('offers/create', ['errors' => [$e->getMessage()], 'skill_id' => $skillId]);
		}
	}

	/**
	 * API: update an existing offer (JSON).
	 */
	public function update()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Valid offer UUID is required'], 422);
		}

		$existing = $this->offerModel->findById($id);
		if (!$existing) {
			$this->json(['error' => 'Offer not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && (!isset($user['id']) || $existing['user_id'] !== $user['id'])) {
			$this->json(['error' => 'Not authorized to update this offer'], 403);
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
			$this->json(['error' => 'No update data provided'], 422);
		}

		try {
			$ok = $this->offerModel->update($id, $data);
			if (!$ok) {
				$this->json(['error' => 'Failed to update offer'], 500);
			}
			$updated = $this->offerModel->findById($id);
			$this->json(['success' => true, 'data' => $updated]);
		} catch (Exception $e) {
			$this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * API: delete an offer (JSON POST id).
	 */
	public function delete()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Valid offer UUID is required'], 422);
		}

		$existing = $this->offerModel->findById($id);
		if (!$existing) {
			$this->json(['error' => 'Offer not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && (!isset($user['id']) || $existing['user_id'] !== $user['id'])) {
			$this->json(['error' => 'Not authorized to delete this offer'], 403);
		}

		$hard = isset($_POST['hard']) && ($_POST['hard'] === '1' || $_POST['hard'] === 1 || $_POST['hard'] === true);
		if ($hard && $isAdmin) {
			$removed = $this->offerModel->delete($id);
			if (!$removed) {
				$this->json(['error' => 'Could not delete offer'], 500);
			}
			$this->json(['success' => true]);
		}

		$ok = $this->offerModel->soft_delete($id);
		if (!$ok) {
			$this->json(['error' => 'Could not soft-delete offer'], 500);
		}

		$this->json(['success' => true]);
	}

	/**
	 * Render current user's offers (profile/offers).
	 */
	public function myOffers()
	{
		$user = $this->requireAuth();
		$offers = $this->offerModel->getOffersByUser($user['id']);
		$this->render('profile/offers', ['offers' => $offers]);
	}

	/**
	 * Edit an offer (owner or admin). GET renders form, POST applies update and redirects to profile/offers.
	 */
	public function edit(string $id)
	{
		$user = $this->requireAuth();

		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Offer not found'], 404);
		}

		$offer = $this->offerModel->findById($id);
		if (!$offer) {
			$this->json(['error' => 'Offer not found'], 404);
		}

		$isAdmin = !empty($user['is_admin']) || !empty($_SESSION['is_admin']);
		if (!$isAdmin && (!isset($user['id']) || $offer['user_id'] !== $user['id'])) {
			$this->json(['error' => 'Not authorized to edit this offer'], 403);
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->render('offers/edit', ['offer' => $offer]);
			return;
		}

		$data = [];
		$title = trim($_POST['title'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$availability = trim($_POST['availability'] ?? '');
		if ($title !== '') $data['title'] = $title;
		if ($description !== '') $data['description'] = $description;
		if ($availability !== '') $data['availability'] = $availability;

		if (empty($data)) {
			$this->render('offers/edit', ['offer' => $offer, 'errors' => ['No update data provided']]);
			return;
		}

		$ok = $this->offerModel->update($id, $data);
		if (!$ok) {
			$this->render('offers/edit', ['offer' => $offer, 'errors' => ['Failed to update offer']]);
			return;
		}

		$this->redirect('profile/offers');
	}

	/**
	 * Delete an offer by id (owner or admin). Accepts POST and redirects to profile/offers.
	 */
	public function deleteById(string $id)
	{
		$user = $this->requireAuth();

		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Offer not found'], 404);
		}

		$offer = $this->offerModel->findById($id);
		if (!$offer) {
			$this->json(['error' => 'Offer not found'], 404);
		}

		$isAdmin = !empty($user['is_admin']) || !empty($_SESSION['is_admin']);
		if (!$isAdmin && $offer['user_id'] !== $user['id']) {
			$this->json(['error' => 'Not authorized to delete this offer'], 403);
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->json(['error' => 'POST method required to delete offer'], 405);
		}

		$ok = $this->offerModel->delete($id);
		if (!$ok) $this->json(['error' => 'Could not delete offer'], 500);

		$this->redirect('profile/offers');
	}
}
