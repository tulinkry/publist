<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

// SignInForm and DialogSignInForm declare the exact same two required
// fields, just on top of different rendering base classes (plain
// Nette\Application\UI\Form vs Tulinkry's Bootstrap-styled Form) - covered
// together rather than duplicating the same assertions twice.

$classes = [
	\App\Forms\Front\SignInForm::class,
	\App\Forms\Front\DialogSignInForm::class,
];

foreach ($classes as $class) {
	test("$class: valid email + password produces no errors", function () use ($class) {
		$form = new $class();
		$form['email']->setValue('user@example.com');
		$form['password']->setValue('secret');
		$form->validate();
		Assert::same([], $form->getErrors());
	});

	test("$class: missing email is rejected", function () use ($class) {
		$form = new $class();
		$form['password']->setValue('secret');
		$form->validate();
		Assert::notSame([], $form->getErrors());
	});

	test("$class: missing password is rejected", function () use ($class) {
		$form = new $class();
		$form['email']->setValue('user@example.com');
		$form->validate();
		Assert::notSame([], $form->getErrors());
	});
}
