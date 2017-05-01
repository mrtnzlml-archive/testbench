<?php

require __DIR__ . '/../../vendor/autoload.php';

return Testbench\Bootstrap::setup(__DIR__ . '/_temp', function (Nette\Configurator $configurator) {
	$configurator->createRobotLoader()->addDirectory([
		__DIR__ . '/../app',
		__DIR__ . '/../libs',
	])->register();

	$configurator->addParameters([
		'appDir' => __DIR__ . '/../app',

		'testsDir' => __DIR__,
	]);

	$configurator->addConfig(__DIR__ . '/../app/configs/config.neon');
	$configurator->addConfig(__DIR__ . '/../app/configs/config.local.neon');
	$configurator->addConfig(__DIR__ . '/tests.neon');
});
