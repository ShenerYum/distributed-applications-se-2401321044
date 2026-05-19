<?php

class Database
{
	private static $instance = null;
	private $pdo;

	private function __construct(array $config)
	{
		if (!isset($config['db']) || !is_array($config['db'])) return;

		$hostRaw = $config['host'] ?? '127.0.0.1';
		$dbName = $config['db']['database'] ?? '';
		$user = $config['db']['username'] ?? 'root';
		$pass = $config['db']['password'] ?? '';
		$charset = $config['db']['charset'] ?? 'utf8mb4';

		// Parse host and optional port
		$host = $hostRaw;
		$port = null;
		if (strpos($hostRaw, ':') !== false) {
			list($host, $port) = explode(':', $hostRaw, 2);
			$host = trim($host);
			$port = (int) trim($port);
		}

		$dsn = "mysql:host={$host};";
		if (!empty($port)) {
			$dsn .= "port={$port};";
		}
		$dsn .= "dbname={$dbName};charset={$charset}";

		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		];

		try {
			$this->pdo = new PDO($dsn, $user, $pass, $options);
		} catch (PDOException $e) {
			// TODO: 500
			echo 'Couldn\'t connect to the database: ' . $e->getMessage();
		}
	}

	public static function getInstance(array $config = [])
	{
		if (self::$instance === null) {
			if (empty($config)) {
				$cfgFile = __DIR__ . '/../config/database.php';

				if (file_exists($cfgFile)) $config = require $cfgFile;
			}

			if (empty($config)) {
				throw new RuntimeException('Database configuration required for first initialization');
			}

			self::$instance = new self($config);
		}

		return self::$instance;
	}

	public function getConnection()
	{
		return $this->pdo;
	}
}
