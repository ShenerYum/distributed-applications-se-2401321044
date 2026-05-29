<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;

use App\Models\SkillModel;

class SkillService extends Service
{
	public function __construct(private SkillModel $skills) {}


	public function getSkill(string $id): ?array
	{
		$skill = $this->skills->findById($id);
		if (!$skill) {
			throw new \InvalidArgumentException('Skill not found', 404);
		}

		return $skill;
	}

	public function createSkill(array $data): array
	{
		$this->validateRequiredFields($data, ['name', 'category', 'difficulty']);

		$skill = $this->skills->createSkill($data);
		if (!$skill) {
			throw new \RuntimeException('Failed to create skill', 500);
		}

		return $skill;
	}

	public function updateSkill(string $id, array $data): array
	{
		$this->validateRequiredFields($data, ['name', 'category', 'difficulty']);

		$updated = $this->skills->updateSkill($id, $data);
		if (!$updated) {
			throw new \RuntimeException('Failed to update skill', 500);
		}

		return $updated;
	}

	public function deleteSkill(string $id): void
	{
		if (!$this->skills->deleteSkill($id)) {
			throw new \RuntimeException('Failed to delete skill', 500);
		}
	}

	public function listSkills(array $filters, int $limit = 20, int $offset = 0): array
	{
		if (!empty($filters)) {
			return $this->skills->findBy($filters, $limit, $offset);
		}

		$this->validateFilters($filters, ['name', 'category', 'is_active']);

		return $this->skills->findBy($filters, $limit, $offset);
	}

	public function getSkillsByName(string $skillName, int $limit = 20, int $offset = 0): array
	{
		return $this->skills->findBy(['name' => $skillName], $limit, $offset);
	}
}
