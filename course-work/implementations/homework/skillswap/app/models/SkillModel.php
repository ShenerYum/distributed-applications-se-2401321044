<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Model.php';

/**
 * SkillModel is responsible for handling skill-related operations such as creating, updating, deleting, and listing skills.
 */
class SkillModel extends Model
{
	public function __construct()
	{
		parent::__construct();
		$this->setTable('Skills');
		$this->setPrimaryKey('id');
	}

	/**
	 * Create a new skill.
	 * 
	 * @param array $data ['name' => string, 'description' => string|null, ...]
	 * 
	 * @return array Created skill record
	 * 
	 * @throws InvalidArgumentException if required fields are missing or invalid.
	 */
	public function createSkill(array $data): array
	{
		$name = trim($data['name'] ?? '');
		if ($name === '') {
			throw new InvalidArgumentException('Skill name is required');
		}

		$payload = [
			'name' => $name,
			'description' => $data['description'] ?? null,
		];

		foreach ($data as $k => $v) {
			if (!array_key_exists($k, $payload)) {
				$payload[$k] = $v;
			}
		}

		$id = $this->create($payload);
		$skill = $this->findById($id);
		return $skill ?: [];
	}

	/**
	 * Get skill by id.
	 * 
	 * @param string $id
	 * @return array|null
	 */
	public function getSkillById(string $id): ?array
	{
		return $this->findById($id);
	}

	/**
	 * Update a skill by id.
	 * 
	 * @param string $id
	 * @param array $data
	 * 
	 * @return array|false Updated record or false on failure
	 * 
	 * @throws InvalidArgumentException if $data is empty.
	 */
	public function updateSkill(string $id, array $data)
	{
		if (empty($data)) {
			throw new InvalidArgumentException('No data provided for update');
		}

		$ok = $this->update($id, $data);
		if ($ok) {
			return $this->findById($id);
		}
		return false;
	}

	/**
	 * Delete a skill by id.
	 * 
	 * @param string $id
	 * @return bool
	 */
	public function deleteSkill(string $id): bool
	{
		return $this->delete($id);
	}

	/**
	 * List skills with optional filters and pagination.
	 * 
	 * @param array $filters (column => value)
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function listSkills(array $filters = [], int $limit = 20, int $offset = 0): array
	{
		if (!empty($filters)) {
			return $this->findBy($filters, $limit, $offset);
		}

		return $this->findAll($limit, $offset);
	}

	/**
	 * Search skills by name (partial match).
	 * 
	 * @param string $term
	 * @param int $limit
	 * @return array
	 */
	public function searchByName(string $term, int $limit = 20): array
	{
		$sql = 'SELECT * FROM ' . $this->quoteIdentifier($this->table) . ' WHERE ' . $this->quoteIdentifier('name') . ' LIKE :term LIMIT :limit';
		$stmt = $this->db->prepare($sql);
		$like = '%' . $term . '%';

		$stmt->bindValue(':term', $like, PDO::PARAM_STR);
		$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}
}
