<?php

namespace Testbench\Mocks;

use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Drivers\PgSqlDriver;

/**
 * @method onConnect(NetteDatabaseConnectionMock $connection)
 */
class NetteDatabaseConnectionMock extends \Nette\Database\Connection implements \Testbench\Providers\IDatabaseProvider
{

	private $__testbench_databaseName;

	public function __construct($dsn, $user = NULL, $password = NULL, array $options = NULL)
	{
		$container = \Testbench\ContainerFactory::create(FALSE);

		if ($container->parameters['testbench']['setupDatabase']) {
			$this->onConnect[] = function (NetteDatabaseConnectionMock $connection) use ($container) {
				if ($this->__testbench_databaseName !== NULL) { //already initialized (needed for pgsql)
					return;
				}
				try {
					$this->__testbench_database_setup($connection, $container);
				} catch (\Exception $e) {
					\Tester\Assert::fail($e->getMessage());
				}
			};
		}

		parent::__construct($dsn, $user, $password, $options);
	}

	/** @internal */
	public function __testbench_database_setup($connection, \Nette\DI\Container $container)
	{
		$this->__testbench_databaseName = 'db_tests_' . getmypid();

		$this->__testbench_database_drop($connection, $container);
		$this->__testbench_database_create($connection, $container);

        foreach ($container->parameters['testbench']['sqls'] as $file) {
            \Nette\Database\Helpers::loadFromFile($connection, $file);
        }

		register_shutdown_function(function () use ($connection, $container) {
			$this->__testbench_database_drop($connection, $container);
		});
	}

	/**
	 * @internal
	 *
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbench_database_create($connection, \Nette\DI\Container $container)
	{
		$connection->query("CREATE DATABASE {$this->__testbench_databaseName}");
		if ($connection->getSupplementalDriver() instanceof MySqlDriver) {
			$connection->query("USE {$this->__testbench_databaseName}");
		} else {
			$this->__testbench_database_connect($connection, $container, $this->__testbench_databaseName);
		}
	}

	/**
	 * @internal
	 *
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbench_database_drop($connection, \Nette\DI\Container $container)
	{
		if (!$connection->getSupplementalDriver() instanceof MySqlDriver) {
			$this->__testbench_database_connect($connection, $container);
		}
		$connection->query("DROP DATABASE IF EXISTS {$this->__testbench_databaseName}");
	}

	/**
	 * @internal
	 *
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbench_database_connect($connection, \Nette\DI\Container $container, $databaseName = NULL)
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === NULL) {
			$dbName = $container->parameters['testbench']['dbname'];
			if ($dbName) {
				$databaseName = $dbName;
			} elseif ($connection->getSupplementalDriver() instanceof PgSqlDriver) {
				$databaseName = 'postgres';
			} else {
				throw new \LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}

		$dsn = preg_replace('~dbname=[a-z0-9_-]+~i', "dbname=$databaseName", $connection->getDsn());

		$dbr = (new \Nette\Reflection\ClassType($connection))->getParentClass(); //:-(
		$params = $dbr->getProperty('params');
		$params->setAccessible(TRUE);
		$params = $params->getValue($connection);

		$options = $dbr->getProperty('options');
		$options->setAccessible(TRUE);
		$options = $options->getValue($connection);

		$connection->disconnect();
		$connection->__construct($dsn, $params[1], $params[2], $options);
		$connection->connect();
	}

}
