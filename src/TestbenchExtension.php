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

		//$builder->addDefinition($this->prefix('applicationRequestMock'))->setClass('Testbench\ApplicationRequestMock');
		$builder->addDefinition($this->prefix('presenterMock'))->setClass('Testbench\PresenterMock');
	}

	public function beforeCompile()
	{
		$builder = $this->compiler->getContainerBuilder();
		foreach ($builder->findByType('Testbench\PresenterMock') as $name => $definition) {
			$builder->removeDefinition($name);
			$builder->addDefinition($name)->setClass($definition->class);
		}
	}

}
