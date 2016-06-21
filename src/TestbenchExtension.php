<?php

namespace Testbench;

class TestbenchExtension extends \Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->compiler->getContainerBuilder();
		$testbenchConfig = $this->getConfig();
		if (!isset($testbenchConfig['url'])) {
			$testbenchConfig['url'] = 'http://test.bench/';
		}
		$builder->parameters[$this->name] = $testbenchConfig;

		$this->prepareDoctrine();
		$this->prepareNetteDatabase($builder);

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
			$builder->addDefinition($this->prefix('presenterMock'))->setClass('Testbench\Mocks\PresenterMock');
		}
	}

	/**
	 * 'wrapperClass' is not a service!
	 */
	private function prepareDoctrine()
	{
		$doctrineConnectionSectionKeys = ['dbname' => NULL, 'driver' => NULL, 'connection' => NULL];
		/** @var \Nette\DI\CompilerExtension $extension */
		foreach ($this->compiler->getExtensions('Kdyby\Doctrine\DI\OrmExtension') as $extension) {
			if (array_intersect_key($extension->config, $doctrineConnectionSectionKeys)) {
				$extension->config['wrapperClass'] = 'Testbench\Mocks\DoctrineConnectionMock';
			} else {
				foreach ($extension->config as $sectionName => $sectionConfig) {
					if (is_array($sectionConfig) && array_intersect_key($sectionConfig, $doctrineConnectionSectionKeys)) {
						$extension->config[$sectionName]['wrapperClass'] = 'Testbench\Mocks\DoctrineConnectionMock';
					}
				}
			}
		}
	}

	private function prepareNetteDatabase(\Nette\DI\ContainerBuilder $builder)
	{
		$ndbConnectionSectionKeys = ['dsn' => NULL, 'user' => NULL, 'password' => NULL];
		/** @var \Nette\DI\CompilerExtension $extension */
		foreach ($this->compiler->getExtensions('Nette\Bridges\DatabaseDI\DatabaseExtension') as $extension) {
			if (array_intersect_key($extension->config, $ndbConnectionSectionKeys)) {
				$extensionConfig = $extension->config;
				$definitionName = $extension->name . '.default.connection';
				$builder->getDefinition($definitionName)
					->setClass('Testbench\Mocks\NetteDatabaseConnectionMock', [
						$extensionConfig['dsn'],
						$extensionConfig['user'],
						$extensionConfig['password'],
						$extensionConfig['options'],
					]);
			} else {
				foreach ($extension->config as $sectionName => $sectionConfig) {
					$definitionName = $extension->name . '.' . $sectionName . '.connection';
					$builder->getDefinition($definitionName)
						->setClass('Testbench\Mocks\NetteDatabaseConnectionMock', [
							$sectionConfig['dsn'],
							$sectionConfig['user'],
							$sectionConfig['password'],
							$sectionConfig['options'],
						]);
				}
			}
		}
	}

}
