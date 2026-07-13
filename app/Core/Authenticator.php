<?php

/**
 * @author Kryštof Tulinger
 *
 */

namespace App\Core;

use Nette;
use Nette\Security as NS;
use App\Model\UserModel;

/**
 * Users authenticator.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class Authenticator implements NS\Authenticator
{
	public function __construct(
		private readonly UserModel $users,
	) {
	}

	/**
	 * Performs an authentication.
	 * $username is actually the email (the login form's field is called
	 * "email" - kept authenticating by email to match existing behavior).
	 *
	 * Passwords stored in the legacy sha512+fixed-salt format (see
	 * calculateHash()) are verified against that format and transparently
	 * rehashed with password_hash() on successful login, so every account
	 * migrates to a real, per-hash-salted algorithm the first time its owner
	 * logs in - no bulk migration/forced reset needed.
	 * @throws NS\AuthenticationException
	 */
	public function authenticate(string $username, string $password): NS\IIdentity
	{
		$user = $this->users->by(['email' => $username])->fetch();

		if (!$user) {
			throw new NS\AuthenticationException("User '$username' not found.", self::IDENTITY_NOT_FOUND);
		}

		$id = $user->id;
		$right = $user->right;
		$isLegacy = self::isLegacyHash($user->password);

		if (!self::verify($password, $user->password, $username)) {
			throw new NS\AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}

		if ($isLegacy) {
			// ActiveRow::update() refetches with raw columns only, which
			// would silently drop the "id" alias Repository adds - fetch
			// id/right above, before update() replaces $user's row data.
			$user->update(['password' => password_hash($password, PASSWORD_DEFAULT)]);
		}

		return new NS\SimpleIdentity($id, [$right]);
	}

	/**
	 * Verifies a plaintext password against a stored hash, transparently
	 * supporting both the legacy sha512 format and password_hash() output -
	 * used anywhere a password needs re-checking after the fact (e.g.
	 * ChangePasswordForm's "confirm your current password" step), not just
	 * at login, since accounts migrate to password_hash() gradually (see
	 * authenticate()) rather than all at once.
	 */
	public static function verify(string $password, string $hash, string $email): bool
	{
		return self::isLegacyHash($hash)
			? hash_equals($hash, self::calculateHash($password, $email))
			: password_verify($password, $hash);
	}

	/**
	 * Legacy hashes are a fixed-length (128 char) hex sha512 digest;
	 * password_hash() output always starts with an algorithm tag like "$2y$"
	 * and is never a bare hex string, so the two formats can't collide.
	 */
	private static function isLegacyHash(string $hash): bool
	{
		return (bool) preg_match('/^[0-9a-f]{128}$/', $hash);
	}

	/**
	 * Legacy salted password hash - kept only to verify not-yet-migrated
	 * accounts (see authenticate()) and in tests. Do not use for new hashes;
	 * use password_hash() instead.
	 */
	public static function calculateHash($password, $email)
	{
		return hash("sha512", $password . $email. str_repeat('123', 10));
	}

}
