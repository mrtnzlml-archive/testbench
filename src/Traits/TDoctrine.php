<?php

namespace Testbench;

trait TDoctrine
{
	
	/** @return \Nette\DI\Container */
	abstract function getDIContainer();

	/**
	 * @return \Kdyby\Doctrine\EntityManager
	 */
	protected function getEntityManager()
	{
		$container = $this->getDIContainer();
		/** @var Mocks\DoctrineConnectionMock $connection */
		$connection = $container->getByType('Doctrine\DBAL\Connection');
		if (!$connection instanceof Mocks\DoctrineConnectionMock) {
			$serviceNames = $container->findByType('Doctrine\DBAL\Connection');
			throw new \LogicException(sprintf(
				'The service %s should be instance of Testbench\Mocks\DoctrineConnectionMock, to allow lazy schema initialization.',
				reset($serviceNames)
			));
		}
		return $container->getByType('Kdyby\Doctrine\EntityManager');
	}

}
