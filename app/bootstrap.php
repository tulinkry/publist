<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

//$configurator->setDebugMode(true); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../vendor/tulinkry' )
	->addDirectory(__DIR__ . '/../vendor/olicek' )
	->register();


$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/menu.neon');
$configurator->addConfig(__DIR__ . '/config/header.neon');
$configurator->addConfig(__DIR__ . '/config/parameters.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon', Nette\Configurator::AUTO);


Kdyby\Replicator\Container::register();


$container = $configurator->createContainer();

return $container;
