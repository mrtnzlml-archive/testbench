<?php declare(strict_types = 1);

namespace Testbench;

use Kdyby\Doctrine\EntityManager;
use LogicException;

trait TDoctrine
{

	protected function getEntityManager(): EntityManager
	{
		$container = ContainerFactory::create(false);
		/** @var Mocks\DoctrineConnectionMock $connection */
		$connection = $container->getByType('Doctrine\DBAL\Connection');
		if (!$connection instanceof Mocks\DoctrineConnectionMock) {
			$serviceNames = $container->findByType('Doctrine\DBAL\Connection');
			throw new LogicException(sprintf(
				'The service %s should be instance of Testbench\Mocks\DoctrineConnectionMock, to allow lazy schema initialization.',
				reset($serviceNames)
			));
		}
		return $container->getByType('Kdyby\Doctrine\EntityManager');
	}

}
