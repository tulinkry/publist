<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

// Neither CoordsForm (two plain text inputs) nor SearchForm (a free-text
// search box plus a multi-select with a default) declare a single
// setRequired()/addRule() - grouped together to pin down that an empty
// submission always validates cleanly for both.

test('Front\CoordsForm: empty latitude/longitude validates with no errors', function () {
	$form = new \App\Forms\Front\CoordsForm();
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('Front\SearchForm: empty search term validates with no errors', function () {
	$form = new \App\Forms\Front\SearchForm(null, null, new \StdClass());
	$form->validate();
	Assert::same([], $form->getErrors());
});
