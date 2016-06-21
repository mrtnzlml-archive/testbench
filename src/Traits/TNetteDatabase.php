<?php

namespace Testbench;

trait TNetteDatabase
{

	protected function getContext()
	{
		$container = \Testbench\ContainerFactory::create(FALSE);
		/** @var Mocks\NetteDatabaseConnectionMock $connection */
		$connection = $container->getByType('Nette\Database\Connection');
		if (!$connection instanceof Mocks\NetteDatabaseConnectionMock) {
			$serviceNames = $container->findByType('Nette\Database\Connection');
			throw new \LogicException(sprintf(
				'The service %s should be instance of Testbench\Mocks\NetteDatabaseConnectionMock, to allow lazy schema initialization.',
				reset($serviceNames)
			));
		}
		/** @var \Nette\Database\Context $context */
		$context = $container->getByType('Nette\Database\Context');
		return $context;
	}

}
