<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @multiple 100
 */
class DatabaseBench extends \Tester\TestCase
{

	use \Testbench\TDatabaseSetup;

	public function setUp()
	{
		\Tracy\Debugger::timer(getmypid());
	}

	public function tearDown()
	{
		$time = \Tracy\Debugger::timer(getmypid());
		//Assert::match('0.%d%', $time, 'Test was too slow');
	}

	public function testDatabaseCreation()
	{
		$connection = $this->getEntityManager()->getConnection();
		$result = $connection->query('SELECT * FROM table_1')->fetchAll();
		Assert::same([
			['id' => '1', 'column_1' => 'value_1', 'column_2' => 'value_2'],
			['id' => '2', 'column_1' => 'value_1', 'column_2' => 'value_2'],
			['id' => '3', 'column_1' => 'value_1', 'column_2' => 'value_2'],
		], $result);
		Assert::match('db_tests_' . getmypid(), $connection->getDatabase());
	}

}

(new DatabaseBench)->run();
