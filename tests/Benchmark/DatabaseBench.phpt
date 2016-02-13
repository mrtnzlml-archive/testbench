<?php

namespace Test;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @multiple 100
 */
class DatabaseBench extends \Tester\TestCase
{

	use \Testbench\TDoctrine;

	public function setUp()
	{
		\Tracy\Debugger::timer(getmypid());
	}

	public function tearDown()
	{
		$time = \Tracy\Debugger::timer(getmypid());
		//Assert::match('0.%d%', $time, 'Test was too slow');
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

(new DatabaseBench)->run();
