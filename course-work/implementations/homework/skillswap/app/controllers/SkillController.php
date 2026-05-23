<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Controller.php';

/**
 * SkillController is responsible for handling skill-related operations such as creating,
 * updating, deleting, and listing skills. Admin privileges are required for creating, updating,
 * and deleting skills.
 */
class SkillController extends Controller
{
	/**
	 * The SkillModel instance for handling skill-related database operations.
	 * @var SkillModel
	 */
	private $skillModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$this->skillModel = $this->loadModel('SkillModel');
	}


	private function requireAdmin()
	{
		$user = $this->requireAuth();
		$isAdmin = false;

		if (isset($user['is_admin']) && $user['is_admin']) {
			$isAdmin = true;
		}

		if (isset($user['role']) && strtolower($user['role']) === 'admin') {
			$isAdmin = true;
		}

		if (!$isAdmin) {
			$this->json(['error' => 'Admin privileges required'], 403);
		}

		return true;
	}

	/**
	 * GET list skills with optional filtering by name and pagination.
	 * 
	 * @return array JSON response containing the list of skills or an error message.
	 */
	public function list()
	{
		$filters = [];
		if (!empty($_GET['name'])) {
			$filters['name'] = trim($_GET['name']);
		}

		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

		if (!empty($filters)) {
			$skills = $this->skillModel->findBy($filters, $limit, $offset);
		} else {
			$skills = $this->skillModel->findAll($limit, $offset);
		}

		return $this->json(['success' => true, 'data' => $skills]);
	}

	/**
	 * POST create a new skill. Admin only.
	 * 
	 * @return array JSON response containing the created skill or an error message.
	 */
	public function create()
	{
		$this->requireAdmin();

		$input = $_POST;
		$name = trim($input['name'] ?? '');
		$description = trim($input['description'] ?? '');

		if ($name === '') {
			return $this->json(['error' => 'Skill name is required'], 422);
		}

		try {
			$skill = $this->skillModel->createSkill([
				'name' => $name,
				'description' => $description,
			]);
			return $this->json(['success' => true, 'data' => $skill], 201);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * PUT update an existing skill. Admin only. Requires skill id and at least one of name or description.
	 * 
	 * @return array JSON response containing the updated skill or an error message.
	 */
	public function update()
	{
		$this->requireAdmin();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid skill UUID is required'], 422);
		}

		$data = [];
		if (isset($_POST['name'])) {
			$data['name'] = trim($_POST['name']);
		}
		if (isset($_POST['description'])) {
			$data['description'] = trim($_POST['description']);
		}

		if (empty($data)) {
			return $this->json(['error' => 'No update data provided'], 422);
		}

		try {
			$updated = $this->skillModel->updateSkill($id, $data);
			if ($updated === false) {
				return $this->json(['error' => 'Failed to update skill'], 500);
			}
			return $this->json(['success' => true, 'data' => $updated]);
		} catch (Exception $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * DELETE delete a skill by id. Admin only.
	 * 
	 * @return array JSON response indicating success or an error message.
	 */
	public function delete()
	{
		$this->requireAdmin();

		$id = trim($_POST['id'] ?? '');
		if (!$this->isValidUUID($id)) {
			return $this->json(['error' => 'Valid skill UUID is required'], 422);
		}

		$removed = $this->skillModel->deleteSkill($id);
		if (!$removed) {
			return $this->json(['error' => 'Could not delete skill'], 500);
		}

		return $this->json(['success' => true]);
	}
}
