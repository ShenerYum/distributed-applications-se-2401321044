<?php

use App\Core\Router;

use App\Middleware\{
	AuthMiddleware,
	AdminMiddleware
};

use App\Controllers\Api\SkillController;

// Define middleware groups for convenience
$auth = [AuthMiddleware::class];
$admin = [AuthMiddleware::class, AdminMiddleware::class];

/** @var Router $router */

// Skills API
$router->get('/api/skills', [SkillController::class, 'list']);
$router->post('/api/skills', [SkillController::class, 'create'], $admin);
$router->put('/api/skills/{skillId}', [SkillController::class, 'update'], $admin);
$router->delete('/api/skills/{skillId}', [SkillController::class, 'delete'], $admin);
