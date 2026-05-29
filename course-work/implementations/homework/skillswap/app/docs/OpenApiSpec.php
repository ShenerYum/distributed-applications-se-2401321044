<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
	title: "SkillSwap API",
	version: "1.0.0",
	description: "API specification for the SkillSwap application"
)]
#[OA\Server(
	url: "https://skillswap"
)]
class OpenApiSpec {}
