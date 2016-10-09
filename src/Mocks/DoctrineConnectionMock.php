<?php

namespace Testbench\Mocks;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;

/**
 * @method onConnect(DoctrineConnectionMock $self)
 */
class DoctrineConnectionMock extends \Kdyby\Doctrine\Connection implements \Testbench\Providers\IDatabaseProvider
{

	private $__testbench_databaseName;

	public $onConnect = [];

	public function connect()
	{
		if (parent::connect()) {
			$this->onConnect($this);
		}
	}

	public function __construct(
		array $params,
		DBAL\Driver $driver,
		DBAL\Configuration $config = NULL,
		Common\EventManager $eventManager = NULL
	) {
		$container = \Testbench\ContainerFactory::create(FALSE);
		$this->onConnect[] = function (DoctrineConnectionMock $connection) use ($container) {
			if ($this->__testbench_databaseName !== NULL) { //already initialized (needed for pgsql)
				return;
			}
			try {
				$this->__testbench_database_setup($connection, $container);
			} catch (\Exception $e) {
				\Tester\Assert::fail($e->getMessage());
			}
		};
		parent::__construct($params, $driver, $config, $eventManager);
	}

	/** @internal */
	public function __testbench_database_setup($connection, \Nette\DI\Container $container)
	{
		$this->__testbench_databaseName = 'db_tests_' . getmypid();

		$this->__testbench_database_drop($connection, $container);
		$this->__testbench_database_create($connection, $container);

		$config = $container->parameters['testbench'];

		foreach ($config['sqls'] as $file) {
			\Kdyby\Doctrine\Dbal\BatchImport\Helpers::loadFromFile($connection, $file);
		}

		if ($config['migrations'] === TRUE) {
			if (class_exists(\Zenify\DoctrineMigrations\Configuration\Configuration::class)) {
				/** @var \Zenify\DoctrineMigrations\Configuration\Configuration $migrationsConfig */
				$migrationsConfig = $container->getByType(\Zenify\DoctrineMigrations\Configuration\Configuration::class);
				$migrationsConfig->__construct($container, $connection);
				$migrationsConfig->registerMigrationsFromDirectory($migrationsConfig->getMigrationsDirectory());
				$migration = new \Doctrine\DBAL\Migrations\Migration($migrationsConfig);
				$migration->migrate($migrationsConfig->getLatestVersion());
			}
		}

		register_shutdown_function(function () use ($connection, $container) {
			$this->__testbench_database_drop($connection, $container);
		});
	}

	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_create($connection, \Nette\DI\Container $container)
	{
		$connection->exec("CREATE DATABASE {$this->__testbench_databaseName}");
		if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$connection->exec("USE {$this->__testbench_databaseName}");
		} else {
			$this->__testbench_database_connect($connection, $container, $this->__testbench_databaseName);
		}
	}

	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_drop($connection, \Nette\DI\Container $container)
	{
		if (!$connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->__testbench_database_connect($connection, $container);
		}
		$connection->exec("DROP DATABASE IF EXISTS {$this->__testbench_databaseName}");
	}

	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_connect($connection, \Nette\DI\Container $container, $databaseName = NULL)
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === NULL) {
			$dbname = $container->parameters['testbench']['dbname'];
			if ($dbname) {
				$databaseName = $dbname;
			} elseif ($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
				$databaseName = 'postgres';
			} else {
				throw new \LogicException('You should setup existing database name using testbench:dbname option.');
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
