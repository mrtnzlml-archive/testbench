<?php

namespace Test;

use Testbench\ConnectionMock;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TDatabaseSetupTest extends \Tester\TestCase
{

	use \Testbench\TDatabaseSetup;

	public function testLazyConnection()
	{
		$container = $this->getContainer();
		$db = $container->getByType('Doctrine\DBAL\Connection');
		$db->onConnect[] = function () use ($container) {
			Assert::fail('\Testbench\ConnectionMock::$onConnect event should not be called if you do NOT need database');
		};
		\Tester\Environment::$checkAssertions = FALSE;
	}

	public function testEntityManager()
	{
		Assert::type('\Doctrine\ORM\EntityManagerInterface', $this->getEntityManager());
	}

	public function testDatabaseCreation()
	{
		Assert::same('db_tests_' . getmypid(), $this->getEntityManager()->getConnection()->getDatabase());
	}

	public function testDatabaseSqls()
	{
		/** @var ConnectionMock $connection */
		$connection = $this->getEntityManager()->getConnection();
		$result = $connection->query('SELECT * FROM table_1')->fetchAll();
		Assert::same([
			['id' => '1', 'column_1' => 'value_1', 'column_2' => 'value_2'],
			['id' => '2', 'column_1' => 'value_1', 'column_2' => 'value_2'],
			['id' => '3', 'column_1' => 'value_1', 'column_2' => 'value_2'],
		], $result);
	}

}

(new TDatabaseSetupTest)->run();
