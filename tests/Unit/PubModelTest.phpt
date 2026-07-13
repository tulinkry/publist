<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../bootstrap.php';
seedDatabase($container);

$db = $container->getByType(\Nette\Database\Explorer::class);

test('isValidCoordinate() enforces GPS coordinate ranges', function () {
	Assert::true(\App\Model\PubModel::isValidCoordinate(50.0, 14.0));
	Assert::true(\App\Model\PubModel::isValidCoordinate(90, 180));
	Assert::true(\App\Model\PubModel::isValidCoordinate(-90, -180));
	Assert::false(\App\Model\PubModel::isValidCoordinate(90.1, 14.0));
	Assert::false(\App\Model\PubModel::isValidCoordinate(50.0, 180.1));
	Assert::false(\App\Model\PubModel::isValidCoordinate(-90.1, 14.0));
	Assert::false(\App\Model\PubModel::isValidCoordinate(50.0, -180.1));
});

test('distance() is 0 for the same point and roughly correct for a known offset', function () use ($db) {
	// haversine formula; a known ~1 degree-latitude separation at the
	// equator is roughly 111km (generous tolerance since the source
	// constant 6372.795 is the mean earth radius, not the equatorial one).
	$pub = $db->table('pubs')->get(1);
	Assert::same(0.0, \App\Model\PubModel::distance($pub, $pub->latitude, $pub->longitude));

	$oneDegreeAway = \App\Model\PubModel::distance($pub, $pub->latitude + 1, $pub->longitude);
	Assert::true($oneDegreeAway > 100_000 && $oneDegreeAway < 120_000);
});

test('recompute() averages the ratio (value/max) across every filled criterion', function () use ($db) {
	// drop seedDatabase()'s own blank rating first so it isn't averaged in.
	$db->query('DELETE FROM ratings');
	$db->table('ratings')->insert([
		'pub_id' => 1,
		'user_id' => 1,
		'date' => strtotime('2026-01-01 00:00:00'),
		'calculated' => 1,
		'wine_criteria' => 8.0,
		'food_criteria' => 6.0,
		'toalets_criteria' => 4.0,
		'service_criteria' => 10.0,
		'interier_criteria' => 2.0,
		'exterier_criteria' => 2.0,
		'overall_criteria' => 6.0,
		'food_price_criteria' => 5.0,
	]);

	$pub = $db->table('pubs')->get(1);
	$recomputed = \App\Model\PubModel::recompute($pub);

	// Each criterion is weighted at 1 (all *_WEIGHT constants are 1), and
	// there's no beer rating on this row, so it's a plain mean of the 8
	// filled ratios.
	$expectedRatio = (
		8.0 / \App\Model\PubModel::WINE_MAX
		+ 6.0 / \App\Model\PubModel::FOOD_MAX
		+ 4.0 / \App\Model\PubModel::TOALETS_MAX
		+ 10.0 / \App\Model\PubModel::SERVICE_MAX
		+ 2.0 / \App\Model\PubModel::INTERIER_MAX
		+ 2.0 / \App\Model\PubModel::EXTERIER_MAX
		+ 6.0 / \App\Model\PubModel::OVERALL_MAX
		+ 5.0 / \App\Model\PubModel::FOOD_PRICE_MAX
	) / 8;

	Assert::equal($expectedRatio, $recomputed['mark']);
	Assert::equal(8.0 / \App\Model\PubModel::WINE_MAX, $recomputed['wineMark']);
	Assert::same(1, $recomputed['markVoted']);
});

test('recompute() does not divide by zero when every criterion is NULL', function () use ($db) {
	// Regression: $ratingMax in singleRating() could be 0 if nothing is set.
	$db->query('DELETE FROM ratings');
	$db->table('ratings')->insert(['pub_id' => 1, 'user_id' => 1, 'date' => strtotime('2026-01-01'), 'calculated' => 1]);
	$pub = $db->table('pubs')->get(1);
	Assert::noError(fn () => \App\Model\PubModel::recompute($pub));
});
