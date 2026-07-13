<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

// None of these three declare any setRequired()/addRule() at all - just a
// free-form textarea (PubTypesForm/TodoForm) or a multi-upload with no
// server-side constraints (SliderForm) - grouped together to pin down that
// an empty submission always validates cleanly.

test('Admin\PubTypesForm: empty textarea validates with no errors', function () {
	$form = new \App\Forms\Admin\PubTypesForm(__DIR__ . '/../../temp/nonexistent-types.neon');
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('Admin\TodoForm: empty textarea validates with no errors', function () {
	$form = new \App\Forms\Admin\TodoForm(__DIR__ . '/../../temp');
	$form->validate();
	Assert::same([], $form->getErrors());
});

test('Admin\SliderForm: no uploaded files validates with no errors', function () {
	$form = new \App\Forms\Admin\SliderForm(['sliderSrc' => 'images/slider', 'slider' => ['lg' => 'lg', 'md' => 'md', 'sm' => 'sm', 'xs' => 'xs']]);
	$form->validate();
	Assert::same([], $form->getErrors());
});
