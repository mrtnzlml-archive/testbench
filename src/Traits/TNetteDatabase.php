<?php declare(strict_types = 1);

namespace Testbench;

use LogicException;
use Nette\Database\Context;

trait TNetteDatabase
{

	protected function getContext()
	{
		$container = ContainerFactory::create(false);
		/** @var Mocks\NetteDatabaseConnectionMock $connection */
		$connection = $container->getByType('Nette\Database\Connection');
		if (!$connection instanceof Mocks\NetteDatabaseConnectionMock) {
			$serviceNames = $container->findByType('Nette\Database\Connection');
			throw new LogicException(sprintf(
				'The service %s should be instance of Testbench\Mocks\NetteDatabaseConnectionMock, to allow lazy schema initialization.',
				reset($serviceNames)
			));
		}
		/** @var Context $context */
		$context = $container->getByType('Nette\Database\Context');
		return $context;
	}

}
