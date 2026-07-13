<?php

namespace Test;

use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';
$beers = $container->getByType(\App\Model\BeerModel::class);

// Every field's setRequired()/addRule() call is commented out in both
// classes (Admin\BeerForm adds nothing of its own on top of Front\BeerForm),
// so an entirely empty submission is expected to validate cleanly - this
// pins that down rather than silently relying on it.

test('Front\BeerForm: completely empty submission still validates (no rules are actually wired up)', function () use ($beers) {
	$form = new \App\Forms\Front\BeerForm($beers, null);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('Front\BeerForm: a fully filled submission also validates', function () use ($beers) {
	$form = new \App\Forms\Front\BeerForm($beers, null);
	$form['name']->setValue('Staropramen');
	$form['degree']->setValue(12);
	$form['link']->setValue('http://example.com');
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('Admin\BeerForm: completely empty submission still validates', function () use ($beers) {
	$form = new \App\Forms\Admin\BeerForm($beers, null);
	$form->validate();
	Assert::same([], $form->getErrors());
});
