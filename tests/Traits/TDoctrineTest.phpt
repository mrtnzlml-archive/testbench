<?php

namespace Test;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TDoctrineTest extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;
	use \Testbench\TDoctrine;

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
		/** @var \Testbench\ConnectionMock $connection */
		$connection = $this->getEntityManager()->getConnection();
		if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
			Assert::match('testbench_initial', $connection->getDatabase());
		} else {
			Assert::same('db_tests_' . getmypid(), $connection->getDatabase());
		}
	}

	public function testDatabaseSqls()
	{
		/** @var \Testbench\ConnectionMock $connection */
		$connection = $this->getEntityManager()->getConnection();
		$result = $connection->query('SELECT * FROM table_1')->fetchAll();
		if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
			Assert::same([
				['id' => '1', 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => '2', 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => '3', 'column_1' => 'value_1', 'column_2' => 'value_2'],
			], $result);
			Assert::match('testbench_initial', $connection->getDatabase());
		} else {
			Assert::same([
				['id' => 1, 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => 2, 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => 3, 'column_1' => 'value_1', 'column_2' => 'value_2'],
			], $result);
			Assert::same('db_tests_' . getmypid(), $connection->getDatabase());
		}
	}

}

(new TDoctrineTest)->run();
