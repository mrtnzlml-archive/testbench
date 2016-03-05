<?php

namespace Testbench;

class TestbenchExtension extends \Nette\DI\CompilerExtension
{
	static private $connectionSectionKeys = [
		'host' => NULL, 'unix_socket' => NULL, 'driver' => NULL
	];

	public function loadConfiguration()
	{
		$builder = $this->compiler->getContainerBuilder();
		$builder->parameters[$this->name] = $this->getConfig();

		/** @var \Nette\DI\CompilerExtension $extension */
		foreach ($this->compiler->getExtensions('Kdyby\Doctrine\DI\OrmExtension') as $extension) {
			if (array_intersect_key($extension->config, self::$connectionSectionKeys)) {
				$extension->config['wrapperClass'] = 'Testbench\ConnectionMock';
			} else {
				foreach ($extension->config as $sectionName => $sectionConfig) {
					if (is_array($sectionConfig) && array_intersect_key($sectionConfig, self::$connectionSectionKeys)) {
						$extension->config[$sectionName]['wrapperClass'] = 'Testbench\ConnectionMock';
					}
				}
			}
		}

		//TODO: $builder->addDefinition($this->prefix('applicationRequestMock'))->setClass('Testbench\ApplicationRequestMock');
	}

	public function beforeCompile()
	{
		$builder = $this->compiler->getContainerBuilder();

		if ($builder->hasDefinition($this->prefix('presenterMock'))) { //custom testbench.presenterMock implementation
			//workaround because of Application\UI\Presenter descendant (presenterMock needs to be reattached)
			$mockReplacement = $builder->getDefinition($this->prefix('presenterMock'))->getClass();
			$builder->removeDefinition($this->prefix('presenterMock'));
			$builder->addDefinition($this->prefix('presenterMock'))->setClass($mockReplacement);
		} else {
			$builder->addDefinition($this->prefix('presenterMock'))->setClass('Testbench\PresenterMock');
		}
	}

}
