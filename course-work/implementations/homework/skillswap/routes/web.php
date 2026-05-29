<?php


use App\Core\Router;

use App\Controllers\Web\{
	HomeController,
	AuthController,
	ProfileController,
	SkillController,
	UserController,
	OfferController,
	RequestController,
	ReviewController,
	MatchController
};

use App\Middleware\{
	AuthMiddleware,
	AdminMiddleware
};

// Define middleware groups for convenience
$auth = [AuthMiddleware::class];
$admin = [AuthMiddleware::class, AdminMiddleware::class];

/** @var Router $router */

// Home
$router->get('/', [HomeController::class, 'index']);

// Authentication
$router->get('/login', [AuthController::class, 'loginPage']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout'], $auth);

$router->get('/register', [AuthController::class, 'registerPage']);
$router->post('/register', [AuthController::class, 'register']);

// Profile
$router->get('/profile', [ProfileController::class, 'index'], $auth);

$router->post('/profile/delete', [ProfileController::class, 'delete'], $auth);

$router->get('/profile/edit', [ProfileController::class, 'editPage'], $auth);
$router->post('/profile/edit', [ProfileController::class, 'edit'], $auth);

$router->get('/profile/offers', [OfferController::class, 'myOffers'], $auth);
$router->get('/profile/requests', [RequestController::class, 'myRequests'], $auth);
$router->get('/profile/matches', [MatchController::class, 'myMatches'], $auth);

$router->get('/profile/reviews', [ReviewController::class, 'myReviews'], $auth);

// Users
$router->get('/users', [UserController::class, 'index'], $auth);

$router->get('/users/{id}', [UserController::class, 'userPage'], $auth);

$router->get('/users/{id}/edit', [UserController::class, 'editPage'], $admin);
$router->post('/users/{id}/edit', [UserController::class, 'edit'], $admin);

$router->post('/users/{id}/delete', [UserController::class, 'delete'], $admin);

$router->get('/users/{id}/offers', [OfferController::class, 'userOffers'], $auth);
$router->get('/users/{id}/requests', [RequestController::class, 'userRequests'], $auth);
$router->get('/users/{id}/reviews', [ReviewController::class, 'userReviews'], $auth);

// Skills
$router->get('/skills', [SkillController::class, 'index'], $auth);

$router->get('/skills/create', [SkillController::class, 'createPage'], $auth);
$router->post('/skills/create', [SkillController::class, 'create'], $auth);

$router->get('/skills/{skillId}/edit', [SkillController::class, 'editPage'], $admin);
$router->post('/skills/{skillId}/edit', [SkillController::class, 'edit'], $admin);

$router->post('/skills/{skillId}/delete', [SkillController::class, 'delete'], $admin);

// Offers
$router->get('/offers', [OfferController::class, 'index'], $auth);

$router->get('/offers/create', [OfferController::class, 'createPage'], $auth);
$router->post('/offers/create', [OfferController::class, 'create'], $auth);

$router->get('/offers/{offerId}/edit', [OfferController::class, 'editPage'], $auth);
$router->post('/offers/{offerId}/edit', [OfferController::class, 'edit'], $auth);

$router->post('/offers/{offerId}/delete', [OfferController::class, 'delete'], $auth);

// Requests
$router->get('/requests', [RequestController::class, 'index'], $auth);

$router->get('/requests/create', [RequestController::class, 'createPage'], $auth);
$router->post('/requests/create', [RequestController::class, 'create'], $auth);

$router->get('/requests/{requestId}/edit', [RequestController::class, 'editPage'], $auth);
$router->post('/requests/{requestId}/edit', [RequestController::class, 'edit'], $auth);

$router->post('/requests/{requestId}/delete', [RequestController::class, 'delete'], $auth);

// Matches
$router->get('/matches', [MatchController::class, 'index'], $admin);

$router->get('/matches/create', [MatchController::class, 'matchPage'], $auth);
$router->post('/matches/create', [MatchController::class, 'match'], $auth);

$router->post('/matches/{matchId}/accept', [MatchController::class, 'accept'], $auth);
$router->post('/matches/{matchId}/reject', [MatchController::class, 'reject'], $auth);
$router->post('/matches/{matchId}/complete', [MatchController::class, 'complete'], $auth);
$router->post('/matches/{matchId}/delete', [MatchController::class, 'delete'], $auth);

// Reviews
$router->get('/reviews', [ReviewController::class, 'index'], $admin);

$router->get('/reviews/create', [ReviewController::class, 'createPage'], $auth);
$router->post('/reviews/create', [ReviewController::class, 'create'], $auth);

$router->get('/reviews/{id}/edit', [ReviewController::class, 'editPage'], $auth);
$router->post('/reviews/{id}/edit', [ReviewController::class, 'edit'], $auth);

$router->post('/reviews/{id}/delete', [ReviewController::class, 'delete'], $auth);
