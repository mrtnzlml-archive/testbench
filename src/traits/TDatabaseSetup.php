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
	protected $databaseName;

	protected function createContainer()
	{
		if (!class_exists('Doctrine\DBAL\Connection')) {
			throw new \Nette\NotSupportedException('TDatabaseSetup trait supports only Doctrine at this moment.');
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
			if ($this->databaseName !== NULL) {
				return;
			}

			try {
				$this->setupDatabase($db);
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
		return $this->getContainer()->getByType('Kdyby\Doctrine\EntityManager');
	}

	private function setupDatabase(ConnectionMock $db)
	{
		$this->databaseName = 'db_tests_' . getmypid();

		$this->dropDatabase($db);
		$this->createDatabase($db);

		$sqls = [
			$this->container->parameters['appDir'] . '/../_zdroje/db/db_common_structure.sql',
			$this->container->parameters['appDir'] . '/../_zdroje/db/db_data_eshop.sql',
			$this->container->parameters['appDir'] . '/../_zdroje/db/changes struct/cms_innodb.sql', //FIXME: remove
		];
		foreach ($sqls as $file) {
			\Kdyby\Doctrine\Helpers::loadFromFile($db, $file);
		}

		register_shutdown_function(function () use ($db) {
			$this->dropDatabase($db);
		});
	}

	private function createDatabase(ConnectionMock $db)
	{
		$db->exec("CREATE DATABASE {$this->databaseName}");
		$this->connectToDatabase($db, $this->databaseName);
	}

	private function dropDatabase(ConnectionMock $db)
	{
		//FIXME:
		$this->connectToDatabase($db, 'cms_new'); // connect to an existing database other than $this->databaseName
		$db->exec("DROP DATABASE IF EXISTS {$this->databaseName}");
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
