<?php

namespace Testbench;

trait TCompiledContainer
{

	/** @var \Nette\DI\Container */
	private $container;

	/**
	 * @return \Nette\DI\Container
	 */
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

	protected function refreshContainer()
	{
		$this->container = $this->createContainer();
		return $this->container;
	}

	/**
	 * @see: https://api.nette.org/2.3.8/source-Bootstrap.Configurator.php.html
	 */
	private function createContainer()
	{
		$configurator = new \Nette\Configurator();

		$configurator->setTempDirectory(TEMP_DIR); // shared container for performance purposes
		$configurator->setDebugMode(FALSE);

//		$configurator->addParameters([ //FIXME: konfigurovatelné
//			'appDir' => __DIR__ . '/../../../app',
//			'wwwDir' => __DIR__ . '/../../..',
//		]);

//		$configurator->createRobotLoader()
//			->addDirectory([
//				__DIR__ . '/../../../app',
//				__DIR__ . '/../../../administrace',
//				__DIR__ . '/../../../libs',
//				__DIR__ . '/../../../include',
//				__DIR__ . '/../../../presentation',
//			])->register();

		foreach (\Testbench\Bootstrap::$configFiles as $configFile) {
			$configurator->addConfig($configFile);
		}

		return $configurator->createContainer();
	}

}
