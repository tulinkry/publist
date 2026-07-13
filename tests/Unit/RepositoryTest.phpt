<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../bootstrap.php';
seedDatabase($container);

$users = $container->getByType(\App\Model\UserModel::class);
$db = $container->getByType(\Nette\Database\Explorer::class);

test('item() fetches by primary key with the aliased "id" column present', function () use ($users) {
	$user = $users->item(1);
	Assert::notSame(null, $user);
	Assert::same(1, $user->id);
	Assert::same('testuser', $user->username);
	Assert::same(null, $users->item(999));
});

test('all()/by() filter by real column names', function () use ($users) {
	Assert::same(1, count($users->all()));
	Assert::same(1, count($users->by(['email' => 'test@example.com'])));
	Assert::same(0, count($users->by(['email' => 'nobody@example.com'])));
});

test('count() with and without a filter', function () use ($users) {
	Assert::same(1, $users->count());
	Assert::same(1, $users->count(['email' => 'test@example.com']));
	Assert::same(0, $users->count(['email' => 'nobody@example.com']));
});

// The remaining tests build on each other (insert a second user, then page
// over both) - kept in one block since Tester runs a bare-assertion file's
// test() blocks sequentially in the same process, but the dependency should
// stay explicit rather than implicit across separately-named blocks.
test('insert() returns the fresh row and limit() pages over real data in order', function () use ($users, $db) {
	$db->query('DELETE FROM users WHERE username = ?', 'seconduser');
	$inserted = $users->insert([
		'username' => 'seconduser',
		'email' => 'second@example.com',
		'password' => 'irrelevant-for-this-test',
		'right' => 'user',
		'click' => strtotime('2026-01-01'),
		'skin' => 0,
		'ip' => '127.0.0.1',
		'registration' => strtotime('2026-01-01'),
		'state' => 1,
	]);
	Assert::same('seconduser', $inserted->username);
	Assert::true($inserted->id > 0);
	Assert::same(2, $users->count());

	// "seconduser" < "testuser" alphabetically.
	$page = $users->limit(1, 0, [], ['username' => 'ASC']);
	Assert::same('seconduser', $page->fetch()->username);

	$page2 = $users->limit(1, 1, [], ['username' => 'ASC']);
	Assert::same('testuser', $page2->fetch()->username);
});

test('UserModel::hasRated() reflects the ratings table', function () use ($db) {
	$db->query('DELETE FROM ratings');
	$activeRow = $db->table('users')->get(1);
	Assert::false(\App\Model\UserModel::hasRated($activeRow, 1));

	$db->table('ratings')->insert(['pub_id' => 1, 'user_id' => 1, 'date' => strtotime('2026-01-01'), 'calculated' => 1]);
	Assert::true(\App\Model\UserModel::hasRated($activeRow, 1));
	Assert::false(\App\Model\UserModel::hasRated($activeRow, 999));
});
