<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * RequestController handles requests browsing, creation and management (HTML + API).
 */
class RequestController extends Controller
{
	/** @var RequestModel */
	private $requestModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
		$this->requestModel = $this->loadModel('RequestModel');
	}

	/**
	 * API: list requests (JSON) with optional filtering.
	 */
	public function list()
	{
		$skill = isset($_GET['skill_id']) ? trim($_GET['skill_id']) : (isset($_GET['skill']) ? trim($_GET['skill']) : null);
		$availability = isset($_GET['availability']) ? trim($_GET['availability']) : null;

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		if ($skill !== null || $availability !== null) {
			$requests = $this->requestModel->filterRequests($skill, $availability, $limit, $offset);
		} else {
			$requests = $this->requestModel->getAllRequests($limit, $offset);
		}

		$this->json(['success' => true, 'data' => $requests]);
	}

	/**
	 * Render browse requests page (HTML) or return JSON list.
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
			$offerModel = $this->loadModel('OfferModel');
			$userOffers = $offerModel->getOffersByUser($user['id']);
			if (!empty($userOffers)) {
				$skillIds = array_values(array_unique(array_filter(array_column($userOffers, 'skill_id'))));

				$requests = !empty($skillIds) ? $this->requestModel->getRequestsBySkillIds($skillIds, $limit, $offset) : [];
			} else {
				$needs_skill = true;
				$requests = [];
			}
		} else {
			if ($skill !== null || $availability !== null) {
				$requests = $this->requestModel->filterRequests($skill, $availability, $limit, $offset);
			} else {
				$requests = $this->requestModel->getAllRequests($limit, $offset);
			}
		}

		if ($isApi) $this->json(['success' => true, 'data' => $requests]);

		$this->render('requests/index', ['requests' => $requests, 'needs_skill' => $needs_skill]);
	}

	/**
	 * Render create request form (GET) and handle creation (POST).
	 * Inputs: title, desired_level (mapped to availability), notes (mapped to description), max_hours
	 */
	public function create()
	{
		$user = $this->requireAuth();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$skillId = $_GET['skill'] ?? $_GET['skill_id'] ?? null;
			$this->render('requests/create', ['skill_id' => $skillId]);
			return;
		}

		$skillId = trim($_POST['skill_id'] ?? $_POST['skill'] ?? '');
		$title = trim($_POST['title'] ?? '');
		$desiredLevel = trim($_POST['desired_level'] ?? '');
		$notes = trim($_POST['notes'] ?? '');
		$maxHours = trim($_POST['max_hours'] ?? '');

		try {
			$payload = [
				'skill_id' => $skillId,
				'desired_level' => $desiredLevel,
				'title' => $title,
				'notes' => $notes,
				'max_hours' => $maxHours,
				'user_id' => $user['id'] ?? null,
			];

			$request = $this->requestModel->createRequest($payload);

			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
				$this->json(['success' => true, 'data' => $request], 201);
			}

			$this->redirect('profile/requests');
		} catch (Exception $e) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
				$this->json(['error' => $e->getMessage()], 400);
			}

			$this->render('requests/create', ['errors' => [$e->getMessage()], 'skill_id' => $skillId]);
		}
	}

	/**
	 * API: update an existing request (JSON).
	 */
	public function update()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Valid request UUID is required'], 422);
		}

		$existing = $this->requestModel->findById($id);
		if (!$existing) {
			$this->json(['error' => 'Request not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && (!isset($user['id']) || $existing['user_id'] !== $user['id'])) {
			$this->json(['error' => 'Not authorized to update this request'], 403);
		}

		$data = [];
		if (isset($_POST['skill_id']) || isset($_POST['skill'])) {
			$data['skill_id'] = trim($_POST['skill_id'] ?? $_POST['skill']);
		}

		if (isset($_POST['desired_level']) || isset($_POST['desired_level'])) {
			$data['desired_level'] = trim($_POST['desired_level'] ?? $_POST['desired_level']);
		}

		if (isset($_POST['max_hours'])) {
			$data['max_hours'] = trim($_POST['max_hours']);
		}

		if (isset($_POST['title'])) {
			$data['title'] = trim($_POST['title']);
		}

		if (isset($_POST['notes']) || isset($_POST['notes'])) {
			$data['notes'] = trim($_POST['notes'] ?? $_POST['notes']);
		}

		if (empty($data)) {
			$this->json(['error' => 'No update data provided'], 422);
		}

		try {
			$ok = $this->requestModel->update($id, $data);
			if (!$ok) {
				$this->json(['error' => 'Failed to update request'], 500);
			}
			$updated = $this->requestModel->findById($id);
			$this->json(['success' => true, 'data' => $updated]);
		} catch (Exception $e) {
			$this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * API: delete a request (JSON POST id).
	 */
	public function delete()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Valid request UUID is required'], 422);
		}

		$existing = $this->requestModel->findById($id);
		if (!$existing) {
			$this->json(['error' => 'Request not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && (!isset($user['id']) || $existing['user_id'] !== $user['id'])) {
			$this->json(['error' => 'Not authorized to delete this request'], 403);
		}

		$hard = isset($_POST['hard']) && ($_POST['hard'] === '1' || $_POST['hard'] === 1 || $_POST['hard'] === true);
		if ($hard && $isAdmin) {
			$removed = $this->requestModel->delete($id);
			if (!$removed) {
				$this->json(['error' => 'Could not delete request'], 500);
			}
			$this->json(['success' => true]);
		}

		$ok = $this->requestModel->soft_delete($id);
		if (!$ok) {
			$this->json(['error' => 'Could not soft-delete request'], 500);
		}

		$this->json(['success' => true]);
	}

	/**
	 * Render current user's requests (profile/requests).
	 */
	public function myRequests()
	{
		$user = $this->requireAuth();
		$requests = $this->requestModel->getRequestsByUser($user['id']);
		$this->render('profile/requests', ['requests' => $requests]);
	}

	/**
	 * Edit a request (owner or admin). GET renders form, POST applies update and redirects to profile/requests.
	 */
	public function edit(string $id)
	{
		$user = $this->requireAuth();

		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Request not found'], 404);
		}

		$request = $this->requestModel->findById($id);
		if (!$request) {
			$this->json(['error' => 'Request not found'], 404);
		}

		$isAdmin = !empty($user['is_admin']) || !empty($_SESSION['is_admin']);
		if (!$isAdmin && (!isset($user['id']) || $request['user_id'] !== $user['id'])) {
			$this->json(['error' => 'Not authorized to edit this request'], 403);
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->render('requests/edit', ['request' => $request]);
			return;
		}

		$data = [];
		$title = trim($_POST['title'] ?? '');
		$desiredLevel = trim($_POST['desired_level'] ?? '');
		$notes = trim($_POST['notes'] ?? '');
		$maxHours = trim($_POST['max_hours'] ?? '');
		if ($title !== '') $data['title'] = $title;
		if ($desiredLevel !== '') $data['desired_level'] = $desiredLevel;
		if ($notes !== '') $data['notes'] = $notes;
		if ($maxHours !== '') $data['max_hours'] = $maxHours;

		if (empty($data)) {
			$this->render('requests/edit', ['request' => $request, 'errors' => ['No update data provided']]);
			return;
		}

		$ok = $this->requestModel->update($id, $data);
		if (!$ok) {
			$this->render('requests/edit', ['request' => $request, 'errors' => ['Failed to update request']]);
			return;
		}

		$this->redirect('profile/requests');
	}

	/**
	 * Delete a request by id (owner or admin). Accepts POST and redirects to profile/requests.
	 */
	public function deleteById(string $id)
	{
		$user = $this->requireAuth();

		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Request not found'], 404);
		}

		$request = $this->requestModel->findById($id);
		if (!$request) {
			$this->json(['error' => 'Request not found'], 404);
		}

		$isAdmin = !empty($user['is_admin']) || !empty($_SESSION['is_admin']);
		if (!$isAdmin && $request['user_id'] !== $user['id']) {
			$this->json(['error' => 'Not authorized to delete this request'], 403);
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->json(['error' => 'Method not allowed'], 405);
		}

		$ok = $this->requestModel->delete($id);
		if (!$ok) $this->json(['error' => 'Could not delete request'], 500);

		$this->redirect('profile/requests');
	}
}
