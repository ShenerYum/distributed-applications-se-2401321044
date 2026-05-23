<?php

if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__DIR__, 2));
}

require_once __ROOT__ . '/app/models/OfferModel.php';
require_once __ROOT__ . '/app/models/RequestModel.php';
require_once __ROOT__ . '/app/models/UserModel.php';
require_once __ROOT__ . '/app/models/SkillModel.php';

/**
 * MatchingService is responsible for finding mutual matches between users based on their offers and requests.
 * It computes a score for each match based on availability and user ratings.
 */
class MatchingService
{
	/**
	 * OfferModel instance for accessing offers.
	 * @var OfferModel
	 */
	private $offerModel;

	/**
	 * RequestModel instance for accessing requests.
	 * @var RequestModel
	 */
	private $requestModel;

	/**
	 * UserModel instance for accessing user data (e.g., ratings).
	 * @var UserModel
	 */
	private $userModel;

	public function __construct()
	{
		$this->offerModel = new OfferModel();
		$this->requestModel = new RequestModel();
		$this->userModel = new UserModel();
	}

	/**
	 * Find mutual matches for the given user.
	 * A mutual match means: A offers X and requests Y, B offers Y and requests X.
	 * Returns an array of matches with a computed score.
	 *
	 * @param string $userId
	 * @param int $limit
	 * @return array
	 */
	public function findMutualMatches(string $userId, int $limit = 100): array
	{
		$offersA = $this->offerModel->getOffersByUser($userId);
		$requestsA = $this->requestModel->getRequestsByUser($userId);

		$results = [];

		foreach ($offersA as $offerA) {
			foreach ($requestsA as $requestA) {
				// A offers X (offerA.skill_id) and requests Y (requestA.skill_id)
				$skillX = $offerA['skill_id'] ?? null;
				$skillY = $requestA['skill_id'] ?? null;

				// find users B who offer Y and request X (matching by skill_id)
				$sql = "SELECT o.user_id as user_id, o.id as offer_id, r.id as request_id, o.skill_id as offer_skill_id, r.skill_id as request_skill_id, o.availability as offer_availability, r.availability as request_availability
		FROM `Offers` o
		JOIN `Requests` r ON r.user_id = o.user_id
		WHERE o.skill_id = :skillY AND r.skill_id = :skillX";

				$rows = $this->offerModel->rawQuery($sql, [':skillY' => $skillY, ':skillX' => $skillX]);

				foreach ($rows as $row) {
					if ($row['user_id'] === $userId) {
						continue;
					}

					$score = 50; // base

					if (!empty($row['offer_availability']) && isset($requestA['availability']) && trim($row['offer_availability']) === trim($requestA['availability'])) {
						$score += 20;
					}
					if (!empty($row['request_availability']) && isset($offerA['availability']) && trim($row['request_availability']) === trim($offerA['availability'])) {
						$score += 20;
					}

					$otherUser = $this->userModel->findById($row['user_id']);
					$ratingBonus = 0;
					if ($otherUser && isset($otherUser['rating']) && is_numeric($otherUser['rating'])) {
						$rating = (float)$otherUser['rating'];

						$ratingBonus = (int)round(($rating / 5.0) * 10);
						if ($ratingBonus > 10) $ratingBonus = 10;
					}
					$score += $ratingBonus;

					$match = [
						'user_id' => $row['user_id'],
						'offer_id' => $row['offer_id'],
						'request_id' => $row['request_id'],
						'offer_skill_id' => $row['offer_skill_id'],
						'request_skill_id' => $row['request_skill_id'],
						'offer_availability' => $row['offer_availability'] ?? null,
						'request_availability' => $row['request_availability'] ?? null,
						'score' => $score,
						'matched_against' => [
							'my_offer_id' => $offerA['id'],
							'my_request_id' => $requestA['id'],
						],
					];

					$results[] = $match;
				}
			}
		}

		usort($results, function ($a, $b) {
			return $b['score'] <=> $a['score'];
		});

		if ($limit !== null && count($results) > $limit) {
			$results = array_slice($results, 0, $limit);
		}

		return $results;
	}
}
