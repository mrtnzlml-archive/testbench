<?php

namespace Test;

use Nette\Database\Drivers\MySqlDriver;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TNetteDatabaseTest extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;
	use \Testbench\TNetteDatabase;

	public function testLazyConnection()
	{
		$container = $this->getContainer();
		$db = $container->getByType('Nette\Database\Connection');
		$db->onConnect[] = function () use ($container) {
			Assert::fail('\Nette\Database\Connection::$onConnect event should not be called if you do NOT need database');
		};
		\Tester\Environment::$checkAssertions = FALSE;
	}

	public function testEntityManager()
	{
		Assert::type('Nette\Database\Context', $this->getContext());
	}

	public function testDatabaseCreation()
	{
		/** @var \Nette\Database\Connection $connection */
		$connection = $this->getContext()->getConnection();
		preg_match('~.*dbname=([a-z0-9_-]+)~i', $connection->getDsn(), $matches);
		if ($connection->getSupplementalDriver() instanceof MySqlDriver) {
			Assert::match('testbench_initial', $matches[1]);
		} else {
			Assert::same('db_tests_' . getmypid(), $matches[1]);
		}
	}

	public function testDatabaseSqls()
	{
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

(new TNetteDatabaseTest)->run();
