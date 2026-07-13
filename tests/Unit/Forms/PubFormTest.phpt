<?php

namespace Test;

use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';
$pubs = $container->getByType(\App\Model\PubModel::class);
$users = $container->getByType(\App\Model\UserModel::class);
$parametres = $container->getByType(\Tulinkry\Services\ParameterService::class);

function validPubForm($pubs, $users, $parametres): \App\Forms\Front\PubForm
{
	$form = new \App\Forms\Front\PubForm($pubs, $users, $parametres);
	$form['whole_name']->setValue('Hospoda U Testu');
	$form['type']->setValue(['0']);
	$form['name']->setValue('U Testu');
	return $form;
}

test('whole_name, type and name filled in produces no errors', function () use ($pubs, $users, $parametres) {
	$form = validPubForm($pubs, $users, $parametres);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('missing whole_name is rejected', function () use ($pubs, $users, $parametres) {
	$form = validPubForm($pubs, $users, $parametres);
	$form['whole_name']->setValue('');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

test('missing type selection is rejected', function () use ($pubs, $users, $parametres) {
	$form = validPubForm($pubs, $users, $parametres);
	$form['type']->setValue([]);
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

test('missing name is rejected', function () use ($pubs, $users, $parametres) {
	$form = validPubForm($pubs, $users, $parametres);
	$form['name']->setValue('');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});
