<?php
/**
 * @author Kryštof Tulinger
 *
 */

namespace Authenticator;

use Nette;
use Nette\Security as NS;
use Kdyby\Doctrine\EntityManager;
use Entity\User;

/**
 * Users authenticator.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class Authenticator extends Nette\Object implements NS\IAuthenticator
{
	/** @var Kdyby\Doctrine\EntityDao */
	private $users;

	/** @var Kdyby\Doctrine\EntityManager */
	protected $em;

	public function __construct( EntityManager $em )
	{
		$this->users = $em -> getDao ( "\Entity\User" );
		$this->em = $em;
	}



	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($email, $password) = $credentials;
		$user = $this->users->findOneBy(array('email' => $email));

		if (!$user) {
			throw new NS\AuthenticationException("User '$email' not found.", self::IDENTITY_NOT_FOUND);
		}

		if ($user->password !== $this->calculateHash($password, $email)) {
			throw new NS\AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}
		$this -> em -> detach ( $user );
		return new NS\Identity($user->getId(), [ $user->right ], array ( "userClass" => $user ) );
	}



	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	static public function calculateHash($password, $email)
	{
		return hash( "sha512", $password . $email. str_repeat('123', 10) );
	}

}
