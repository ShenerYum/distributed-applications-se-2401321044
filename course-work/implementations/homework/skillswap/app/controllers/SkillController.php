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

		$this->json(['success' => true, 'data' => $skills]);
	}

	/**
	 * Render skills index view for HTML clients, or return JSON for API.
	 */
	public function index()
	{
		$isApi = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
		if (!empty($_GET['name'])) {
			$skills = $this->skillModel->findBy(['name' => trim($_GET['name'])], $limit, $offset);
		} else {
			$skills = $this->skillModel->findAll($limit, $offset);
		}

		if ($isApi) $this->json(['success' => true, 'data' => $skills]);

		$this->render('skills/index', ['skills' => $skills]);
	}

	/**
	 * POST create a new skill. Admin only.
	 * 
	 * @return array JSON response containing the created skill or an error message.
	 */
	public function create()
	{
		$this->requireAdmin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->render('skills/create');
			return;
		}

		$input = $_POST;
		$name = trim($input['name'] ?? '');
		$description = trim($input['description'] ?? '');
		$category = trim($input['category'] ?? '');

		if ($name === '') {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
				$this->json(['error' => 'Skill name is required'], 422);
			}

			$this->render('skills/create', ['errors' => ['Skill name is required']]);
			return;
		}

		try {
			$skill = $this->skillModel->createSkill([
				'name' => $name,
				'description' => $description,
				'category' => $category,
			]);
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
				$this->json(['success' => true, 'data' => $skill], 201);
			}
			$this->redirect('skills');
		} catch (Exception $e) {
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
				$this->json(['error' => $e->getMessage()], 400);
			}
			$this->render('skills/create', ['errors' => [$e->getMessage()]]);
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
			$this->json(['error' => 'Valid skill UUID is required'], 422);
		}

		$data = [];
		if (isset($_POST['name'])) {
			$data['name'] = trim($_POST['name']);
		}
		if (isset($_POST['description'])) {
			$data['description'] = trim($_POST['description']);
		}
		if (isset($_POST['category'])) {
			$data['category'] = trim($_POST['category']);
		}

		if (empty($data)) {
			$this->json(['error' => 'No update data provided'], 422);
		}

		try {
			$updated = $this->skillModel->updateSkill($id, $data);
			if ($updated === false) {
				$this->json(['error' => 'Failed to update skill'], 500);
			}
			$this->json(['success' => true, 'data' => $updated]);
		} catch (Exception $e) {
			$this->json(['error' => $e->getMessage()], 400);
		}
	}

	/**
	 * DELETE delete a skill by id. Admin only.
	 * 
	 * @return array JSON response indicating success or an error message.
	 */
	public function delete(string $id)
	{
		// Support admin-only HTML delete via skills/{id}/delete (POST) or API POST id
		$this->requireAdmin();

		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Valid skill UUID is required'], 422);
		}

		$removed = $this->skillModel->deleteSkill($id);
		if (!$removed) {
			$this->json(['error' => 'Could not delete skill'], 500);
		}

		if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
			$this->json(['success' => true]);
		}

		$this->redirect('skills');
	}

	/**
	 * Edit skill (admin only): GET renders edit form, POST applies update and redirects.
	 */
	public function edit(string $id)
	{
		$this->requireAdmin();

		if (!$this->isValidUUID($id)) {
			$this->json(['error' => 'Valid skill UUID is required'], 422);
		}

		$skill = $this->skillModel->findById($id);
		if (!$skill) {
			$this->json(['error' => 'Skill not found'], 404);
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->render('skills/edit', ['skill' => $skill]);
			return;
		}

		$data = [];
		$name = trim($_POST['name'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$category = trim($_POST['category'] ?? '');
		if ($name !== '') $data['name'] = $name;
		if ($description !== '') $data['description'] = $description;
		if ($category !== '') $data['category'] = $category;

		if (empty($data)) {
			$this->render('skills/edit', ['skill' => $skill, 'errors' => ['No update data provided']]);
			return;
		}

		$ok = $this->skillModel->updateSkill($id, $data);
		if (!$ok) {
			$this->render('skills/edit', ['skill' => $skill, 'errors' => ['Failed to update skill']]);
			return;
		}

		$this->redirect('skills');
	}
}
