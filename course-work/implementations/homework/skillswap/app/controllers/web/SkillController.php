<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Response;
use App\Core\WebController;

use App\Services\AuthService;
use App\Services\SkillService;

/**
 * SkillController handles HTML rendering for skill operations.
 */
class SkillController extends WebController
{
	public function __construct(
		private AuthService $authService,
		private SkillService $skillService
	) {
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	}

	public function index(): Response
	{
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
		$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
		$filters = [
			'name'     => !empty($_GET['name']) ? trim($_GET['name']) : null,
			'category' => !empty($_GET['category']) ? trim($_GET['category']) : null
		];

		try {
			$isAdmin = $this->authService->getCurrentUser()['is_admin'] ?? false;
			$filters['is_active'] = $isAdmin ? null : 1;

			$skills = $this->skillService->listSkills($filters, $limit, $offset);

			return $this->render('skills/index', ['skills' => $skills] + ['limit' => $limit, 'offset' => $offset, 'filters' => $filters]);
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}


	public function createPage(): Response
	{
		return $this->render('skills/create');
	}

	public function create(): Response
	{
		$input = $_POST;
		try {
			$this->skillService->createSkill([
				'name' => trim($input['name'] ?? ''),
				'category' => trim($input['category'] ?? ''),
				'description' => $input['description'] ?? '',
				'difficulty' => trim($input['difficulty'] ?? ''),
				'is_active' => trim($input['is_active'] ?? '')
			]);

			return $this->redirect('skills');
		} catch (\Exception $e) {
			return $this->retry('skills/create', $e);
		}
	}

	public function editPage(string $id): Response
	{
		try {
			$this->skillService->validateUUID($id);

			$skill = $this->skillService->getSkill($id);
		} catch (\InvalidArgumentException $e) {
			return $this->fail($e);
		}

		return $this->render('skills/edit', ['skill' => $skill]);
	}

	/**
	 * Edit skill (admin only): GET renders edit form, POST applies update and redirects.
	 */
	public function edit(string $id): Response
	{
		try {
			$this->skillService->validateUUID($id);

			$skill = $this->skillService->getSkill($id);
		} catch (\InvalidArgumentException $e) {
			return $this->fail($e);
		}

		$input = $_POST;
		try {
			$this->skillService->updateSkill($id, [
				'name' => trim($input['name'] ?? ''),
				'category' => trim($input['category'] ?? ''),
				'description' => $input['description'] ?? '',
				'difficulty' => trim($input['difficulty'] ?? ''),
				'is_active' => trim($input['is_active'] ?? '')
			]);

			return $this->redirect('skills');
		} catch (\Exception $e) {
			return $this->retry('skills/edit', $e, ['skill' => $skill]);
		}
	}

	public function delete(string $id): Response
	{
		try {
			$this->skillService->validateUUID($id);
			$this->skillService->deleteSkill($id);

			return $this->redirect('skills');
		} catch (\Exception $e) {
			return $this->fail($e);
		}
	}
}
