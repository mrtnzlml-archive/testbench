<?php

namespace Testbench;

class TestbenchExtension extends \Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->compiler->getContainerBuilder();
		$builder->parameters[$this->name] = $this->getConfig();

		/** @var \Nette\DI\CompilerExtension $extension */
		foreach ($this->compiler->getExtensions('Kdyby\Doctrine\DI\OrmExtension') as $name => $extension) {
			$extension->config['wrapperClass'] = 'Testbench\ConnectionMock';
		}
	}

}
