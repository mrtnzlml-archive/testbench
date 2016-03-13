<?php

namespace Test;

use Nette\Database\Drivers\MySqlDriver;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @multiple 50
 */
class NetteDatabaseBench extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;
	use \Testbench\TNetteDatabase;

	public function testDatabaseSqls()
	{
		$this->changeRunLevel(\Testbench::SLOW);

		/** @var \Nette\Database\Connection $connection */
		$connection = $this->getContext()->getConnection();
		$result = $connection->query('SELECT * FROM table_1')->fetchAssoc('id=');
		preg_match('~.*dbname=([a-z0-9_-]+)~i', $connection->getDsn(), $matches);

		Assert::same([
			1 => ['id' => 1, 'column_1' => 'value_1', 'column_2' => 'value_2'],
			['id' => 2, 'column_1' => 'value_1', 'column_2' => 'value_2'],
			['id' => 3, 'column_1' => 'value_1', 'column_2' => 'value_2'],
		], $result);

		if ($connection->getSupplementalDriver() instanceof MySqlDriver) {
			Assert::match('testbench_initial', $matches[1]);
		} else {
			Assert::same('db_tests_' . getmypid(), $matches[1]);
		}
	}

}

(new NetteDatabaseBench)->run();
