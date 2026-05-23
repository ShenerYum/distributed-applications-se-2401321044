<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

require_once __ROOT__ . '/core/Database.php';

/**
 * Base Model class providing common database operations for all models.
 * Child classes should set the $table property and can use the provided CRUD methods.
 */
class Model
{
	/**
	 * Database connection instance. Initialized in the constructor.
	 * @var PDO
	 */
	protected $db;

	/**
	 * Table name for the model. Set in child classes or via setTable().
	 * @var string
	 */
	protected $table = '';

	/**
	 * Primary key column name.
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * Constructor initializes the database connection. The Database instance must be initialized in bootstrap (public/index.php) before creating any Model instances.
	 */
	public function __construct()
	{
		// Database instance must be initialized in bootstrap (public/index.php)
		$this->db = Database::getInstance()->getConnection();
	}

	/**
	 * Set the table name for the model. Can be used in child classes or dynamically.
	 * 
	 * @param string $table The name of the database table associated with this model.
	 */
	public function setTable(string $table)
	{
		$this->table = $table;
	}

	/**
	 * Set the primary key column name for the model.
	 * 
	 * @param string $pk The name of the primary key column.
	 */
	public function setPrimaryKey(string $pk)
	{
		$this->primaryKey = $pk;
	}

	protected function quoteIdentifier(string $identifier): string
	{
		return "`" . str_replace("`", "``", $identifier) . "`";
	}

	protected function generateUUID(): string
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		);
	}

	/**
	 * Create a new record in the database with the provided data. If the primary key is not included in the data, a UUID will be generated for it.
	 * 
	 * @param array $data An associative array of column => value pairs to insert into the database.
	 * @return string The value of the primary key for the newly created record.
	 */
	public function create(array $data): string
	{
		if (empty($this->table)) {
			throw new RuntimeException('Model table not set');
		}

		// Generate UUID for the primary key if not provided
		if (!isset($data[$this->primaryKey])) {
			$data[$this->primaryKey] = $this->generateUUID();
		}

		$cols = array_keys($data);
		$colList = implode(', ', array_map([$this, 'quoteIdentifier'], $cols));
		$placeholders = implode(', ', array_map(function ($c) {
			return ':' . $c;
		}, $cols));

		$sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->quoteIdentifier($this->table), $colList, $placeholders);
		$stmt = $this->db->prepare($sql);

		$bind = [];
		foreach ($data as $k => $v) {
			$bind[':' . $k] = $v;
		}

		$stmt->execute($bind);
		return $data[$this->primaryKey];
	}

	/**
	 * Find a single record by its primary key.
	 * 
	 * @param string $id The value of the primary key to search for.
	 * @return array|null An associative array of the record's data, or null if not found.
	 */
	public function findById(string $id): ?array
	{
		if (empty($this->table)) {
			throw new RuntimeException('Model table not set');
		}

		$sql = sprintf('SELECT * FROM %s WHERE %s = :id LIMIT 1', $this->quoteIdentifier($this->table), $this->quoteIdentifier($this->primaryKey));
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	/**
	 * Find all records in the database.
	 * 
	 * @param ?int $limit The maximum number of records to return.
	 * @param ?int $offset The number of records to skip before starting to return rows.
	 * @return array An array of associative arrays representing the records.
	 */
	public function findAll(?int $limit = null, ?int $offset = null): array
	{
		if (empty($this->table)) {
			throw new RuntimeException('Model table not set');
		}

		$sql = sprintf('SELECT * FROM %s', $this->quoteIdentifier($this->table));
		if ($limit !== null) {
			$sql .= ' LIMIT ' . (int)$limit;
			if ($offset !== null) {
				$sql .= ' OFFSET ' . (int)$offset;
			}
		}

		$stmt = $this->db->query($sql);
		return $stmt->fetchAll();
	}

	/**
	 * Update a record identified by its primary key with the provided data.
	 * 
	 * @param string $id The value of the primary key for the record to update.
	 * @param array $data An associative array of column => value pairs to update in the database.
	 * @return bool True on success, false on failure.
	 */
	public function update(string $id, array $data): bool
	{
		if (empty($this->table)) {
			throw new RuntimeException('Model table not set');
		}

		$cols = array_keys($data);
		$sets = implode(', ', array_map(function ($c) {
			return $this->quoteIdentifier($c) . ' = :' . $c;
		}, $cols));
		$sql = sprintf('UPDATE %s SET %s WHERE %s = :id', $this->quoteIdentifier($this->table), $sets, $this->quoteIdentifier($this->primaryKey));

		$stmt = $this->db->prepare($sql);
		$bind = [':id' => $id];
		foreach ($data as $k => $v) {
			$bind[':' . $k] = $v;
		}

		return $stmt->execute($bind);
	}

	/**
	 * Delete a record identified by its primary key.
	 * 
	 * @param string $id The value of the primary key for the record to delete.
	 * @return bool True on success, false on failure.
	 */
	public function delete(string $id): bool
	{
		if (empty($this->table)) {
			throw new RuntimeException('Model table not set');
		}

		$sql = sprintf('DELETE FROM %s WHERE %s = :id', $this->quoteIdentifier($this->table), $this->quoteIdentifier($this->primaryKey));
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([':id' => $id]);
	}

	/**
	 * Soft delete a record by setting the deleted_at timestamp. The record will only be soft-deleted if it hasn't already been deleted (i.e., deleted_at is NULL).
	 * 
	 * @param string $id The value of the primary key for the record to soft delete.
	 * @return bool True on success, false on failure.
	 */
	public function soft_delete(string $id): bool
	{
		if (empty($this->table)) {
			throw new RuntimeException('Model table not set');
		}

		$sql = sprintf(
			'UPDATE %s SET %s = :ts WHERE %s = :id AND %s IS NULL',
			$this->quoteIdentifier($this->table),
			$this->quoteIdentifier('deleted_at'),
			$this->quoteIdentifier($this->primaryKey),
			$this->quoteIdentifier('deleted_at')
		);
		$stmt = $this->db->prepare($sql);
		$ts = date('Y-m-d H:i:s');
		return $stmt->execute([':ts' => $ts, ':id' => $id]);
	}

	/**
	 * Find records by specified criteria.
	 * 
	 * @param array $criteria An associative array of column => value pairs for the WHERE clause.
	 * @param ?int $limit The maximum number of records to return.
	 * @param ?int $offset The number of records to skip before starting to return rows.
	 * @return array An array of associative arrays representing the records.
	 */
	public function findBy(array $criteria, ?int $limit = null, ?int $offset = null): array
	{
		if (empty($this->table)) {
			throw new RuntimeException('Model table not set');
		}

		$wheres = [];
		$bind = [];
		foreach ($criteria as $k => $v) {
			$placeholder = ':' . $k;
			$wheres[] = $this->quoteIdentifier($k) . ' = ' . $placeholder;
			$bind[$placeholder] = $v;
		}

		$sql = sprintf('SELECT * FROM %s', $this->quoteIdentifier($this->table));
		if (!empty($wheres)) {
			$sql .= ' WHERE ' . implode(' AND ', $wheres);
		}
		if ($limit !== null) {
			$sql .= ' LIMIT ' . (int)$limit;
			if ($offset !== null) {
				$sql .= ' OFFSET ' . (int)$offset;
			}
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($bind);
		return $stmt->fetchAll();
	}

	/**
	 * Execute a raw SQL query with optional parameters and return the results.
	 * 
	 * @param string $sql The raw SQL query to execute.
	 * @param array $params An associative array of parameters to bind to the query (e.g., [':id' => 123]).
	 * @return array An array of associative arrays representing the query results.
	 */
	public function rawQuery(string $sql, array $params = []): array
	{
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	/**
	 * Get the total count of records in the table.
	 * 
	 * @return bool True on success, false on failure.
	 */
	public function beginTransaction(): bool
	{
		return $this->db->beginTransaction();
	}

	/**
	 * Commit the current transaction.
	 * 
	 * @return bool True on success, false on failure.
	 */
	public function commit(): bool
	{
		return $this->db->commit();
	}

	/**
	 * Roll back the current transaction.
	 * 
	 * @return bool True on success, false on failure.
	 */
	public function rollBack(): bool
	{
		return $this->db->rollBack();
	}
}
