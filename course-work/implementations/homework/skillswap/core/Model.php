<?php

require_once __DIR__ . '/Database.php';

class Model
{
	protected $db;

	public function __construct()
	{
		// Database instance must be initialized in bootstrap (public/index.php)
		$this->db = Database::getInstance()->getConnection();
	}
}
