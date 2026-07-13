<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class ContactPresenterTest extends Tester\TestCase
{
	private Nette\DI\Container $container;


	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
	}


	public function testDefault()
	{
		$response = runPresenter($this->container, 'Front:Contact', 'default');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}
}


(new ContactPresenterTest($container))->run();
