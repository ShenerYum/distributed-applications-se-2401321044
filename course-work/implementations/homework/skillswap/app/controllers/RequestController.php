<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * RequestController is responsible for handling request-related operations such as listing requests,
 * creating a new request, updating an existing request, and deleting a request. Authentication is
 * required for creating, updating, and deleting requests. Admin privileges or being the owner of the
 * request is required for updating and deleting requests.
 */
class RequestController extends Controller
{
	/**
	 * The RequestModel instance for handling request-related database operations.
	 * @var RequestModel
	 */
	private $requestModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$this->requestModel = $this->loadModel('RequestModel');
	}

	/**
	 * GET list requests with optional filtering by skill and availability, and pagination.
	 * 
	 * @return array JSON response containing the list of requests or an error message.
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

		return $this->json(['success' => true, 'data' => $requests]);
	}

	/**
	 * POST create a new request. Requires skill_id (or skill name) and availability, and optional title and description.
	 * 
	 * @return array JSON response containing the created request or an error message.
	 */
	public function create()
	{
		$user = $this->requireAuth();

		$input = $_POST;
		try {
			$payload = $input;
			$payload['user_id'] = $user['id'] ?? null;

			$request = $this->requestModel->createRequest($payload);
			return $this->json(['success' => true, 'data' => $request], 201);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * PUT update an existing request. Requires request id and at least one of skill_id, availability, title, or description.
	 * 
	 * @return array JSON response containing the updated request or an error message.
	 */
	public function update()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid request UUID is required'], 422);
		}

		$existing = $this->requestModel->findById($id);
		if (!$existing) {
			return $this->json(['error' => 'Request not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && (!isset($user['id']) || $existing['user_id'] !== $user['id'])) {
			return $this->json(['error' => 'Not authorized to update this request'], 403);
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
			$ok = $this->requestModel->update($id, $data);
			if (!$ok) {
				return $this->json(['error' => 'Failed to update request'], 500);
			}
			$updated = $this->requestModel->findById($id);
			return $this->json(['success' => true, 'data' => $updated]);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * DELETE delete a request by id. Admin or owner only. Supports hard delete with 'hard' parameter for admins.
	 * 
	 * @return array JSON response indicating success or an error message.
	 */
	public function delete()
	{
		$user = $this->requireAuth();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid request UUID is required'], 422);
		}

		$existing = $this->requestModel->findById($id);
		if (!$existing) {
			return $this->json(['error' => 'Request not found'], 404);
		}

		$isAdmin = (isset($user['is_admin']) && $user['is_admin']) || (isset($user['role']) && strtolower($user['role']) === 'admin');
		if (!$isAdmin && (!isset($user['id']) || $existing['user_id'] !== $user['id'])) {
			return $this->json(['error' => 'Not authorized to delete this request'], 403);
		}

		$hard = isset($_POST['hard']) && ($_POST['hard'] === '1' || $_POST['hard'] === 1 || $_POST['hard'] === true);
		if ($hard && $isAdmin) {
			$removed = $this->requestModel->delete($id);
			if (!$removed) {
				return $this->json(['error' => 'Could not delete request'], 500);
			}
			return $this->json(['success' => true]);
		}

		$ok = $this->requestModel->soft_delete($id);
		if (!$ok) {
			return $this->json(['error' => 'Could not soft-delete request'], 500);
		}

		return $this->json(['success' => true]);
	}
}
