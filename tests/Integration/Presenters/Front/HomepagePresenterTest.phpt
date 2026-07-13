<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


/**
 * Front\HomepagePresenter - also the concrete presenter used to exercise
 * handleNextLast()/handleNextRated()/handleLogout(), which actually live on
 * the shared Tulinkry\Application\UI\Presenter / App\Presentation\Front\BasePresenter
 * base classes and are exposed identically by every front presenter.
 */
class HomepagePresenterTest extends Tester\TestCase
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


	public function testDefault()
	{
		$response = runPresenter($this->container, 'Front:Homepage');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Modern Business'));
	}


	public function testHandleNextLast()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Homepage', 'default', ['do' => 'nextLast', 'offset' => 10]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testHandleNextRated()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Homepage', 'default', ['do' => 'nextRated', 'offset' => 10]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testHandleLogout()
	{
		$this->login();
		Assert::true($this->user->isLoggedIn());
		$response = runPresenter($this->container, 'Front:Homepage', 'default', ['do' => 'logout']);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		Assert::false($this->user->isLoggedIn());
	}


	private function login(): void
	{
		$this->user->login('test@example.com', 'test1234');
	}
}


(new HomepagePresenterTest($container))->run();
