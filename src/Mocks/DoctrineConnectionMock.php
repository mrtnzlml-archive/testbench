<?php declare(strict_types = 1);

namespace Testbench\Mocks;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Kdyby\Doctrine\Connection;
use Kdyby\Doctrine\Dbal\BatchImport\Helpers;
use LogicException;
use Nette\DI\Container;
use Testbench\ContainerFactory;
use Testbench\DatabasesRegistry;
use Testbench\Providers\IDatabaseProvider;
use Tester\Assert;
use Tester\Environment;
use Throwable;
use Zenify\DoctrineMigrations\Configuration\Configuration;

/**
 * @method onConnect(DoctrineConnectionMock $self)
 */
class DoctrineConnectionMock extends Connection implements IDatabaseProvider
{

	private $__testbench_databaseName;

	public $onConnect = [];

	public function connect(): void
	{
		if (parent::connect()) {
			$this->onConnect($this);
		}
	}

	public function __construct(
		array $params,
		DBAL\Driver $driver,
		?DBAL\Configuration $config = null,
		?Common\EventManager $eventManager = null
	)
	{
		$container = ContainerFactory::create(false);
		$this->onConnect[] = function (DoctrineConnectionMock $connection) use ($container): void {
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
		parent::__construct($params, $driver, $config, $eventManager);
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

		if ($config['migrations'] === true) {
			if (class_exists(Configuration::class)) {
				/** @var Configuration $migrationsConfig */
				$migrationsConfig = $container->getByType(Configuration::class);
				$migrationsConfig->__construct($container, $connection);
				$migrationsConfig->registerMigrationsFromDirectory($migrationsConfig->getMigrationsDirectory());
				$migration = new Migration($migrationsConfig);
				$migration->migrate($migrationsConfig->getLatestVersion());
			}
		}

		if ($persistent === false) {
			register_shutdown_function(function () use ($connection, $container): void {
				$this->__testbench_database_drop($connection, $container);
			});
		}
	}

	/**
	 * @internal
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_create($connection, Container $container): void
	{
		$connection->exec("CREATE DATABASE {$this->__testbench_databaseName}");
		$this->__testbench_database_change($connection, $container);
	}

	/**
	 * @internal
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_change($connection, Container $container): void
	{
		if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$connection->exec("USE {$this->__testbench_databaseName}");
		} else {
			$this->__testbench_database_connect($connection, $container, $this->__testbench_databaseName);
		}
	}

	/**
	 * @internal
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_drop($connection, Container $container): void
	{
		if (!$connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->__testbench_database_connect($connection, $container);
		}
		$connection->exec("DROP DATABASE IF EXISTS {$this->__testbench_databaseName}");
	}

	/**
	 * @internal
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_connect($connection, Container $container, $databaseName = null): void
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === null) {
			$dbname = $container->parameters['testbench']['dbname'];
			if ($dbname) {
				$databaseName = $dbname;
			} elseif ($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
				$databaseName = 'postgres';
			} else {
				throw new LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}

		$connection->close();
		$connection->__construct(
			['dbname' => $databaseName] + $connection->getParams(),
			$connection->getDriver(),
			$connection->getConfiguration(),
			$connection->getEventManager()
		);
		$connection->connect();
	}

}
