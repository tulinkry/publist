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
	private Nette\Database\Explorer $db;


	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
		$this->user = $container->getByType(Nette\Security\User::class);
		$this->db = $container->getByType(Nette\Database\Explorer::class);
	}


	public function tearDown()
	{
		$this->user->logout(true);
	}


	public function testRequiresBackendRoleWhenAnonymous()
	{
		$response = runPresenter($this->container, 'Admin:Pub', 'default');
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
	}


	public function testDefault()
	{
		$this->login();
		$response = runPresenter($this->container, 'Admin:Pub', 'default');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testDetailValid()
	{
		$this->login();
		$response = runPresenter($this->container, 'Admin:Pub', 'detail', ['id' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Hospoda U Testu'));
	}


	public function testDetailWithMissingPubRedirectsInsteadOfFataling()
	{
		// Regression: actionDetail() never checked item($id) for null before
		// renderDetail() dereferenced $this->pub->id - a 500 for any
		// nonexistent/deleted pub id instead of the graceful redirect its
		// sibling Front\PubPresenter::loadPub() already does.
		$this->login();
		$response = runPresenter($this->container, 'Admin:Pub', 'detail', ['id' => 99999]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
	}


	public function testRatingPage()
	{
		$this->login();
		$response = runPresenter($this->container, 'Admin:Pub', 'rating', ['id' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testHandleHideThenUnhide()
	{
		$this->login();
		$response = runPresenter($this->container, 'Admin:Pub', 'default', ['do' => 'hide', 'pub_id' => 1]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		Assert::same(1, (int) $this->db->table('pubs')->get(1)->hidden);

		$response = runPresenter($this->container, 'Admin:Pub', 'default', ['do' => 'unhide', 'pub_id' => 1]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		Assert::same(0, (int) $this->db->table('pubs')->get(1)->hidden);
	}


	public function testHandleDelete()
	{
		$this->login();
		// A throwaway pub, not the shared fixture pub_id=1 every other test
		// in this file depends on staying around.
		$pub = $this->db->table('pubs')->insert($this->throwawayPub());
		$response = runPresenter($this->container, 'Admin:Pub', 'default', ['do' => 'delete', 'pub_id' => $pub->pub_id]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		Assert::null($this->db->table('pubs')->get($pub->pub_id));
	}


	public function testHandleDeleteRating()
	{
		$this->login();
		$pub = $this->db->table('pubs')->insert($this->throwawayPub());
		$rating = $this->db->table('ratings')->insert([
			'pub_id' => $pub->pub_id,
			'user_id' => 1,
			'date' => strtotime('2026-01-01 00:00:00'),
			'calculated' => 1,
		]);
		$response = runPresenter($this->container, 'Admin:Pub', 'default', ['do' => 'deleteRating', 'rating_id' => $rating->rating_id]);
		Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
		Assert::null($this->db->table('ratings')->get($rating->rating_id));
	}


	private function login(): void
	{
		$this->user->login('test@example.com', 'test1234');
	}


	private function throwawayPub(): array
	{
		$now = strtotime('2026-01-01 00:00:00');
		return [
			'name' => 'Throwaway Pub',
			'whole_name' => 'Throwaway Pub Whole Name',
			'long_name' => 'Throwaway Pub Whole Name',
			'location' => 'Praha',
			'address' => 'Throwaway 1',
			'latitude' => 50.0,
			'longitude' => 14.0,
			'hidden' => 0,
			'inserted' => $now,
			'updated' => $now,
			'markVoted' => 0,
			'beerMarkVoted' => 0,
			'beerPriceVoted' => 0,
			'wineMarkVoted' => 0,
			'winePriceVoted' => 0,
			'foodMarkVoted' => 0,
			'foodPriceVoted' => 0,
		];
	}
}


(new PubPresenterTest($container))->run();
