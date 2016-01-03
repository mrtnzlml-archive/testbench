<?php

namespace Ant\Tests;

use Nette;

trait TCompiledContainer
{

	/** @var Nette\DI\Container */
	private $container;

	protected function getContainer()
	{
		if ($this->container === NULL) {
			$this->container = $this->createContainer();
		}
		return $this->container;
	}

	protected function getService($class)
	{
		$container = $this->getContainer();
		return $container->getByType($class);
	}

	protected function createContainer()
	{
		$configurator = new Nette\Configurator();

		$configurator->setTempDirectory(__DIR__ . '/../'); // shared container for performance purposes
		$configurator->setDebugMode(FALSE);

		$configurator->addParameters([
			'appDir' => __DIR__ . '/../../../app',
			'wwwDir' => __DIR__ . '/../../..',
		]);

		$configurator->createRobotLoader()
			->addDirectory([
				__DIR__ . '/../../../app',
				__DIR__ . '/../../../administrace',
				__DIR__ . '/../../../libs',
				__DIR__ . '/../../../include',
				__DIR__ . '/../../../presentation',
			])->register();

		$configurator->addConfig(__DIR__ . '/../../../app/config/config.neon');
		$configurator->addConfig(__DIR__ . '/../../../app/config/config.local.neon');
		$configurator->addConfig(__DIR__ . '/../../tests.neon');

		return $configurator->createContainer();
	}

}
