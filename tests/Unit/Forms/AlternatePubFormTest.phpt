<?php

namespace Test;

use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';
$pubs = $container->getByType(\App\Model\PubModel::class);
$users = $container->getByType(\App\Model\UserModel::class);
$parametres = $container->getByType(\Tulinkry\Services\ParameterService::class);

// AlternatePubForm swaps Front\PubForm's GpsPicker "coords" control for a
// plain lat/lng text pair with an explicit RANGE rule (-90..90 / -180..180)
// on top of the inherited whole_name/type/name required rules.

function validAlternatePubForm($pubs, $users, $parametres): \App\Forms\Front\AlternatePubForm
{
	$form = new \App\Forms\Front\AlternatePubForm($pubs, $users, $parametres);
	$form['whole_name']->setValue('Hospoda U Testu');
	$form['type']->setValue(['0']);
	$form['name']->setValue('U Testu');
	$form['lat']->setValue('50.083');
	$form['lng']->setValue('14.423');
	return $form;
}

test('valid lat/lng within range produces no errors', function () use ($pubs, $users, $parametres) {
	$form = validAlternatePubForm($pubs, $users, $parametres);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('lat above 90 is rejected', function () use ($pubs, $users, $parametres) {
	$form = validAlternatePubForm($pubs, $users, $parametres);
	$form['lat']->setValue('90.1');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

test('lat below -90 is rejected', function () use ($pubs, $users, $parametres) {
	$form = validAlternatePubForm($pubs, $users, $parametres);
	$form['lat']->setValue('-90.1');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

test('lng above 180 is rejected', function () use ($pubs, $users, $parametres) {
	$form = validAlternatePubForm($pubs, $users, $parametres);
	$form['lng']->setValue('180.1');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

test('lng below -180 is rejected', function () use ($pubs, $users, $parametres) {
	$form = validAlternatePubForm($pubs, $users, $parametres);
	$form['lng']->setValue('-180.1');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

test('missing lat is rejected', function () use ($pubs, $users, $parametres) {
	$form = validAlternatePubForm($pubs, $users, $parametres);
	$form['lat']->setValue('');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});
