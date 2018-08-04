<?php declare(strict_types = 1);

namespace Testbench;

use Exception;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\FakeSession\DI\FakeSessionExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\Statement;
use Nette\SmartObject;

/**
 * @internal
 */
class ContainerFactory
{

	use SmartObject;

	private static $container;

	private function __construct()
	{
		//Cannot be initialized
	}

	final public static function create($new = false, $config = []): Container
	{
		if ($new || self::$container === null) {
			$configurator = new Configurator();
			$configurator->addParameters($config);

			$configurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) use ($config): void {
				$compiler->addConfig($config);
				$compiler->addExtension('testbench', new TestbenchExtension());
				self::registerAdditionalExtension($compiler, 'fakeSession', new FakeSessionExtension());
				if (class_exists('Kdyby\Console\DI\ConsoleExtension')) {
					self::registerAdditionalExtension($compiler, 'console', new ConsoleExtension());
				}
			};

			$configurator->setTempDirectory(Bootstrap::$tempDir); // shared container for performance purposes
			$configurator->setDebugMode(false);

			if (is_callable(Bootstrap::$onBeforeContainerCreate)) {
				call_user_func_array(Bootstrap::$onBeforeContainerCreate, [$configurator]);
			}

			self::$container = $configurator->createContainer();
		}
		return self::$container;
	}

	/**
	 * Register extension if not registered by user.
	 */
	private static function registerAdditionalExtension(Compiler $compiler, $name, $newExtension): void
	{
		$extensions = [];
		$config = $compiler->getConfig();
		foreach ($config['extensions'] ?? [] as $extension) {
			if (is_string($extension)) {
				$extensions[] = $extension;
			} elseif ($extension instanceof Statement) {
				$extensions[] = $extension->getEntity();
			}
		}
		if (!in_array(get_class($newExtension), $extensions)) {
			$compiler->addExtension($name, $newExtension);
		}
	}

	final public function __clone()
	{
		throw new Exception('Clone is not allowed');
	}

	final public function __wakeup(): void
	{
		throw new Exception('Unserialization is not allowed');
	}

}
