<?php declare(strict_types = 1);

namespace Testbench\Mocks;

use LogicException;
use Nette\Database\Connection;
use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Drivers\PgSqlDriver;
use Nette\Database\Helpers;
use Nette\DI\Container;
use Nette\Reflection\ClassType;
use Testbench\ContainerFactory;
use Testbench\DatabasesRegistry;
use Testbench\Providers\IDatabaseProvider;
use Tester\Assert;
use Tester\Environment;
use Throwable;

/**
 * @method onConnect(NetteDatabaseConnectionMock $connection)
 */
class NetteDatabaseConnectionMock extends Connection implements IDatabaseProvider
{

	/** @var string */
	private $__testbench_databaseName;

	public function __construct(
		string $dsn,
		?string $user = null,
		?string $password = null,
		?array $options = null
	)
	{
		$container = ContainerFactory::create(false);
		$this->onConnect[] = function (NetteDatabaseConnectionMock $connection) use ($container): void {
			if ($this->__testbench_databaseName !== null) { //already initialized (needed for pgsql)
				return;
			}
			try {
				$config = $container->parameters['testbench'];
				if ($config['shareDatabase'] === true) {
					$registry = new DatabasesRegistry();
					$dbName = $container->parameters['testbench']['dbprefix'] . getenv(Environment::THREAD);
					if ($registry->registerDatabase($dbName)) {
						$this->__testbench_database_setup($connection, $container, true);
					} else {
						$this->__testbench_databaseName = $dbName;
						$this->__testbench_database_change($connection, $container);
					}
				} else { // always create new test database
					$this->__testbench_database_setup($connection, $container);
				}
			} catch (Throwable $e) {
				Assert::fail($e->getMessage());
			}
		};
		parent::__construct($dsn, $user, $password, $options);
	}

	/** @internal */
	public function __testbench_database_setup($connection, Container $container, $persistent = false): void
	{
		$config = $container->parameters['testbench'];
		$this->__testbench_databaseName = $config['dbprefix'] . getenv(Environment::THREAD);

		$this->__testbench_database_drop($connection, $container);
		$this->__testbench_database_create($connection, $container);

		foreach ($config['sqls'] as $file) {
			Helpers::loadFromFile($connection, $file);
		}

		if ($persistent === false) {
			register_shutdown_function(function () use ($connection, $container): void {
				$this->__testbench_database_drop($connection, $container);
			});
		}
	}

	/**
	 * @internal
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbench_database_create($connection, Container $container): void
	{
		$connection->query("CREATE DATABASE {$this->__testbench_databaseName}");
		$this->__testbench_database_change($connection, $container);
	}

	/**
	 * @internal
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbench_database_change($connection, Container $container): void
	{
		if ($connection->getSupplementalDriver() instanceof MySqlDriver) {
			$connection->query("USE {$this->__testbench_databaseName}");
		} else {
			$this->__testbench_database_connect($connection, $container, $this->__testbench_databaseName);
		}
	}

	/**
	 * @internal
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbench_database_drop($connection, Container $container): void
	{
		if (!$connection->getSupplementalDriver() instanceof MySqlDriver) {
			$this->__testbench_database_connect($connection, $container);
		}
		$connection->query("DROP DATABASE IF EXISTS {$this->__testbench_databaseName}");
	}

	/**
	 * @internal
	 * @param $connection \Nette\Database\Connection
	 */
	public function __testbench_database_connect($connection, Container $container, $databaseName = null): void
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === null) {
			$dbname = $container->parameters['testbench']['dbname'];
			if ($dbname) {
				$databaseName = $dbname;
			} elseif ($connection->getSupplementalDriver() instanceof PgSqlDriver) {
				$databaseName = 'postgres';
			} else {
				throw new LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}

		$dsn = preg_replace('~dbname=[a-z0-9_-]+~i', "dbname=$databaseName", $connection->getDsn());

		$dbr = (new ClassType($connection))->getParentClass(); //:-(
		$params = $dbr->getProperty('params');
		$params->setAccessible(true);
		$params = $params->getValue($connection);

		$options = $dbr->getProperty('options');
		$options->setAccessible(true);
		$options = $options->getValue($connection);

		$connection->disconnect();
		$connection->__construct($dsn, $params[1], $params[2], $options);
		$connection->connect();
	}

}
