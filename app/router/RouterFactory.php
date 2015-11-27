<?php

namespace Router;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList();
		$router[] = new Route('[home]', 'Front:Pub:default');
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Pub:default');
		return $router;
	}

}
