<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class HomepagePresenterTest extends Tester\TestCase
{
	private Nette\DI\Container $container;
	private Nette\Security\User $user;


	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
		$this->user = $container->getByType(Nette\Security\User::class);
	}


	public function setUp()
	{
		$this->user->login('test@example.com', 'test1234');
	}


	public function tearDown()
	{
		$this->user->logout(true);
	}


	public function testDefault()
	{
		$response = runPresenter($this->container, 'Admin:Homepage', 'default');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}
}


(new HomepagePresenterTest($container))->run();
