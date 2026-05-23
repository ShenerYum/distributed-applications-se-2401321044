<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__));
}

/**
 * Base Controller class providing common functionality for all controllers.
 * Includes methods for rendering views, loading models, sending JSON responses, and handling authentication.
 */
class Database
{
	/**
	 * Singleton instance of the Database class.
	 * @var Database|null
	 */
	private static $instance = null;

	/**
	 * PDO connection instance. Initialized in the constructor.
	 * @var PDO
	 */
	private $pdo;

	/**
	 * Private constructor to prevent direct instantiation. Use getInstance() instead.
	 * 
	 * @param array $config Database configuration with keys: host, db (array with database, username, password, charset)
	 */
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

	/**
	 * Get the singleton instance of the Database class.
	 * 
	 * @param array $config Optional configuration for the first initialization. Ignored on subsequent calls.
	 * @return Database The singleton instance.
	 */
	public static function getInstance(array $config = [])
	{
		if (self::$instance === null) {
			if (empty($config)) {
				$cfgFile = __ROOT__ . '/config/database.php';
				if (file_exists($cfgFile)) $config = require $cfgFile;
			}

			if (empty($config)) {
				throw new RuntimeException('Database configuration required for first initialization');
			}

			self::$instance = new self($config);
		}

		return self::$instance;
	}

	/**
	 * Get the PDO connection instance.
	 * 
	 * @return PDO The PDO connection.
	 */
	public function getConnection()
	{
		return $this->pdo;
	}
}
