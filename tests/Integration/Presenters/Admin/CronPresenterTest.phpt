<?php

/** @testCase */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../../bootstrap.php';
seedDatabase($container);


class CronPresenterTest extends Tester\TestCase
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


	public function testRenderRating()
	{
		// Regression: MailReader used to open its IMAP connection eagerly in
		// the constructor, so simply injecting App\Model\EmailModel into
		// CronPresenter (an @inject property every action on the presenter
		// gets, whether it touches email or not) made renderRating() 500
		// whenever the mail server was unreachable, even though it never
		// reads mail itself.
		$response = runPresenter($this->container, 'Admin:Cron', 'rating');
		Assert::type(Nette\Application\Responses\TextResponse::class, $response);
	}


	// renderDefault() is deliberately not covered here - unlike
	// renderRating() above, it unconditionally calls App\Model\EmailModel::by(),
	// which opens a real IMAP connection (see tulinkry/tulinkry's src/Mail/MailReader.php)
	// to a mail server this test environment doesn't have. No mock exists;
	// better covered by a real IMAP integration test than faked here.
}


(new CronPresenterTest($container))->run();
