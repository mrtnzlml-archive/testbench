<?php

namespace Testbench;

trait TDoctrine
{

	/** @var \Nette\DI\Container */
	private $__testbench_container;

	/** @internal */
	private function __testbench_createContainer()
	{
		if (!class_exists('Doctrine\DBAL\Connection')) {
			\Tester\Environment::skip('TDoctrine trait supports only Doctrine at this moment.');
		}

		$container = \Testbench\ContainerFactory::create(FALSE);

		/** @var Mocks\ConnectionMock $db */
		$db = $container->getByType('Doctrine\DBAL\Connection');
		if (!$db instanceof Mocks\ConnectionMock) {
			$serviceNames = $container->findByType('Doctrine\DBAL\Connection');
			throw new \LogicException(sprintf(
				'The service %s should be instance of Ant\Tests\ConnectionMock, to allow lazy schema initialization.',
				reset($serviceNames)
			));
		}

		return $container;
	}

	/**
	 * @internal
	 * @return \Nette\DI\Container
	 */
	private function __testbench_getContainer()
	{
		if ($this->__testbench_container === NULL) {
			$this->__testbench_container = $this->__testbench_createContainer();
		}
		return $this->__testbench_container;
	}

	/**
	 * @return \Kdyby\Doctrine\EntityManager
	 */
	protected function getEntityManager()
	{
		$em = $this->__testbench_getContainer()->getByType('Kdyby\Doctrine\EntityManager');
		$em->getConnection()->connect();
		return $em;
	}

}
