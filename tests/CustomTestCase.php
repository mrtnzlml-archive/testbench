<?php

namespace Test;

use Nette;
use Tester;

class CustomTestCase extends Tester\TestCase
{

	use PresenterTester;

	protected function doCreateConfiguration()
	{
		$config = new Nette\Configurator();
		// shared compiled container for faster tests
		$config->setTempDirectory(dirname(TEMP_DIR));
		return $config;
	}

}
