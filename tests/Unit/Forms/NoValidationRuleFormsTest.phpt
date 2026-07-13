<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../../fixtures/presenter-test-helpers.php';

$container = require __DIR__ . '/../../bootstrap.php';
seedDatabase($container);

$pubs = $container->getByType(\App\Model\PubModel::class);
$users = $container->getByType(\App\Model\UserModel::class);
$descriptions = $container->getByType(\App\Model\DescriptionModel::class);
$pub = $pubs->item(1);

// None of these three forms declare a single setRequired()/addRule() call -
// Front\DescriptionForm's only field is a plain textarea, and both ImageForm
// classes (Admin\ImageForm is a literally empty subclass of Front\ImageForm)
// only have file uploads with no size/type constraints enforced at the form
// level. Grouped together to pin down that a completely empty submission is
// expected to validate cleanly for all three.

test('Front\DescriptionForm: empty text validates with no errors', function () use ($descriptions, $users, $pub) {
	$form = new \App\Forms\Front\DescriptionForm($descriptions, $users, $pub, null);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('Front\ImageForm: no uploaded files validates with no errors', function () use ($pubs, $pub) {
	$form = new \App\Forms\Front\ImageForm($pubs, $pub);
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('Admin\ImageForm: no uploaded files validates with no errors', function () use ($pubs, $pub) {
	$form = new \App\Forms\Admin\ImageForm($pubs, $pub);
	$form->validate();
	Assert::same([], $form->getErrors());
});
