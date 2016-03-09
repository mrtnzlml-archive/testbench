<?php

namespace Testbench;

trait TCompiledContainer
{

	/** @var \Nette\DI\Container */
	private $_container;

	/**
	 * @return \Nette\DI\Container
	 */
	protected function getContainer()
	{
		if ($this->_container === NULL) {
			$this->_container = $this->createContainer();
		}
		return $this->_container;
	}

	protected function getService($class)
	{
		$container = $this->getContainer();
		return $container->getByType($class);
	}

	protected function refreshContainer()
	{
		$this->_container = $this->createContainer();
		return $this->_container;
	}

	/** @internal */
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

	protected function changeRunLevel($testSpeed = \Testbench::FINE)
	{
		if ((int)getenv('RUNLEVEL') < $testSpeed) {
			\Tester\Environment::skip(
				"Required runlevel '$testSpeed' but current runlevel is '" . (int)getenv('RUNLEVEL') . "' (higher runlevel means slower tests)\n" .
				"You can run this test with environment variable: 'RUNLEVEL=$testSpeed vendor/bin/run-tests ...'\n"
			);
		}
	}

}
