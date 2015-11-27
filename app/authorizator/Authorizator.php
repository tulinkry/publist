<?php
/**
 * @author Kryštof Tulinger
 *
 */

namespace Authorizator;

use Nette\Security\Permission;

class AuthorizatorFactory
{

	static public function generateGroups ( $index = 0 )
	{
		$groups = array ();
		$groups [] = "user";
		$groups [] = "admin";
		return $groups;
	}

	/**
     * @return \Nette\Security\IAuthorizator
     */
	public function createAuthorizator ()
	{
		$p = new Permission ();

		$p -> addResource ( "frontend" );
		$p -> addResource ( "backend" );

		$p -> addRole ( "guest" );
		$p -> addRole ( "authenticated" );
		
		$p -> addRole ( "user", "guest" );

		$p -> allow ( "user", "frontend" );		

		$p -> addRole ( "admin", "user" );

		$p -> allow ( "admin" );

		return $p;
	}

}

