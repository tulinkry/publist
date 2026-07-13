<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator();

//$configurator->setDebugMode(true); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

// exposes real env vars as %env.DB_DSN% etc. in config.neon; config.local.neon
// (gitignored, deploy-provided) still wins when both are set
$configurator->addStaticParameters(['env' => getenv() + [
	'EMAIL_PORT' => '143',
	'EMAIL_ARGUMENTS' => 'novalidate-cert',
]]);

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/menu.neon');
$configurator->addConfig(__DIR__ . '/config/header.neon');
$configurator->addConfig(__DIR__ . '/config/parameters.neon');
if (is_file(__DIR__ . '/config/config.local.neon')) {
	$configurator->addConfig(__DIR__ . '/config/config.local.neon');
}


Kdyby\Replicator\Container::register();


$container = $configurator->createContainer();

return $container;
