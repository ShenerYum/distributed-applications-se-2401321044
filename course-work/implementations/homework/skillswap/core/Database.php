<?php

namespace App\Core;

use PDO;


class Database
{
	private PDO $pdo;

	public function __construct(array $config)
	{
		if (empty($config['db'])) {
			throw new \RuntimeException('Database configuration missing');
		}

		$hostRaw  = $config['host'] ?? '127.0.0.1';
		$db       = $config['db']['database'] ?? '';
		$user     = $config['db']['username'] ?? 'root';
		$pass     = $config['db']['password'] ?? '';
		$charset  = $config['db']['charset'] ?? 'utf8mb4';

		$host = $hostRaw;
		$port = null;

		if (strpos($hostRaw, ':') !== false) {
			[$host, $port] = explode(':', $hostRaw, 2);
			$host = trim($host);
			$port = (int) trim($port);
		}

		$dsn = "mysql:host={$host};";

		if ($port) {
			$dsn .= "port={$port};";
		}

		$dsn .= "dbname={$db};charset={$charset}";

		try {
			$this->pdo = new PDO($dsn, $user, $pass, [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
			]);
		} catch (\PDOException $e) {
			throw new \RuntimeException(
				"Database connection failed: " . $e->getMessage(),
				500,
				$e
			);
		}
	}

	/**
	 * Return PDO connection
	 */
	public function pdo(): PDO
	{
		return $this->pdo;
	}
}
