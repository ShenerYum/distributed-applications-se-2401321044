<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/core/Model.php';
require_once __ROOT__ . '/app/models/ReviewModel.php';

/**
 * RatingService: encapsulates user rating recalculation logic.
 */
class RatingService
{
	/** @var ReviewModel */
	private $reviewModel;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
		$this->reviewModel = new ReviewModel();
	}

	/**
	 * Recalculate and persist the aggregate rating for a user.
	 * @param string $userId
	 * @return float
	 */
	public function recalcUserRating(string $userId): float
	{
		return $this->reviewModel->recalcUserRating($userId);
	}
}
