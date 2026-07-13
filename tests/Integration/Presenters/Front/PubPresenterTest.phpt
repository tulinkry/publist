<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class PubPresenterTest extends Tester\TestCase
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


	public function testDetail()
	{
		$response = runPresenter($this->container, 'Front:Pub', 'detail', ['id' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Hospoda U Testu'));
	}


	public function testSearch()
	{
		$response = runPresenter($this->container, 'Front:Pub', 'search', ['term' => 'Testovaci']);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		$body = renderBody($response);
		Assert::true(str_contains($body, 'Testovaci Hospoda U Testu'));
		Assert::true(str_contains($body, '(1 záznamů)'));
	}


	public function testInsert()
	{
		$response = runPresenter($this->container, 'Front:Pub', 'insert');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testInfo()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Pub', 'info', ['id' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testActionImage()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Pub', 'image', ['id' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'name="images1[]"'));
	}


	public function testHandleShowDialog()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Pub', 'default', ['do' => 'showDialog']);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'name="email"'));
	}


	public function testHandleCloseDialog()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Pub', 'default', ['do' => 'closeDialog']);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testHandleEnableDescription()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Pub', 'detail', ['id' => 1, 'do' => 'enableDescription']);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'name="text"'));
	}


	public function testHandleLocation()
	{
		$this->login();
		$response = runPresenter($this->container, 'Front:Pub', 'default', ['do' => 'location', 'lat' => 50.12, 'lng' => 14.41]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		$session = $this->container->getByType(Nette\Http\Session::class)->getSection('coords');
		Assert::same(50.12, $session->lat);
		Assert::same(14.41, $session->lng);
	}


	// handleSearch() unconditionally calls App\Model\BeerLinksModel::by(), which
	// does a live file_get_contents() to http://www.pivnici.cz (see
	// app/Model/BeerLinksModel.php) - no mock exists, and hitting a real
	// third party from the test suite would be slow/flaky. Not covered here.


	private function login(): void
	{
		$this->user->login('test@example.com', 'test1234');
	}
}


(new PubPresenterTest($container))->run();
