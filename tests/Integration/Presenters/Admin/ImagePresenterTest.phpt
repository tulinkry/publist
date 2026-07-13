<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class ImagePresenterTest extends Tester\TestCase
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
		$response = runPresenter($this->container, 'Admin:Image', 'default');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testDetail()
	{
		// Regression: renderDetail() filtered ratings by ["pub" => ...] instead
		// of the real column "pub_id" - a 500 on every real request.
		$response = runPresenter($this->container, 'Admin:Image', 'detail', ['id' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Testovaci Hospoda U Testu'));
	}


	public function testActionInsert()
	{
		$response = runPresenter($this->container, 'Admin:Image', 'insert', ['id' => 1]);
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testHandleRotateThenDelete()
	{
		$dir = WWW_DIR . '/images/pubs/1';
		$file = 'imagetest-' . uniqid() . '.jpg';
		makeFixtureImage("$dir/$file", 400, 300);
		makeFixtureImage("$dir/thumbnails/$file", 400, 300);
		try {
			$response = runPresenter($this->container, 'Admin:Image', 'detail', ['id' => 1, 'do' => 'rotate', 'file_name' => $file]);
			Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
			[$w, $h] = getimagesize("$dir/$file");
			Assert::true($w < $h, "image should be rotated to portrait, got {$w}x{$h}");

			$response = runPresenter($this->container, 'Admin:Image', 'detail', ['id' => 1, 'do' => 'delete', 'file_name' => $file]);
			Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
			Assert::false(file_exists("$dir/$file"));
		} finally {
			@unlink("$dir/$file");
			@unlink("$dir/thumbnails/$file");
		}
	}
}


(new ImagePresenterTest($container))->run();
