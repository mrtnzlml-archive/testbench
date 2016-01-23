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
		$configurator->onCompile[] = function ($_, \Nette\DI\Compiler $compiler) {
			$compiler->addExtension('testbench', new TestbenchExtension);
			$consoleExtension = 'Kdyby\Console\DI\ConsoleExtension';
			if (class_exists($consoleExtension) && isset($compiler->config['extensions']) && !isset($compiler->config['extensions']['console'])) {
				$compiler->addExtension('console', new \Kdyby\Console\DI\ConsoleExtension);
			}
		};

		$configurator->setTempDirectory(\Testbench\Bootstrap::$tempDir); // shared container for performance purposes
		$configurator->setDebugMode(FALSE);

		if (is_callable(\Testbench\Bootstrap::$onBeforeContainerCreate)) {
			call_user_func_array(\Testbench\Bootstrap::$onBeforeContainerCreate, [$configurator]);
		}

		return $configurator->createContainer();
	}

}
