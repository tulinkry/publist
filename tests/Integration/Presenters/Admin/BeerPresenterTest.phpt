<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class BeerPresenterTest extends Tester\TestCase
{
	private Nette\DI\Container $container;
	private Nette\Security\User $user;
	private Nette\Database\Explorer $db;


	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
		$this->user = $container->getByType(Nette\Security\User::class);
		$this->db = $container->getByType(Nette\Database\Explorer::class);
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
		$response = runPresenter($this->container, 'Admin:Beer', 'default');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Pivo'));
	}


	public function testDetailValid()
	{
		$response = runPresenter($this->container, 'Admin:Beer', 'detail', ['id' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Pivo'));
	}


	public function testHandleDelete()
	{
		$beer = $this->db->table('beers')->insert(['name' => 'Throwaway Beer', 'degree' => 10, 'link' => null]);
		$response = runPresenter($this->container, 'Admin:Beer', 'default', ['do' => 'delete', 'beer_id' => $beer->beer_id]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		Assert::null($this->db->table('beers')->get($beer->beer_id));
	}


	// actionInsert() and handleSearch() both unconditionally call
	// App\Model\BeerLinksModel::by(), which does a live file_get_contents() to
	// http://www.pivnici.cz (see app/Model/BeerLinksModel.php) - no mock
	// exists, and hitting a real third party from the test suite would be
	// slow/flaky. Deliberately not covered here.
}


(new BeerPresenterTest($container))->run();
