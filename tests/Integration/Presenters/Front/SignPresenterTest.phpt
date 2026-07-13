<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class SignPresenterTest extends Tester\TestCase
{
	private Nette\DI\Container $container;


	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
	}


	public function testLogin()
	{
		$response = runPresenter($this->container, 'Front:Sign', 'login');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testRegister()
	{
		$response = runPresenter($this->container, 'Front:Sign', 'register');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	public function testActionLogout()
	{
		// actionLogout() itself is a no-op (body all commented out) - still
		// worth covering that the route boots and renders logout.latte.
		$response = runPresenter($this->container, 'Front:Sign', 'logout');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
		Assert::true(str_contains(renderBody($response), 'Byl jsi úspěšně odhlášen'));
	}
}


(new SignPresenterTest($container))->run();
