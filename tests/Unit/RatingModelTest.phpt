<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$names = [
	'year' => 'roků', 'month' => 'měsíců', 'day' => 'dní',
	'hour' => 'hodin', 'minute' => 'minut', 'second' => 'sekund',
];

test('formatDuration() picks the largest whole unit that fits', function () use ($names) {
	Assert::same('2 roků', \App\Model\RatingModel::formatDuration(2 * 365 * 24 * 60 * 60 + 1, $names));
	Assert::same('3 měsíců', \App\Model\RatingModel::formatDuration(3 * 30 * 24 * 60 * 60 + 1, $names));
	Assert::same('5 dní', \App\Model\RatingModel::formatDuration(5 * 24 * 60 * 60 + 1, $names));
	Assert::same('4 hodin', \App\Model\RatingModel::formatDuration(4 * 60 * 60 + 1, $names));
	Assert::same('30 minut', \App\Model\RatingModel::formatDuration(30 * 60 + 1, $names));
	Assert::same('45 sekund', \App\Model\RatingModel::formatDuration(45, $names));
});

test('formatDuration() falls through to seconds for zero (regression: switch($num) vs switch(true))', function () use ($names) {
	Assert::same('0 sekund', \App\Model\RatingModel::formatDuration(0, $names));
});
