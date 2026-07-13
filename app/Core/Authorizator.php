<?php

/**
 * @author Kryštof Tulinger
 *
 */

namespace App\Core;

use Nette\Security\Permission;

class AuthorizatorFactory
{
	/**
	 * Canonical resource names - reference these instead of the raw string
	 * literals when adding new isAllowed('frontend'|'backend') checks.
	 * Existing call sites (15+ presenters/forms) intentionally left as
	 * literals rather than mass-replaced, to avoid risking a typo in a
	 * security-relevant check for a purely cosmetic change.
	 */
	public const RESOURCE_FRONTEND = "frontend";
	public const RESOURCE_BACKEND = "backend";

	public static function generateGroups($index = 0)
	{
		$groups = array();
		$groups [] = "user";
		$groups [] = "admin";
		return $groups;
	}

	public function createAuthorizator(): Permission
	{
		$p = new Permission();

		$p->addResource(self::RESOURCE_FRONTEND);
		$p->addResource(self::RESOURCE_BACKEND);

		$p->addRole("guest");
		$p->addRole("authenticated");

		$p->addRole("user", "guest");

		$p->allow("user", self::RESOURCE_FRONTEND);

		$p->addRole("admin", "user");

		$p->allow("admin");

		return $p;
	}

}
