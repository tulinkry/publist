<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../bootstrap.php';
seedDatabase($container);

$pubs = $container->getByType(\App\Model\PubModel::class);
$users = $container->getByType(\App\Model\UserModel::class);
$parametres = $container->getByType(\Tulinkry\Services\ParameterService::class);

// Admin\PubForm only adds a hidden "id" field and prefills defaults from an
// existing pub (seedDatabase() inserts pub_id 1) - the whole_name/type/name
// required rules it validates against are Front\PubForm's, exercised here
// against the admin-specific constructor/edit path.

function validAdminPubForm($pubs, $users, $parametres): \App\Forms\Admin\PubForm
{
	$form = new \App\Forms\Admin\PubForm($pubs, $users, $parametres, 1);
	$form['whole_name']->setValue('Testovaci Hospoda U Testu');
	$form['type']->setValue(['0']);
	$form['name']->setValue('Testovaci Hospoda');
	return $form;
}

test('editing an existing pub with all required fields filled produces no errors', function () use ($pubs, $users, $parametres) {
	$form = validAdminPubForm($pubs, $users, $parametres);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('clearing whole_name on an edit is rejected', function () use ($pubs, $users, $parametres) {
	$form = validAdminPubForm($pubs, $users, $parametres);
	$form['whole_name']->setValue('');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});

test('clearing the short name on an edit is rejected', function () use ($pubs, $users, $parametres) {
	$form = validAdminPubForm($pubs, $users, $parametres);
	$form['name']->setValue('');
	$form->validate();
	Assert::notSame([], $form->getErrors());
});
