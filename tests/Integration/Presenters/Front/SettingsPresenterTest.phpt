<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class SettingsPresenterTest extends Tester\TestCase
{
	private Nette\DI\Container $container;
	private Nette\Security\User $user;


	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
		$this->user = $container->getByType(Nette\Security\User::class);
	}


	public function tearDown()
	{
		$this->user->logout(true);
	}


	public function testRequiresLoginWhenAnonymous()
	{
		// SettingsPresenter requires 'frontend' - anonymous should be
		// redirected to the login page, not 500.
		$response = runPresenter($this->container, 'Front:Settings', 'default');
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
	}


	public function testDefault()
	{
		$this->user->login('test@example.com', 'test1234');
		$response = runPresenter($this->container, 'Front:Settings', 'default');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}
}


(new SettingsPresenterTest($container))->run();
