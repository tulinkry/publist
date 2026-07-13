<?php

namespace Test;

use Nette;

// Modern Nette's built-in CSRF protection (Presenter::detectedCsrf(), see
// vendor/nette/application/src/Application/UI/AccessPolicy.php) requires a
// same-origin signal - it falls back (no Sec-Fetch-Site header, as here)
// to checking for this cookie. Without it every handle*() signal call in
// runPresenter() would redirect to "this" instead of running. Must be set
// before the container's Nette\Http\Request singleton is first resolved,
// so it has to happen here, at the top of the first required fixture file.
$_COOKIE['_nss'] = '1';

/**
 * Loads the SQLite test schema into the container's (in-memory) database
 * and seeds one pub, one user and one rating - just enough for BasePresenter's
 * startup() (which unconditionally queries pubs on every request) and a
 * handful of detail/rating-page presenters to run without hitting real data.
 */
function seedDatabase(Nette\DI\Container $container): void
{
	$db = $container->getByType(Nette\Database\Explorer::class);
	$db->getConnection()->getPdo()->exec(file_get_contents(__DIR__ . '/schema.sql'));

	// Nette's sqlite driver stores DATETIME columns as unix timestamps
	// (fmtDateTime = 'U'), not formatted strings like MySQL.
	$now = strtotime('2026-01-01 00:00:00');

	$db->table('users')->insert([
		'username' => 'testuser',
		'email' => 'test@example.com',
		'password' => \App\Core\Authenticator::calculateHash('test1234', 'test@example.com'),
		'right' => 'admin',
		'click' => $now,
		'skin' => 0,
		'ip' => '127.0.0.1',
		'registration' => $now,
		'state' => 1,
	]);

	$db->table('pubs')->insert([
		'name' => 'Testovaci Hospoda',
		'whole_name' => 'Testovaci Hospoda U Testu',
		'long_name' => 'Testovaci Hospoda U Testu',
		'location' => 'Praha',
		'address' => 'Testovaci 1',
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
	]);

	$db->table('ratings')->insert([
		'pub_id' => 1,
		'user_id' => 1,
		'date' => $now,
		'calculated' => 1,
	]);

	$db->table('beers')->insert([
		'name' => 'Testovaci Pivo',
		'degree' => 12,
		'link' => null,
	]);
}

/**
 * Runs $presenterName (e.g. "Front:Pub") for $action with $params via the
 * real DI container - the same container production uses, just pointed at
 * the in-memory sqlite fixture. Catches nothing: a wiring/covariance/missing
 * service bug throws here exactly like it would on a live request, which is
 * the point (see modernize-nette-app skill: "the test suite lies to you about
 * DI wiring" unless presenters go through the real container).
 */
function runPresenter(Nette\DI\Container $container, string $presenterName, string $action = 'default', array $params = []): Nette\Application\Response
{
	$presenterFactory = $container->getByType(Nette\Application\IPresenterFactory::class);
	/** @var Nette\Application\UI\Presenter $presenter */
	$presenter = $presenterFactory->createPresenter($presenterName);
	$presenter->autoCanonicalize = false;

	$request = new Nette\Application\Request($presenterName, Nette\Application\Request::FORWARD, ['action' => $action] + $params);

	return $presenter->run($request);
}

/**
 * Renders a TextResponse's template to a real HTML string - TextResponse::getSource()
 * holds the Template object itself (rendering is normally deferred to send()),
 * so behavior assertions on the actual markup need this explicit cast.
 */
function renderBody(Nette\Application\Responses\TextResponse $response): string
{
	// @layout.latte pulls in Tulinkry\Components\WebLoader\Compiler, which
	// triggers a pre-existing (unrelated to this test suite) PHP8.4
	// implicit-nullable-parameter E_DEPRECATED the first time it's loaded -
	// Tester\Environment turns that into a fatal test failure otherwise.
	return @(string) $response->getSource();
}

/** Generates a throwaway valid JPEG file for handleRotate/handleDelete signal tests. */
function makeFixtureImage(string $path, int $width = 400, int $height = 300): void
{
	$dir = dirname($path);
	if (!file_exists($dir)) {
		mkdir($dir, 0777, true);
	}

	$img = imagecreatetruecolor($width, $height);
	imagefill($img, 0, 0, imagecolorallocate($img, 100, 150, 200));
	imagejpeg($img, $path, 80);
	imagedestroy($img);
}
