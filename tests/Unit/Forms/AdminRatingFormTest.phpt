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
$beerRatings = $container->getByType(\App\Model\BeerRatingModel::class);

// Admin\RatingForm looks up an existing rating (seedDatabase() inserts
// rating_id 1) and delegates all field/rule construction to
// App\Forms\Front\RatingForm - same rules as the front-end form, just
// exercised through the edit-an-existing-rating constructor path.

function newAdminRatingForm($pubs, $ratings, $beers, $beerRatings): \App\Forms\Admin\RatingForm
{
	$form = new \App\Forms\Admin\RatingForm($pubs, $ratings, $beers, 1, $beerRatings);
	foreach (['interierCriteria', 'exterierCriteria', 'serviceCriteria', 'overallCriteria', 'foodPriceCriteria'] as $field) {
		$form['mandatory'][$field]->setValue(1);
	}
	return $form;
}

test('editing the seeded rating with mandatory criteria filled validates with no errors', function () use ($pubs, $ratings, $beers, $beerRatings) {
	$form = newAdminRatingForm($pubs, $ratings, $beers, $beerRatings);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('a beer row left without a selected brand is rejected', function () use ($pubs, $ratings, $beers, $beerRatings) {
	$form = newAdminRatingForm($pubs, $ratings, $beers, $beerRatings);
	$form['optional']['beers']->createOne();
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

// winePrice's RANGE rule can't be exercised here either - see the longer
// note in RatingFormTest.phpt: ChoiceControl::getValue() always re-checks
// the item list and returns null for anything outside it.
