<?php

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();

if (!defined('WWW_DIR')) {
	define('WWW_DIR', __DIR__ . '/../www');
}

$configurator = new Nette\Configurator();
$configurator->setDebugMode(false);
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__ . '/../app')
	->register();

// zero-credential defaults so the container compiles standalone
$configurator->addStaticParameters(['env' => getenv() + [
	'DB_DSN' => 'sqlite::memory:',
	'DB_USER' => null,
	'DB_PASSWORD' => null,
	'GOOGLE_MAPS_API_KEY' => '',
	'EMAIL_SERVER' => 'localhost',
	'EMAIL_PORT' => 143,
	'EMAIL_ARGUMENTS' => 'novalidate-cert',
	'EMAIL_USER' => '',
	'EMAIL_PASSWORD' => '',
]]);

$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../app/config/menu.neon');
$configurator->addConfig(__DIR__ . '/../app/config/header.neon');
$configurator->addConfig(__DIR__ . '/../app/config/parameters.neon');
if (is_file(__DIR__ . '/../app/config/config.local.neon')) {
	$configurator->addConfig(__DIR__ . '/../app/config/config.local.neon');
}

// app/bootstrap.php registers this the same way (a one-time global extension
// method on Nette\Forms\Container, not a DI-compiled extension) - without it,
// any form using addDynamic() (e.g. App\Forms\Front\RatingForm's beer rows)
// fails with "Call to undefined method ...Container::addDynamic()".
Kdyby\Replicator\Container::register();

return $configurator->createContainer();
