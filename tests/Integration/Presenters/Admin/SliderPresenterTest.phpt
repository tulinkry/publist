<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class SliderPresenterTest extends Tester\TestCase
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
		$response = runPresenter($this->container, 'Admin:Slider', 'default');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testHandleRotateThenDelete()
	{
		$dir = WWW_DIR . '/images/slider';
		$sizes = ['md', 'lg', 'xs', 'sm'];
		$file = 'slidertest-' . uniqid() . '.jpg';
		foreach ($sizes as $size) {
			makeFixtureImage("$dir/$size/$file", 400, 300);
		}
		try {
			$response = runPresenter($this->container, 'Admin:Slider', 'default', ['do' => 'rotate', 'file_name' => $file]);
			Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
			foreach ($sizes as $size) {
				[$w, $h] = getimagesize("$dir/$size/$file");
				Assert::true($w < $h, "$size should be rotated to portrait, got {$w}x{$h}");
			}

			$response = runPresenter($this->container, 'Admin:Slider', 'default', ['do' => 'delete', 'file_name' => $file]);
			Assert::type(Nette\Application\Responses\RedirectResponse::class, $response);
			foreach ($sizes as $size) {
				Assert::false(file_exists("$dir/$size/$file"));
			}
		} finally {
			foreach ($sizes as $size) {
				@unlink("$dir/$size/$file");
			}
		}
	}
}


(new SliderPresenterTest($container))->run();
