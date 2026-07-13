<?php

/**
 * True HTTP-level integration test: spawns the real production entrypoint
 * (www/index.php via bin/router.php, same as production/Docker) as a child
 * `php -S` process and drives it with real cURL requests, so it exercises
 * routing, sessions and Nette's same-origin form-submission check exactly
 * like a browser would - none of which the container-constructed presenter
 * tests in AnonymousPresentersTest/AuthenticatedPresentersTest go through,
 * since those call Presenter::run() directly with a hand-built Request.
 *
 * This is exactly the gap that made a real login POST silently do nothing
 * during development: curl without a same-origin signal (no Sec-Fetch-Site
 * header, no _nss cookie from a prior same-site GET) is indistinguishable
 * from a cross-site form post, so Nette's Form::receiveHttpData() discards
 * it. A presenter-construction test can't catch that; only a real HTTP
 * round trip can.
 */

namespace Test;

use Tester\Assert;
use Tester\HttpAssert;

require __DIR__ . '/../fixtures/presenter-test-helpers.php';

$root = __DIR__ . '/../..';
$dbFile = tempnam(sys_get_temp_dir(), 'publist-http-test-') . '.sqlite';
$port = 8199;
$base = "http://127.0.0.1:$port";

putenv('DB_DSN=sqlite:' . $dbFile);
putenv('DB_USER=');
putenv('DB_PASSWORD=');
putenv('GOOGLE_MAPS_API_KEY=');
putenv('EMAIL_SERVER=localhost');
putenv('EMAIL_PORT=143');
putenv('EMAIL_ARGUMENTS=novalidate-cert');
putenv('EMAIL_USER=');
putenv('EMAIL_PASSWORD=');
// No real mail transport here - see app/bootstrap.php's MOCK_MAILER handling.
putenv('MOCK_MAILER=1');

// Seed the same file the spawned server will use, via the exact same helper
// the container-based tests use - Repository/Explorer behave identically
// against a sqlite file vs :memory:, only the DSN differs.
$container = require __DIR__ . '/../bootstrap.php';
seedDatabase($container);

$routerScript = tempnam(sys_get_temp_dir(), 'publist-router-') . '.php';
file_put_contents($routerScript, file_get_contents("$root/bin/router.php"));

/**
 * A real cookie-jar-backed request, like a browser: the jar file persists
 * both the session cookie and Nette's same-origin `_nss` cookie (set on
 * every response - see Nette\Http\Helpers::StrictCookieName) across calls,
 * so a plain GET-then-POST sequence is indistinguishable from a browser
 * without having to fake either cookie by hand.
 */
function curlFetch(string $url, string $method, string $cookieJar, ?string $body = null, bool $ajax = false): array
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
	$headers = $ajax ? ['X-Requested-With: XMLHttpRequest'] : [];
	if ($body !== null) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
	}
	if ($headers) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	$response = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	return [
		'code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
		'body' => substr($response, $headerSize),
	];
}

function loginViaCookieJar(string $base, string $cookieJar): void
{
	curlFetch("$base/front.sign/login", 'GET', $cookieJar);
	curlFetch("$base/front.sign/login", 'POST', $cookieJar, http_build_query([
		'email' => 'test@example.com',
		'password' => 'test1234',
		'_do' => 'signInForm-submit',
	]));
}

/**
 * Same as curlFetch() but sends a real multipart/form-data body, needed for
 * file-upload forms (Nette's MultiUpload controls only populate from real
 * multipart parts, not urlencoded bodies).
 */
function curlFetchMultipart(string $url, string $cookieJar, array $fields): array
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	$response = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	return [
		'code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
		'body' => substr($response, $headerSize),
	];
}

/** Generates a throwaway valid JPEG file for upload tests. */
function makeTestImage(string $path, int $width = 400, int $height = 300): void
{
	$img = imagecreatetruecolor($width, $height);
	imagefill($img, 0, 0, imagecolorallocate($img, 100, 150, 200));
	imagejpeg($img, $path, 80);
	imagedestroy($img);
}

/**
 * Fails if the body contains Tracy's bluescreen (a real PHP warning/error
 * surfaced during rendering) - checking only the HTTP status code isn't
 * enough, since a non-fatal warning can still return 200 while Tracy injects
 * its bluescreen markup inline around the point it occurred, leaving the
 * rest of the page to render normally after it. This is exactly the gap
 * that let a real, live front.pub/insert bug (a 500 fixed further below,
 * plus several more warnings uncovered one at a time after each fix) slip
 * past a test that only checked the response code.
 */
function assertNoTracyError(string $body, string $context): void
{
	Assert::false(str_contains($body, 'class="tracy-bs-main"'), "$context: page rendered a Tracy warning/error bluescreen");
}

$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$process = proc_open(
	['php', '-S', "127.0.0.1:$port", '-t', "$root/www", $routerScript],
	$descriptors,
	$pipes,
	$root,
	null,
	['bypass_shell' => true]
);
Assert::true(is_resource($process));
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

try {
	// Wait for the dev server to come up.
	$ready = false;
	for ($i = 0; $i < 40; $i++) {
		$conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.25);
		if ($conn) {
			fclose($conn);
			$ready = true;
			break;
		}
		usleep(100_000);
	}
	Assert::true($ready, 'dev server did not come up in time');

	test('homepage boots through the real HTTP/session/router stack', function () use ($base) {
		HttpAssert::fetch("$base/")
			->expectCode(200)
			->expectBody(contains: 'Publist')
			->denyBody(contains: 'class="tracy-bs-main"');
	});

	test('every anonymous GET page renders without a PHP warning/error', function () use ($base) {
		// A 200 response code alone doesn't prove the page is clean - a
		// non-fatal warning still returns 200 while Tracy injects its
		// bluescreen inline (see assertNoTracyError() above). This is a
		// direct regression test for that exact class of bug: front.pub/insert
		// rendered 200 while showing five different warnings/errors, one at a
		// time, each only visible after the previous one was fixed.
		foreach ([
			'/front.pub/insert',
			'/front.pub/search',
			'/front.sign/login',
			'/front.sign/register',
			'/front.sign/logout',
			'/front.contact/', // "default" is canonicalized away from the URL
			'/front.contact/about',
			'/front.homepage/',
			'/front.ladder/?sort=1',
			'/front.ladder/all?sort=1',
			'/front.ladder/beer?sort=1',
			'/front.ladder/wine?sort=1',
			'/front.ladder/trial?sort=1',
			'/front.ladder/harmonika?sort=1',
			'/front.ladder/newest?sort=1',
			'/front.ladder/closest',
			'/front.ladder/closestmap', // route matching is case-sensitive; "closestMap" 404s
		] as $path) {
			$get = HttpAssert::fetch("$base$path");
			try {
				$get->expectCode(200);
				$get->denyBody(contains: 'class="tracy-bs-main"');
			} catch (\Throwable $e) {
				throw new \Exception("GET $path: " . $e->getMessage(), 0, $e);
			}
		}
	});

	test('every authenticated GET page renders without a PHP warning/error', function () use ($base) {
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);
			foreach ([
				'/front.pub/detail/1',
				'/front.pub/info/1',
				'/front.pub/image/1',
				'/front.settings/', // "default" is canonicalized away from the URL
				'/admin.pub/',
				'/admin.pub/detail/1',
				'/admin.pub/rating/1',
				'/admin.pub/types',
				'/admin.beer/',
				'/admin.beer/detail/1',
				'/admin.beer/insert',
				'/admin.homepage/',
				'/admin.homepage/dashboard',
				'/admin.image/',
				'/admin.image/detail/1',
				'/admin.image/insert/1',
				'/admin.slider/',
				'/admin.cron/rating',
				// admin.email/ is deliberately excluded - it requires a real
				// IMAP server, which this dev environment doesn't have; that's
				// an environment limitation, not a regression.
			] as $path) {
				$res = curlFetch("$base$path", 'GET', $jar);
				Assert::same(200, $res['code'], "GET $path");
				assertNoTracyError($res['body'], "GET $path");
			}
		} finally {
			@unlink($jar);
		}
	});

	test('a same-origin login (GET then POST with the _nss cookie) succeeds', function () use ($base) {
		$get = HttpAssert::fetch("$base/front.sign/login");
		$get->expectCode(200);

		$loginBody = http_build_query([
			'email' => 'test@example.com',
			'password' => 'test1234',
			'_do' => 'signInForm-submit',
		]);
		$post = HttpAssert::fetch(
			"$base/front.sign/login",
			'POST',
			['Content-Type' => 'application/x-www-form-urlencoded'],
			['_nss' => '1'],
			false,
			$loginBody
		);
		$post->expectCode(303);
		$post->expectHeader('set-cookie', contains: 'PHPSESSID');
	});

	test('a cross-origin-looking login POST (no _nss cookie) is silently ignored', function () use ($base) {
		// Regression coverage for the exact confusion this app hit during
		// development: without the same-origin signal, Nette treats the
		// form as not submitted at all, so the request just re-renders the
		// (still-anonymous) login page instead of logging in - it must NOT
		// look like a redirect/login success.
		$loginBody = http_build_query([
			'email' => 'test@example.com',
			'password' => 'test1234',
			'_do' => 'signInForm-submit',
		]);
		$post = HttpAssert::fetch(
			"$base/front.sign/login",
			'POST',
			['Content-Type' => 'application/x-www-form-urlencoded'],
			[],
			false,
			$loginBody
		);
		// The form is treated as not-submitted at all, so the presenter just
		// redirects back to the same login URL (not to the post-login page),
		// and no session gets created.
		$post->expectCode(303);
		$post->expectHeader('location', contains: '/front.sign/login');
		$post->denyHeader('set-cookie', contains: 'PHPSESSID');
	});

	test('submitting the search form does not 500', function () use ($base) {
		// Regression: every form's process() called $form->getValues(TRUE) -
		// Nette 2.x's "return as plain array" flag. In Nette 3 the first
		// parameter is $returnType (string|object|null); PHP's weak typing
		// silently coerces the bool true to the string "1", which Nette then
		// tries to use as a class name for Reflection - "Class '1' does not
		// exist" on every single form submission in the app.
		$get = HttpAssert::fetch("$base/");
		$get->expectCode(200);

		$body = http_build_query([
			'search' => 'Testovaci',
			'fields' => ['whole_name'],
			'_submit' => 'Hledej!',
			'_do' => 'searchForm-submit',
		]);
		$post = HttpAssert::fetch(
			"$base/",
			'POST',
			['Content-Type' => 'application/x-www-form-urlencoded'],
			['_nss' => '1'],
			false,
			$body
		);
		$post->expectCode(303);
		$post->expectHeader('location', contains: '/front.pub/search');
	});

	test('submitting the description form on a pub detail page actually saves it', function () use ($base, $dbFile) {
		// Regression: Tulinkry\Forms\Form::attached() is dead code under
		// modern Nette (it's never called - see tulinkry/tulinkry's src/Forms/Form.php)
		// but used to be the ONLY thing that wired a form's process() method
		// onto onSuccess for any form that didn't wire it explicitly.
		// DescriptionForm *looked* like it worked in the browser (Nette forms
		// redisplay submitted values by default, so the new text appeared on
		// the re-rendered page) but process() never actually ran, so
		// pub_descriptions never received a row.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);

			curlFetch("$base/front.pub/detail/1", 'GET', $jar);
			$post = curlFetch("$base/front.pub/detail/1", 'POST', $jar, http_build_query([
				'id' => '',
				'text' => 'HttpTest regression description',
				'submit' => 'Upravit',
				'_do' => 'descriptionForm-submit',
			]));
			Assert::same(303, $post['code']);

			$pdo = new \PDO('sqlite:' . $dbFile);
			$row = $pdo->query("SELECT text FROM pub_descriptions WHERE pub_id = 1 ORDER BY description_id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
			Assert::truthy($row);
			Assert::same('HttpTest regression description', $row['text']);
		} finally {
			@unlink($jar);
		}
	});

	test('submitting the beer form on a pub info page actually saves it', function () use ($base, $dbFile) {
		// Regression: same root cause as the description form above -
		// App\Forms\Front\BeerForm::process() was never wired to
		// onSuccess, so "adding" a beer silently no-opped despite the UI
		// showing a "Nové pivo úspěšně přidáno" success flash message (that
		// flash comes from PubPresenter's own onSubmit closure, which fires
		// regardless of whether process() ever ran).
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);

			curlFetch("$base/front.pub/info/1", 'GET', $jar);
			$post = curlFetch("$base/front.pub/info/1", 'POST', $jar, http_build_query([
				'id' => '',
				'name' => 'HttpTest Regression Beer',
				'degree' => '12',
				'link' => '',
				'submit' => 'Přidat',
				'_do' => 'beerForm-submit',
			]));
			Assert::same(303, $post['code']);

			$pdo = new \PDO('sqlite:' . $dbFile);
			$row = $pdo->query("SELECT name, degree FROM beers WHERE name = 'HttpTest Regression Beer'")->fetch(\PDO::FETCH_ASSOC);
			Assert::truthy($row);
			Assert::same(12, (int) $row['degree']);
		} finally {
			@unlink($jar);
		}
	});

	test('submitting the contact form does not 500', function () use ($base) {
		// Regression: same dead-attachHandlers() root cause. Additionally,
		// ContactForm::process() used to call Message::send() with no
		// try/catch at all, unlike every other form's persistence call - if
		// no MTA is configured (or process() now genuinely runs for the
		// first time in a fresh environment), send() throwing would 500.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			curlFetch("$base/front.contact/default", 'GET', $jar);
			$post = curlFetch("$base/front.contact/default", 'POST', $jar, http_build_query([
				'name' => 'HttpTest Regression',
				'email' => 'httptest@example.com',
				'message' => 'Hello from the regression suite',
				'submit' => 'Odeslat',
				'_do' => 'contactForm-submit',
			]));
			Assert::true(in_array($post['code'], [200, 303], true), "expected 200 or 303, got {$post['code']}");
		} finally {
			@unlink($jar);
		}
	});

	test('submitting the change-password form actually changes the password', function () use ($base, $dbFile) {
		// Regression: same dead-attachHandlers() root cause - the password
		// looked "changed" (success flash) but process() never ran, so the
		// stored hash never actually updated.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);

			curlFetch("$base/front.settings/default", 'GET', $jar);
			$post = curlFetch("$base/front.settings/default", 'POST', $jar, http_build_query([
				'password1' => 'newpass123',
				'password2' => 'newpass123',
				'old_password' => 'test1234',
				'submit' => 'Uložit',
				'_do' => 'changePasswordForm-submit',
			]));
			Assert::same(303, $post['code']);

			$pdo = new \PDO('sqlite:' . $dbFile);
			$row = $pdo->query("SELECT password FROM users WHERE email = 'test@example.com'")->fetch(\PDO::FETCH_ASSOC);
			Assert::truthy($row);
			Assert::notSame(\App\Core\Authenticator::calculateHash('test1234', 'test@example.com'), $row['password']);
			Assert::true(password_verify('newpass123', $row['password']));
		} finally {
			@unlink($jar);
			// Other tests in this file share the same spawned server/db and
			// log in as test@example.com/test1234 - restore it directly so
			// this test doesn't break test ordering for the rest of the file.
			$pdo = new \PDO('sqlite:' . $dbFile);
			$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'test@example.com'");
			$stmt->execute([\App\Core\Authenticator::calculateHash('test1234', 'test@example.com')]);
		}
	});

	test('submitting the todo form on the admin homepage actually saves it', function () use ($base) {
		// Regression: same dead-attachHandlers() root cause. TodoForm writes
		// a real file next to the presenter, so back up/restore it around
		// the test instead of leaving test output committed to the repo.
		$todoFile = __DIR__ . '/../../app/Presentation/Admin/Homepage/todo.txt';
		$original = file_exists($todoFile) ? file_get_contents($todoFile) : null;
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);

			curlFetch("$base/admin.homepage/default", 'GET', $jar);
			$marker = 'HttpTest regression todo ' . md5((string) $original);
			$post = curlFetch("$base/admin.homepage/default", 'POST', $jar, http_build_query([
				'todo' => $marker,
				'submit' => 'Uložit',
				'_do' => 'todoForm-submit',
			]));
			Assert::true(in_array($post['code'], [200, 303], true), "expected 200 or 303, got {$post['code']}");
			Assert::true(file_exists($todoFile));
			Assert::same($marker, file_get_contents($todoFile));
		} finally {
			@unlink($jar);
			if ($original === null) {
				@unlink($todoFile);
			} else {
				file_put_contents($todoFile, $original);
			}
		}
	});

	test('submitting the pub-types form on the admin pub page actually saves it', function () use ($base) {
		// Regression: same dead-attachHandlers() root cause. PubTypesForm
		// writes a real config file, so back up/restore it around the test.
		$typesFile = __DIR__ . '/../../app/config/types.neon';
		$original = file_get_contents($typesFile);
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);

			curlFetch("$base/admin.pub/default", 'GET', $jar);
			$newContent = "types:\n\tRestaurace: \"HttpTest regression\"\n";
			$post = curlFetch("$base/admin.pub/default", 'POST', $jar, http_build_query([
				'types' => $newContent,
				'submit' => 'Uložit',
				'_do' => 'pubTypesForm-submit',
			]));
			Assert::true(in_array($post['code'], [200, 303], true), "expected 200 or 303, got {$post['code']}");
			Assert::same($newContent, file_get_contents($typesFile));
		} finally {
			@unlink($jar);
			file_put_contents($typesFile, $original);
		}
	});

	test('submitting the image form on a pub page actually saves an image', function () use ($base) {
		// Regression: same dead-attachHandlers() root cause, plus a second,
		// independently-dormant bug it exposed: Image::save() is void and
		// throws on failure in current Nette (it used to return bool) - see
		// App\Model\PubModel::saveImage() - so the old
		// "$return_value && $img->resize(...)->save(...)" boolean chain
		// always evaluated falsy, silently short-circuiting past the
		// thumbnail save every time even on a real success. ImageForm saves
		// real files under www/images/pubs/<id>/ (a shared, non-isolated
		// directory since this test drives the real www/ tree) - only assert
		// on files that appear during the test and delete exactly those.
		$galleryDir = __DIR__ . '/../../www/images/pubs/1';
		$before = file_exists($galleryDir) ? scandir($galleryDir) : [];
		$thumbDir = "$galleryDir/thumbnails";
		$beforeThumb = file_exists($thumbDir) ? scandir($thumbDir) : [];

		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		$imagePath = tempnam(sys_get_temp_dir(), 'publist-test-image-') . '.jpg';
		makeTestImage($imagePath);
		try {
			loginViaCookieJar($base, $jar);
			curlFetch("$base/front.pub/image/1", 'GET', $jar);

			$post = curlFetchMultipart("$base/front.pub/image/1", $jar, [
				'id' => '1',
				'images1[]' => new \CURLFile($imagePath, 'image/jpeg', 'httptest.jpg'),
				'submit' => 'Přidat',
				'_do' => 'imageForm-submit',
			]);
			// A real successful upload always redirects (303) - ImageForm::process()
			// only falls through to a 200 re-render on a validation/save error, so
			// this must be an exact 303, not "200 or 303" (a 200 here would mean the
			// upload silently failed, exactly the false-positive this regression hid).
			Assert::same(303, $post['code']);

			$after = file_exists($galleryDir) ? scandir($galleryDir) : [];
			$newFiles = array_diff($after, $before);
			Assert::true(count($newFiles) > 0, 'expected at least one new file in ' . $galleryDir);

			$afterThumb = file_exists($thumbDir) ? scandir($thumbDir) : [];
			$newThumbs = array_diff($afterThumb, $beforeThumb);
			Assert::true(count($newThumbs) > 0, 'expected at least one new thumbnail in ' . $thumbDir);

			foreach ($newFiles as $file) {
				@unlink("$galleryDir/$file");
			}
			foreach ($newThumbs as $file) {
				@unlink("$thumbDir/$file");
			}
		} finally {
			@unlink($jar);
			@unlink($imagePath);
		}
	});

	test('submitting the pub-insert form actually creates a pub', function () use ($base, $dbFile) {
		// Regression: same dead-attachHandlers() root cause, plus a second,
		// independently-dormant bug it exposed: GpsPositionPicker's value
		// object only has lat/lng/address (see
		// vendor/vojtech-dobes/nette-forms-gpspicker/src/GpsPositionPicker.php),
		// but PubForm::processValues() read a nonexistent ->location
		// property off it - a MemberAccessException on every successful
		// submission, once process() actually got a chance to run.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);

			curlFetch("$base/front.pub/insert", 'GET', $jar);
			$post = curlFetch("$base/front.pub/insert", 'POST', $jar, http_build_query([
				'whole_name' => 'HttpTest Regression Pub',
				'type' => ['0'],
				'name' => 'Regression Pub',
				'long_name' => 'A pub inserted by the regression suite',
				'opening_hours' => '',
				'website' => '',
				'coords[lat]' => '50.09',
				'coords[lng]' => '14.43',
				'coords[search]' => 'Testovaci 42, Praha',
				'agreement' => '',
				'submit' => 'Vložit',
				'_do' => 'pubForm-submit',
			]));
			Assert::same(303, $post['code']);

			$pdo = new \PDO('sqlite:' . $dbFile);
			$row = $pdo->query("SELECT whole_name, address, location FROM pubs WHERE whole_name = 'HttpTest Regression Pub'")->fetch(\PDO::FETCH_ASSOC);
			Assert::truthy($row);
			Assert::same('Testovaci 42, Praha', $row['address']);
			Assert::same('Testovaci 42, Praha', $row['location']);
		} finally {
			@unlink($jar);
		}
	});

	test('submitting the admin pub-edit form actually updates the pub', function () use ($base, $dbFile) {
		// Regression: App\Forms\Admin\PubForm::process() has its own copy
		// of the same GpsPositionPicker->location bug fixed in the front
		// form above - same fix, separate call site.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);

			curlFetch("$base/admin.pub/detail/1", 'GET', $jar);
			$post = curlFetch("$base/admin.pub/detail/1", 'POST', $jar, http_build_query([
				'id' => '1',
				'whole_name' => 'Updated Testovaci Hospoda',
				'type' => ['0'],
				'name' => 'Updated Hospoda',
				'long_name' => 'Updated description',
				'opening_hours' => '',
				'website' => '',
				'coords[lat]' => '50.1',
				'coords[lng]' => '14.5',
				'coords[search]' => 'Nova adresa 1, Praha',
				'submit' => 'Uložit',
				'_do' => 'pubForm-submit',
			]));
			Assert::same(303, $post['code']);

			$pdo = new \PDO('sqlite:' . $dbFile);
			$row = $pdo->query("SELECT whole_name, address, location FROM pubs WHERE pub_id = 1")->fetch(\PDO::FETCH_ASSOC);
			Assert::truthy($row);
			Assert::same('Updated Testovaci Hospoda', $row['whole_name']);
			Assert::same('Nova adresa 1, Praha', $row['address']);
			Assert::same('Nova adresa 1, Praha', $row['location']);
		} finally {
			@unlink($jar);
		}
	});

	test('submitting the registration form actually creates a user', function () use ($base, $dbFile) {
		// Regression: SignPresenter::registerFormSubmitted() had three
		// independent bugs, all dormant until a real submission finally
		// reached the insert - $form->values returned an ArrayHash (not the
		// plain array Repository::insert() requires); "right"/"skin"/"state"
		// (NOT NULL, no DB default) were never set; and "another_password"
		// (a form-only confirmation field, not a users table column) was
		// never stripped before the insert.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			curlFetch("$base/front.sign/register", 'GET', $jar);
			$post = curlFetch("$base/front.sign/register", 'POST', $jar, http_build_query([
				'username' => 'httptestregression',
				'password' => 'regpass123',
				'another_password' => 'regpass123',
				'email' => 'httptestregistration@example.com',
				'register' => 'Registrovat',
				'_do' => 'registrationForm-submit',
			]));
			Assert::same(303, $post['code']);

			$pdo = new \PDO('sqlite:' . $dbFile);
			$row = $pdo->query("SELECT username, email, \"right\", skin, state FROM users WHERE username = 'httptestregression'")->fetch(\PDO::FETCH_ASSOC);
			Assert::truthy($row);
			Assert::same('httptestregistration@example.com', $row['email']);
			Assert::same('user', $row['right']);
		} finally {
			@unlink($jar);
		}
	});

	test('submitting the slider form on the admin slider page actually saves an image', function () use ($base) {
		// Regression: same dead-attachHandlers() root cause, plus the same
		// Nette\Utils\Strings::random() removal that broke ImageForm (see
		// app/Model/PubModel.php::saveImage()) - SliderForm::save() called
		// it too - plus two more bugs of its own: Image::save() being void
		// now (see the ImageForm regression above) broke the same boolean
		// chain here across FOUR save() calls, and resize() no longer
		// accepts an "Npx"-suffixed string (plain int or "N%" only), so
		// every resize() call threw. Also requires real images >= 1200x800,
		// unlike ImageForm.
		$galleryDir = __DIR__ . '/../../www/images/slider';
		$dirs = ['lg', 'md', 'sm', 'xs'];
		$before = [];
		foreach ($dirs as $dir) {
			$before[$dir] = file_exists("$galleryDir/$dir") ? scandir("$galleryDir/$dir") : [];
		}

		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		$imagePath = tempnam(sys_get_temp_dir(), 'publist-test-slider-') . '.jpg';
		makeTestImage($imagePath, 1200, 800);
		try {
			loginViaCookieJar($base, $jar);
			curlFetch("$base/admin.slider/default", 'GET', $jar);

			$post = curlFetchMultipart("$base/admin.slider/default", $jar, [
				'images[]' => new \CURLFile($imagePath, 'image/jpeg', 'httptest-slider.jpg'),
				'submit' => 'Přidat',
				'_do' => 'sliderForm-submit',
			]);
			// A real successful upload always redirects (303) - SliderForm::process()
			// only falls through to a 200 re-render on a validation/save error.
			Assert::same(303, $post['code']);

			$newFilesByDir = [];
			foreach ($dirs as $dir) {
				$after = file_exists("$galleryDir/$dir") ? scandir("$galleryDir/$dir") : [];
				$newFilesByDir[$dir] = array_diff($after, $before[$dir]);
				Assert::true(count($newFilesByDir[$dir]) > 0, "expected at least one new file in $galleryDir/$dir");
			}

			foreach ($newFilesByDir as $dir => $files) {
				foreach ($files as $file) {
					@unlink("$galleryDir/$dir/$file");
				}
			}
		} finally {
			@unlink($jar);
			@unlink($imagePath);
		}
	});

	test('rating form star widgets render without a wrapping div per star', function () use ($base) {
		// Regression: Html::getName() is non-nullable now (defaults to '',
		// never null), so Tulinkry\Forms\Form::attachClasses()'s
		// "!== NULL" check was always true, forcing a <div class="radio">
		// around every star even though RatingForm explicitly disables the
		// separator - that div is block-level, so all 10 stars stacked
		// vertically instead of forming one row.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);
			$res = curlFetch("$base/front.pub/info/1", 'GET', $jar);
			Assert::same(200, $res['code']);
			Assert::true(str_contains($res['body'], 'name="mandatory[interierCriteria]"'));
			Assert::false(str_contains($res['body'], '<div class="radio">'), 'star radios should not be wrapped in <div class="radio">');
		} finally {
			@unlink($jar);
		}
	});

	test('pub insert form exposes the type auto-detect regex map to the browser', function () use ($base) {
		// Regression: PubForm::__construct() set $select->data['types'] = ...,
		// but Html's array-index data setter only ever renders a literal
		// "data" attribute, never "data-types" - main.js's $slc.data('types')
		// therefore read undefined, and Object.keys(undefined) threw,
		// aborting pubForm() before it bound any of the keyup handlers (the
		// whole-name autofill, capitalization and "Vypnout našeptávání"
		// button all went dead from one uncaught exception).
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		$res = curlFetch("$base/front.pub/insert", 'GET', $jar);
		@unlink($jar);
		Assert::same(200, $res['code']);
		Assert::true((bool) preg_match('/data-types=\'([^\']*)\'/', $res['body'], $m), 'expected a data-types attribute on the type select');
		$types = json_decode(str_replace('&quot;', '"', $m[1]), true);
		Assert::truthy($types);
		Assert::true(isset($types['Restaurace']), 'expected the "Restaurace" regex pattern to be present');
	});

	test('clicking "add beer" on the rating form actually adds a beer row', function () use ($base) {
		// Regression: three independent bugs, all dormant until this signal
		// finally got a chance to run - Kdyby\Replicator\Container::register()
		// (called from app/bootstrap.php) referenced a since-renamed
		// NAME_SEPARATOR constant (now NameSeparator); the beer brand <select>
		// was built from a raw Nette\Database\Table\Selection instead of a
		// fetchPairs() array; and calling this signal at all requires the
		// same live-HTTP CSRF context as every other form regression fixed
		// this session.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);
			curlFetch("$base/front.pub/info/1", 'GET', $jar);

			$post = curlFetch("$base/front.pub/info/1", 'POST', $jar, http_build_query([
				'optional' => ['beers' => ['addBeer' => 'Přidat pivo']],
				'_do' => 'ratingForm-submit',
			]));
			Assert::same(200, $post['code']);
			assertNoTracyError($post['body'], 'add beer');
			Assert::true(str_contains($post['body'], 'name="optional[beers][0][brand]"'), 'expected a new beer row to appear');
		} finally {
			@unlink($jar);
		}
	});

	test('clicking "delete beer" on the rating form removes the beer row', function () use ($base) {
		// Regression: Kdyby\Replicator\Container::remove() reflects into
		// Nette\Forms\ControlGroup's private $controls property expecting an
		// \SplObjectStorage (contains()/detach()) - current Nette stores it
		// as a \WeakMap instead (isset()/unset() via ArrayAccess), so every
		// "delete beer" click threw immediately.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);
			curlFetch("$base/front.pub/info/1", 'GET', $jar);
			curlFetch("$base/front.pub/info/1", 'POST', $jar, http_build_query([
				'optional' => ['beers' => ['addBeer' => 'Přidat pivo']],
				'_do' => 'ratingForm-submit',
			]));

			$post = curlFetch("$base/front.pub/info/1", 'POST', $jar, http_build_query([
				'optional' => ['beers' => ['0' => ['deleteBeer' => 'Smazat pivo']]],
				'_do' => 'ratingForm-submit',
			]));
			Assert::same(200, $post['code']);
			assertNoTracyError($post['body'], 'delete beer');
			Assert::false(str_contains($post['body'], 'name="optional[beers][0][brand]"'), 'expected the beer row to be gone');
		} finally {
			@unlink($jar);
		}
	});

	test('submitting the rating form actually updates an existing rating', function () use ($base, $dbFile) {
		// Regression: RatingForm::ratingFormSubmitted() took the
		// "$this->rating already exists" branch (the seeded fixture already
		// has a rating for this user+pub), called $this->rating->update(...),
		// then read ->id off the SAME row object - but ActiveRow::update()
		// refetches with select('*') (raw columns only), dropping the
		// aliased "id" column, so ->id threw MemberAccessException on every
		// edit of an existing rating. Also a reminder that Nette renames a
		// field literally named "submit" to "_submit" (it's in Nette's own
		// Forms\Helpers::UnsafeNames list, to avoid colliding with the native
		// <form>.submit() DOM method) - not a bug, just easy to get wrong
		// when hand-building a POST body.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);
			curlFetch("$base/front.pub/info/1", 'GET', $jar);

			$post = curlFetch("$base/front.pub/info/1", 'POST', $jar, http_build_query([
				'mandatory' => [
					'interierCriteria' => '5',
					'exterierCriteria' => '5',
					'serviceCriteria' => '5',
					'overallCriteria' => '5',
					'foodPriceCriteria' => '5',
				],
				'optional' => [
					'wineCriteria' => '0',
					'toaletsCriteria' => '0',
					'foodCriteria' => '0',
					'garden' => '',
				],
				'_submit' => 'Odeslat hodnocení',
				'_do' => 'ratingForm-submit',
			]));
			Assert::same(303, $post['code']);

			$pdo = new \PDO('sqlite:' . $dbFile);
			$row = $pdo->query("SELECT interier_criteria, exterier_criteria FROM ratings WHERE pub_id = 1 AND user_id = 1")->fetch(\PDO::FETCH_ASSOC);
			Assert::truthy($row);
			Assert::same(5, (int) $row['interier_criteria']);
			Assert::same(5, (int) $row['exterier_criteria']);
		} finally {
			@unlink($jar);
		}
	});

	test('rotating a pub image actually rotates the file and its thumbnail', function () use ($base) {
		// Regression: PubModel::rotateImage() chained "$ret = $ret && $img->save(...)" -
		// Image::save() is void now (see other save() regressions this
		// session), so $ret was always falsy after the first save(), and the
		// thumbnail's save() call never even ran (short-circuited).
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		$imagePath = tempnam(sys_get_temp_dir(), 'publist-test-rotate-') . '.jpg';
		makeTestImage($imagePath, 400, 300);
		try {
			loginViaCookieJar($base, $jar);
			curlFetch("$base/front.pub/image/1", 'GET', $jar);
			curlFetchMultipart("$base/front.pub/image/1", $jar, [
				'id' => '1',
				'images1[]' => new \CURLFile($imagePath, 'image/jpeg', 'rotate-test.jpg'),
				'submit' => 'Přidat',
				'_do' => 'imageForm-submit',
			]);

			$galleryDir = __DIR__ . '/../../www/images/pubs/1';
			$files = glob("$galleryDir/*.jpg");
			usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
			$file = basename($files[0]);

			$post = curlFetch("$base/admin.image/detail/1?do=rotate&file_name=$file", 'GET', $jar, null, true);
			assertNoTracyError($post['body'], 'rotate image');
			Assert::false(str_contains($post['body'], 'Neexistující obrázek'), 'rotateImage() should not report failure');

			[$w, $h] = getimagesize("$galleryDir/$file");
			[$tw, $th] = getimagesize("$galleryDir/thumbnails/$file");
			Assert::same([300, 400], [$w, $h]);
			Assert::true($tw < $th, 'thumbnail should also be rotated (portrait now)');

			@unlink("$galleryDir/$file");
			@unlink("$galleryDir/thumbnails/$file");
		} finally {
			@unlink($jar);
			@unlink($imagePath);
		}
	});

	test('rotating a slider image rotates all four sizes, not just one', function () use ($base) {
		// Regression: SliderPresenter::handleRotate()'s inner closure did
		// "if (!$img->save(...)) return false;" per size in a loop over
		// [md, lg, xs, sm] - since save() is void now, this always bailed
		// out after rotating just "md", leaving the other three sizes
		// un-rotated and reporting "Neexistující obrázek" even though the
		// file existed.
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		$imagePath = tempnam(sys_get_temp_dir(), 'publist-test-slider-rotate-') . '.jpg';
		makeTestImage($imagePath, 1200, 800);
		try {
			loginViaCookieJar($base, $jar);
			curlFetch("$base/admin.slider/", 'GET', $jar);
			curlFetchMultipart("$base/admin.slider/", $jar, [
				'images[]' => new \CURLFile($imagePath, 'image/jpeg', 'slider-rotate-test.jpg'),
				'submit' => 'Přidat',
				'_do' => 'sliderForm-submit',
			]);

			$galleryDir = __DIR__ . '/../../www/images/slider';
			$files = glob("$galleryDir/lg/*.jpg");
			usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
			$file = basename($files[0]);

			$post = curlFetch("$base/admin.slider/?do=rotate&file_name=$file", 'GET', $jar, null, true);
			assertNoTracyError($post['body'], 'rotate slider image');
			Assert::false(str_contains($post['body'], 'Neexistující obrázek'), 'handleRotate() should not report failure');

			foreach (['lg', 'md', 'sm', 'xs'] as $size) {
				[$w, $h] = getimagesize("$galleryDir/$size/$file");
				Assert::true($w < $h, "$size should be rotated (portrait now), got {$w}x{$h}");
			}

			foreach (['lg', 'md', 'sm', 'xs'] as $size) {
				@unlink("$galleryDir/$size/$file");
			}
		} finally {
			@unlink($jar);
			@unlink($imagePath);
		}
	});

	test('submitting the admin rating-edit form actually updates the rating', function () use ($base, $dbFile) {
		// Regression: App\Forms\Admin\RatingForm::ratingFormUpdated() looked
		// the rating up via $values["id"], but that hidden field always holds
		// $pub_id (see App\Forms\Front\RatingForm's constructor) - and
		// Admin's own constructor always passes pub_id as null, so the lookup
		// never found anything ("Neexistující hodnocení!" every time). Fixed
		// to use $this->rating directly. Also hit the by-now-familiar
		// ActiveRow::update()-drops-the-"id"-alias bug, and a *third* variant:
		// $entity->ref('pubs', 'pub_id') returns a raw row that never had the
		// "id" alias applied at all (ref() bypasses Repository entirely).
		$jar = tempnam(sys_get_temp_dir(), 'publist-cookies-') . '.txt';
		try {
			loginViaCookieJar($base, $jar);
			curlFetch("$base/admin.pub/rating/1", 'GET', $jar);

			$post = curlFetch("$base/admin.pub/rating/1", 'POST', $jar, http_build_query([
				'mandatory' => [
					'interierCriteria' => '8',
					'exterierCriteria' => '8',
					'serviceCriteria' => '8',
					'overallCriteria' => '8',
					'foodPriceCriteria' => '8',
				],
				'optional' => [
					'wineCriteria' => '0',
					'toaletsCriteria' => '0',
					'foodCriteria' => '0',
					'garden' => '',
				],
				'_submit' => 'Uložit hodnocení',
				'_do' => 'ratingForm-submit',
			]));
			Assert::same(303, $post['code']);

			$pdo = new \PDO('sqlite:' . $dbFile);
			$row = $pdo->query("SELECT interier_criteria FROM ratings WHERE pub_id = 1 AND user_id = 1")->fetch(\PDO::FETCH_ASSOC);
			Assert::truthy($row);
			Assert::same(8, (int) $row['interier_criteria']);
		} finally {
			@unlink($jar);
		}
	});

} finally {
	proc_terminate($process);
	proc_close($process);
	@unlink($dbFile);
	@unlink($routerScript);
}
