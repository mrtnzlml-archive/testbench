<?php

namespace Testbench;

/**
 * @internal
 */
class ContainerFactory extends \Nette\Object
{

	private static $container;

	private function __construct()
	{
		//Cannot be initialized
	}

	/**
	 * @return \Nette\DI\Container
	 */
	final public static function create($new = FALSE)
	{
		if ($new || self::$container === NULL) {
			$configurator = new \Nette\Configurator();

			$configurator->onCompile[] = function ($_, \Nette\DI\Compiler $compiler) {
				$compiler->addExtension('testbench', new \Testbench\TestbenchExtension);
				$compiler->addExtension('fakeSession', new \Kdyby\FakeSession\DI\FakeSessionExtension);
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

			self::$container = $configurator->createContainer();
		}
		return self::$container;
	}

	final public function __clone()
	{
		throw new \Exception('Clone is not allowed');
	}

	final public function __wakeup()
	{
		throw new \Exception('Unserialization is not allowed');
	}

}
