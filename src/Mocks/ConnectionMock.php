<?php

namespace Testbench\Mocks;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Testbench\Mocks;

/**
 * @method onConnect(ConnectionMock $self)
 */
class ConnectionMock extends \Kdyby\Doctrine\Connection
{

	public $onConnect = [];

	/** @var string|NULL */
	private $__testbench_databaseName;

	private $container;

	public function __construct(
		array $params,
		DBAL\Driver $driver,
		DBAL\Configuration $config = NULL,
		Common\EventManager $eventManager = NULL
	) {
		parent::__construct($params, $driver, $config, $eventManager);
		$this->container = $container = \Testbench\ContainerFactory::create(FALSE);
		$this->onConnect[] = function (Mocks\ConnectionMock $db) use ($container) {
			if ($this->__testbench_databaseName !== NULL) {
				return;
			}

			try {
				$this->setupDatabase($db, $container);
			} catch (\Exception $e) {
				\Tester\Assert::fail($e->getMessage());
			}
		};
	}

	/** @internal */
	private function setupDatabase(Mocks\ConnectionMock $db, $container)
	{
		$this->__testbench_databaseName = 'db_tests_' . getmypid();

		$this->dropDatabase($db);
		$this->createDatabase($db);

		if (isset($container->parameters['testbench']['sqls'])) {
			foreach ($container->parameters['testbench']['sqls'] as $file) {
				\Kdyby\Doctrine\Dbal\BatchImport\Helpers::loadFromFile($db, $file);
			}
		}

		register_shutdown_function(function () use ($db) {
			$this->dropDatabase($db);
		});
	}

	/** @internal */
	private function createDatabase(Mocks\ConnectionMock $db)
	{
		$db->exec("CREATE DATABASE {$this->__testbench_databaseName}");
		if ($db->getDatabasePlatform() instanceof MySqlPlatform) {
			$db->exec("USE {$this->__testbench_databaseName}");
		} else {
			$this->connectToDatabase($db, $this->__testbench_databaseName);
		}
	}

	/** @internal */
	private function dropDatabase(Mocks\ConnectionMock $db)
	{
		if (!$db->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->connectToDatabase($db);
		}
		$db->exec("DROP DATABASE IF EXISTS {$this->__testbench_databaseName}");
	}

	/** @internal */
	private function connectToDatabase(Mocks\ConnectionMock $db, $databaseName = NULL)
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === NULL) {
			$config = $this->container->parameters['testbench'];
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

	public function connect()
	{
		if (parent::connect()) {
			$this->onConnect($this);
		}
	}

}
