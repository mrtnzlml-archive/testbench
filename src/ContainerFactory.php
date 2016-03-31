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

			$configurator->onCompile[] = function (\Nette\Configurator $configurator, \Nette\DI\Compiler $compiler) {
				$compiler->addExtension('testbench', new \Testbench\TestbenchExtension);
				self::registerAdditionalExtension($compiler, 'fakeSession', new \Kdyby\FakeSession\DI\FakeSessionExtension);
				if (class_exists('Kdyby\Console\DI\ConsoleExtension')) {
					self::registerAdditionalExtension($compiler, 'console', new \Kdyby\Console\DI\ConsoleExtension);
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

	/**
	 * Register extension if not registered by user.
	 */
	private static function registerAdditionalExtension(\Nette\DI\Compiler $compiler, $name, $newExtension)
	{
		$extensions = [];
		foreach (isset($compiler->config['extensions']) ? $compiler->config['extensions'] : [] as $extension) {
			if (is_string($extension)) {
				$extensions[] = $extension;
			} elseif ($extension instanceof \Nette\DI\Statement) {
				$extensions[] = $extension->getEntity();
			}
		}
		if ($extensions && !in_array(get_class($newExtension), $extensions)) {
			$compiler->addExtension($name, $newExtension);
		}
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
