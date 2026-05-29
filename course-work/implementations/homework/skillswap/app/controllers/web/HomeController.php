<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\WebController;

class HomeController extends WebController
{
	public function index()
	{
		return $this->render('home');
	}
}
