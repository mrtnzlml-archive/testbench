<?php

namespace Testbench;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;

trait TDoctrine
{

	/** @var string|NULL */
	private $__testbench_databaseName;

	/** @var \Nette\DI\Container */
	private $__testbench_container;

	/** @internal */
	private function __testbench_createContainer()
	{
		if (!class_exists('Doctrine\DBAL\Connection')) {
			\Tester\Environment::skip('TDoctrine trait supports only Doctrine at this moment.');
		}

		$container = \Testbench\ContainerFactory::create(FALSE);

		/** @var ConnectionMock $db */
		$db = $container->getByType('Doctrine\DBAL\Connection');
		if (!$db instanceof ConnectionMock) {
			$serviceNames = $container->findByType('Doctrine\DBAL\Connection');
			throw new \LogicException(sprintf(
				'The service %s should be instance of Ant\Tests\ConnectionMock, to allow lazy schema initialization.',
				reset($serviceNames)
			));
		}

		$db->onConnect[] = function (ConnectionMock $db) use ($container) {
			if ($this->__testbench_databaseName !== NULL) {
				return;
			}

			try {
				$this->setupDatabase($db, $container);
			} catch (\Exception $e) {
				\Tester\Assert::fail($e->getMessage());
			}
		};

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

	/** @internal */
	private function setupDatabase(ConnectionMock $db, $container)
	{
		$this->__testbench_databaseName = 'db_tests_' . getmypid();

		$this->dropDatabase($db);
		$this->createDatabase($db);

		if (isset($container->parameters['testbench']['sqls'])) {
			foreach ($container->parameters['testbench']['sqls'] as $file) {
				\Kdyby\Doctrine\Helpers::loadFromFile($db, $file);
			}
		}

		register_shutdown_function(function () use ($db) {
			$this->dropDatabase($db);
		});
	}

	/** @internal */
	private function createDatabase(ConnectionMock $db)
	{
		$db->exec("CREATE DATABASE {$this->__testbench_databaseName}");
		if ($db->getDatabasePlatform() instanceof MySqlPlatform) {
			$db->exec("USE {$this->__testbench_databaseName}");
		} else {
			$this->connectToDatabase($db, $this->__testbench_databaseName);
		}
	}

	/** @internal */
	private function dropDatabase(ConnectionMock $db)
	{
		if (!$db->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->connectToDatabase($db);
		}
		$db->exec("DROP DATABASE IF EXISTS {$this->__testbench_databaseName}");
	}

	/** @internal */
	private function connectToDatabase(ConnectionMock $db, $databaseName = NULL)
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === NULL) {
			$config = $this->__testbench_getContainer()->parameters['testbench'];
			if (isset($config['dbname'])) {
				$databaseName = $config['dbname'];
			} elseif ($db->getDatabasePlatform() instanceof PostgreSqlPlatform) {
				$databaseName = 'postgres';
			} else {
				throw new \LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}

		$db->close();
		$db->__construct(
			['dbname' => $databaseName] + $db->getParams(),
			$db->getDriver(),
			$db->getConfiguration(),
			$db->getEventManager()
		);
		$db->connect();
	}

}
