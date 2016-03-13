<?php

namespace Testbench;

use Nette\Database\Connection;
use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Drivers\PgSqlDriver;

trait TNetteDatabase
{

	private $__testbench_ndb_container;

	private $__testbench_ndb_databaseName;

	protected function getContext()
	{
		/** @var \Nette\Database\Context $context */
		$context = $this->__testbench_ndb_getContainer()->getByType('Nette\Database\Context');
		$connection = $context->getConnection();
		if ($connection->getPdo()) {
			//call event 'onConnect' event every time because of SQLs load
			$connection->onConnect($connection);
		} else {
			$connection->connect();
		}
		return $context;
	}

	/**
	 * @internal
	 * @return \Nette\DI\Container
	 */
	private function __testbench_ndb_getContainer()
	{
		if ($this->__testbench_ndb_container === NULL) {
			$container = \Testbench\ContainerFactory::create(FALSE);

			/** @var Connection $db */
			$db = $container->getByType('Nette\Database\Connection');
			$db->onConnect[] = function (Connection $db) use ($container) {
				if ($this->__testbench_ndb_databaseName !== NULL) {
					return;
				}

				try {
					$this->__testbench_ndb_setupDatabase($db, $container);
				} catch (\Exception $e) {
					\Tester\Assert::fail($e->getMessage());
				}
			};

			$this->__testbench_ndb_container = $container;
		}
		return $this->__testbench_ndb_container;
	}

	/** @internal */
	private function __testbench_ndb_setupDatabase(Connection $db, $container)
	{
		$this->__testbench_ndb_databaseName = 'db_tests_' . getmypid();

		$this->__testbench_ndb_dropDatabase($db);
		$this->__testbench_ndb_createDatabase($db);

		if (isset($container->parameters['testbench']['sqls'])) {
			foreach ($container->parameters['testbench']['sqls'] as $file) {
				\Nette\Database\Helpers::loadFromFile($db, $file);
			}
		}

		register_shutdown_function(function () use ($db) {
			$this->__testbench_ndb_dropDatabase($db);
		});
	}

	/** @internal */
	private function __testbench_ndb_createDatabase(Connection $db)
	{
		$db->query("CREATE DATABASE {$this->__testbench_ndb_databaseName}");
		if ($db->getSupplementalDriver() instanceof MySqlDriver) {
			$db->query("USE {$this->__testbench_ndb_databaseName}");
		} else {
			$this->__testbench_ndb_connectToDatabase($db, $this->__testbench_ndb_databaseName);
		}
	}

	/** @internal */
	private function __testbench_ndb_dropDatabase(Connection $db)
	{
		if (!$db->getSupplementalDriver() instanceof MySqlDriver) {
			$this->__testbench_ndb_connectToDatabase($db);
		}
		$db->query("DROP DATABASE IF EXISTS {$this->__testbench_ndb_databaseName}");
	}

	/** @internal */
	private function __testbench_ndb_connectToDatabase(Connection $db, $databaseName = NULL)
	{
		//connect to an existing database other than $this->_databaseName
		$container = $this->__testbench_ndb_getContainer();
		if ($databaseName === NULL) {
			$config = $container->parameters['testbench'];
			if (isset($config['dbname'])) {
				$databaseName = $config['dbname'];
			} elseif ($db->getSupplementalDriver() instanceof PgSqlDriver) {
				$databaseName = 'postgres';
			} else {
				throw new \LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}

		$dsn = preg_replace('~dbname=[a-z0-9_-]+~i', "dbname=$databaseName", $db->getDsn());

		$dbr = $db->getReflection(); //:-(
		$params = $dbr->getProperty('params');
		$params->setAccessible(TRUE);
		$params = $params->getValue($db);

		$options = $dbr->getProperty('options');
		$options->setAccessible(TRUE);
		$options = $options->getValue($db);

		$db->disconnect();
		$db->__construct($dsn, $params[1], $params[2], $options);
		$db->connect();
	}

}
