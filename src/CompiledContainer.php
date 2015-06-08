<?php

namespace Test;

use Nette;
use Nette\DI\Container;
use Nette\Http\Session;

trait CompiledContainer
{

	/** @var Container */
	private $container;

	/** @return Container */
	protected function getContainer()
	{
		if ($this->container === NULL) {
			$this->container = $this->createContainer();
		}
		return $this->container;
	}

	/** @return bool */
	protected function isContainerCreated()
	{
		return $this->container !== NULL;
	}

	protected function refreshContainer()
	{
		$container = $this->getContainer();

		/** @var Session $session */
		if (($session = $container->getByType('Nette\Http\Session')) && $session->isStarted()) {
			$session->close();
		}

		$this->container = new $container();
		$this->container->initialize();
	}

	/** @return bool */
	protected function tearDownContainer()
	{
		if ($this->container) {
			/** @var Session $session */
			$session = $this->getContainer()->getByType('Nette\Http\Session');
			if ($session->isStarted()) {
				$session->destroy();
			}
			$this->container = NULL;
			return TRUE;
		}
		return FALSE;
	}

	/** @return Nette\Configurator */
	protected function doCreateConfiguration()
	{
		$config = new Nette\Configurator();
		$config->addParameters([
			'rootDir' => $rootDir = dirname(dirname(dirname(dirname(__DIR__)))),
			'appDir' => $rootDir . '/app',
			'wwwDir' => $rootDir . '/www',
		]);
		// shared compiled container for faster tests
		$config->setTempDirectory(dirname(TEMP_DIR));
		return $config;
	}

	/**
	 * @param array $configs
	 *
	 * @return Container
	 */
	protected function createContainer(array $configs = [])
	{
		$config = $this->doCreateConfiguration();
		foreach ($configs as $file) {
			$config->addConfig($file);
		}
		/** @var Container $container */
		$container = $config->createContainer();
		return $container;
	}

	/**
	 * @param string $type
	 *
	 * @return object
	 */
	public function getService($type)
	{
		$container = $this->getContainer();
		if ($object = $container->getByType($type, FALSE)) {
			return $object;
		}
		return $container->createInstance($type);
	}

}
