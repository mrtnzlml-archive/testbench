<?php

namespace Testbench;

trait TDatabaseSetup
{

	use TCompiledContainer {
		createContainer as parentCreateContainer;
	}

	/**
	 * @var string|NULL
	 */
	protected $_databaseName;

	protected function createContainer()
	{
		if (!class_exists('Doctrine\DBAL\Connection')) {
			\Tester\Environment::skip('TDatabaseSetup trait supports only Doctrine at this moment.');
		}

		$container = $this->parentCreateContainer();

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
			if ($this->_databaseName !== NULL) {
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
	 * @return \Kdyby\Doctrine\EntityManager
	 */
	protected function getEntityManager()
	{
		$em = $this->getContainer()->getByType('Kdyby\Doctrine\EntityManager');
		$em->getConnection()->connect();
		return $em;
	}

	private function setupDatabase(ConnectionMock $db, $container)
	{
		$this->_databaseName = 'db_tests_' . getmypid();

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

	private function createDatabase(ConnectionMock $db)
	{
		$db->exec("CREATE DATABASE {$this->_databaseName}");
		$this->connectToDatabase($db, $this->_databaseName);
	}

	private function dropDatabase(ConnectionMock $db)
	{
		//connect to an existing database other than $this->_databaseName
		$this->connectToDatabase($db, $this->getContainer()->parameters['testbench']['dbname']);
		$db->exec("DROP DATABASE IF EXISTS {$this->_databaseName}");
	}

	private function connectToDatabase(ConnectionMock $db, $databaseName)
	{
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
