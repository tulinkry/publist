<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../bootstrap.php';
seedDatabase($container);

// calculateHash() is a pure function, no container/DB needed.

test('hash matches the documented sha512(password . email . repeated salt) formula', function () {
	Assert::same(
		hash('sha512', 'secret' . 'user@example.com' . str_repeat('123', 10)),
		\App\Core\Authenticator::calculateHash('secret', 'user@example.com')
	);
});

test('same password with different emails produces different hashes', function () {
	Assert::notSame(
		\App\Core\Authenticator::calculateHash('secret', 'a@example.com'),
		\App\Core\Authenticator::calculateHash('secret', 'b@example.com')
	);
});

test('different passwords with the same email produce different hashes', function () {
	Assert::notSame(
		\App\Core\Authenticator::calculateHash('secret1', 'user@example.com'),
		\App\Core\Authenticator::calculateHash('secret2', 'user@example.com')
	);
});

test('hashing is deterministic', function () {
	Assert::same(
		\App\Core\Authenticator::calculateHash('secret', 'user@example.com'),
		\App\Core\Authenticator::calculateHash('secret', 'user@example.com')
	);
});

// authenticate() migration behaviour - seedDatabase() creates the user with
// a legacy sha512-format hash (via calculateHash()), matching every real
// pre-migration account.

test('authenticate() accepts a legacy-format hash and transparently upgrades it', function () use ($container) {
	$auth = $container->getByType(\App\Core\Authenticator::class);
	$db = $container->getByType(\Nette\Database\Explorer::class);

	$before = $db->table('users')->get(1)->password;
	Assert::match('~^[0-9a-f]{128}$~', $before, 'seeded password should start out in the legacy sha512 format');

	$identity = $auth->authenticate('test@example.com', 'test1234');
	Assert::same(1, $identity->getId());

	$after = $db->table('users')->get(1)->password;
	Assert::notSame($before, $after, 'password column should have been rehashed after a successful legacy login');
	Assert::true(password_verify('test1234', $after), 'the new hash must verify against the same plaintext password');
});

test('authenticate() accepts the upgraded password_hash() format on a subsequent login', function () use ($container) {
	// Continues from the previous test in the same process: the user's
	// password is now in password_hash() format, not the legacy one.
	$auth = $container->getByType(\App\Core\Authenticator::class);
	$identity = $auth->authenticate('test@example.com', 'test1234');
	Assert::same(1, $identity->getId());
});

test('authenticate() rejects a wrong password in either hash format', function () use ($container) {
	$auth = $container->getByType(\App\Core\Authenticator::class);
	Assert::exception(
		fn () => $auth->authenticate('test@example.com', 'not-the-password'),
		\Nette\Security\AuthenticationException::class
	);
});

test('authenticate() rejects an unknown email', function () use ($container) {
	$auth = $container->getByType(\App\Core\Authenticator::class);
	Assert::exception(
		fn () => $auth->authenticate('nobody@example.com', 'whatever'),
		\Nette\Security\AuthenticationException::class
	);
});
