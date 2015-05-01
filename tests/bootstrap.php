<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Bootstrap.php';
require __DIR__ . '/presenters/PresenterPresenter.php';
require __DIR__ . '/ModuleModule/presenters/PresenterPresenter.php';

$configurator = Test\Bootstrap::setup(__DIR__);
$configurator->createRobotLoader()
	->addDirectory(__DIR__ . '/../src')
	->register();

$container = $configurator->createContainer();

/** @var Nette\Application\Routers\RouteList $routeList */
$routeList = $container->getService('router');
$routeList[] = new Nette\Application\Routers\Route('<presenter>/<action>[/<id>]', 'Presenter:default');

return $container;
