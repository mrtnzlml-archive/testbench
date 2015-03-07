<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/presenters/PresenterPresenter.php';
require __DIR__ . '/ModuleModule/presenters/PresenterPresenter.php';

if (!class_exists('Tester\Assert')) {
	echo "Install Nette Tester using `composer update --dev`\n";
	exit(1);
}

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

@mkdir(__DIR__ . '/../temp');

$configurator = new Nette\Configurator;
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__ . '/../src')
	->register();

$container = $configurator->createContainer();
/** @var Nette\Application\Routers\RouteList $routeList */
$routeList = $container->getService('router');
$routeList[] = new Nette\Application\Routers\Route('<presenter>/<action>[/<id>]', 'Presenter:default');

return $container;
