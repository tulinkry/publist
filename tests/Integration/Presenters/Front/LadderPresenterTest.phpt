<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class LadderPresenterTest extends Tester\TestCase
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


	public function testClosest()
	{
		$response = runPresenter($this->container, 'Front:Ladder', 'closest');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testDefault()
	{
		$response = runPresenter($this->container, 'Front:Ladder', 'default', ['sort' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Hospoda U Testu'));
	}


	public function testRenderAll()
	{
		$response = runPresenter($this->container, 'Front:Ladder', 'all', ['sort' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Hospoda U Testu'));
	}


	public function testRenderBeer()
	{
		$response = runPresenter($this->container, 'Front:Ladder', 'beer', ['sort' => 4]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Hospoda U Testu'));
	}


	public function testRenderWine()
	{
		$response = runPresenter($this->container, 'Front:Ladder', 'wine', ['sort' => 6]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Hospoda U Testu'));
	}


	public function testRenderTrial()
	{
		$response = runPresenter($this->container, 'Front:Ladder', 'trial', ['sort' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testRenderHarmonika()
	{
		$response = runPresenter($this->container, 'Front:Ladder', 'harmonika', ['sort' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testRenderNewest()
	{
		$response = runPresenter($this->container, 'Front:Ladder', 'newest', ['sort' => 16]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testActionClosestMap()
	{
		// lowercase, matching what the router's path2action filter actually
		// produces for a real "/front.ladder/closestmap" request - see
		// app/Presentation/Front/Ladder/closestmap.latte
		$response = runPresenter($this->container, 'Front:Ladder', 'closestmap');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Nejbližší'));
	}


	public function testHandleSort()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Ladder', 'default', ['do' => 'sort', 'sort' => 2, 'mode' => 1]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		Assert::true(str_contains($response->getUrl(), 'sort=2'));
	}


	public function testHandleLocation()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Ladder', 'default', ['do' => 'location', 'lat' => 50.11, 'lng' => 14.42]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		$session = $this->container->getByType(Nette\Http\Session::class)->getSection('coords');
		Assert::same(50.11, $session->lat);
		Assert::same(14.42, $session->lng);
	}


	private function login(): void
	{
		$this->user->login('test@example.com', 'test1234');
	}
}


(new LadderPresenterTest($container))->run();
