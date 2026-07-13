<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../bootstrap.php';
seedDatabase($container);

$db = $container->getByType(\Nette\Database\Explorer::class);
$db->table('beers')->insert(['name' => 'Pilsner Urquell', 'degree' => 12]);

$pubs = $container->getByType(\App\Model\PubModel::class);
$ratings = $container->getByType(\App\Model\RatingModel::class);
$beers = $container->getByType(\App\Model\BeerModel::class);
$users = $container->getByType(\App\Model\UserModel::class);

function newRatingForm($pubs, $ratings, $beers, $users): \App\Forms\Front\RatingForm
{
	$form = new \App\Forms\Front\RatingForm($pubs, $ratings, $beers, $users, null, null);
	foreach (['interierCriteria', 'exterierCriteria', 'serviceCriteria', 'overallCriteria', 'foodPriceCriteria'] as $field) {
		$form['mandatory'][$field]->setValue(1);
	}
	return $form;
}

test('mandatory criteria alone (no beer rows added) validates with no errors', function () use ($pubs, $ratings, $beers, $users) {
	$form = newRatingForm($pubs, $ratings, $beers, $users);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('a beer row with a selected brand validates with no errors', function () use ($pubs, $ratings, $beers, $users) {
	$form = newRatingForm($pubs, $ratings, $beers, $users);
	$row = $form['optional']['beers']->createOne();
	$row['brand']->setValue(1);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('a beer row left without a selected brand is rejected', function () use ($pubs, $ratings, $beers, $users) {
	$form = newRatingForm($pubs, $ratings, $beers, $users);
	$form['optional']['beers']->createOne();
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

test('winePrice within the 10-500 range validates with no errors', function () use ($pubs, $ratings, $beers, $users) {
	$form = newRatingForm($pubs, $ratings, $beers, $users);
	$form['optional']['winePrice']->setValue(50);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('winePrice left as "Nehodnotit" (empty) skips the range rule entirely', function () use ($pubs, $ratings, $beers, $users) {
	$form = newRatingForm($pubs, $ratings, $beers, $users);
	$form->validate();
	Assert::same([], $form->getErrors());
});

// The RANGE rule on winePrice can't actually be exercised: ChoiceControl::
// getValue() (see vendor/nette/forms/src/Forms/Controls/ChoiceControl.php)
// re-checks array_key_exists() against the item list on every read and
// returns null for anything outside it, checkDefaultValue(false) or not -
// so no value ever reaches the RANGE validator except ones already known
// to be in [10, 500]. Dead defensive code on a SelectBox; left uncovered.
