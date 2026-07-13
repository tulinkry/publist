<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// Regression: CronPresenter once extended App\Presentation\Front\BasePresenter
// instead of App\Presentation\Admin\BasePresenter, silently skipping the
// isAllowed('backend') check every other Admin presenter enforces in
// startup() - reachable by anyone, unauthenticated. A live HTTP/presenter
// test can't cover this presenter specifically (constructing it always tries
// to connect to the injected EmailModel's real IMAP server), so this checks
// the one thing that actually matters: every App\Presentation\Admin\* presenter
// is part of the BasePresenter hierarchy that enforces the role check.

test('every Admin presenter extends the Admin BasePresenter (enforces the backend role check)', function () {
	$files = glob(__DIR__ . '/../../app/Presentation/Admin/*/*Presenter.php');
	Assert::true(count($files) > 0, 'expected to find at least one Admin presenter file');
	foreach ($files as $file) {
		$class = 'App\\Presentation\\Admin\\' . basename(dirname($file)) . '\\' . basename($file, '.php');
		Assert::true(
			is_subclass_of($class, 'App\\Presentation\\Admin\\BasePresenter'),
			"$class must extend App\\Presentation\\Admin\\BasePresenter"
		);
	}
});
