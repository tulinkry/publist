<?php

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\SimpleRouter;

/**
 * Router factory.
 */
class RouterFactory
{
	public static function createRouter(): RouteList
	{
		$router = new RouteList();
		$router->addRoute('[home]', 'Front:Pub:default');
		$router->addRoute('<presenter>/<action>[/<id>]', 'Pub:default');
		return $router;
	}

}
