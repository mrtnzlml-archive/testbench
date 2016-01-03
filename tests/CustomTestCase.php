<?php

namespace Test;

use Nette;
use Tester;

/**
 * @deprecated
 */
class CustomTestCase extends Tester\TestCase
{

	use PresenterTester {
		PresenterTester::createContainer as parentCreateContainer;
		PresenterTester::doCreateConfiguration as parentDoCreateConfiguration;
	}

	protected function doCreateConfiguration()
	{
		$config = new Nette\Configurator();
		// shared compiled container for faster tests
		$config->setTempDirectory(dirname(TEMP_DIR));
		return $config;
	}

	protected function createContainer(array $configs = [])
	{
		$container = $this->parentCreateContainer($configs);
		$routeList = $container->getService('router');
		$routeList[] = new Nette\Application\Routers\Route('/x/y[[[/<presenter>]/<action>][/<id>]]', 'Presenter:default');
		return $container;
	}

}
