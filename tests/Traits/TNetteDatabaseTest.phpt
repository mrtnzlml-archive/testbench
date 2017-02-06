<?php

namespace Tests\Traits;

use Nette\Database\Drivers\MySqlDriver;
use Tester\Assert;

require getenv('BOOTSTRAP');

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
		$db->onConnect[] = function () {
			Assert::fail('\Nette\Database\Connection::$onConnect event should not be called if you do NOT need database');
		};
		\Tester\Environment::$checkAssertions = FALSE;
	}

	public function testContext()
	{
		Assert::type('Nette\Database\Context', $this->getContext());
	}

	public function testDatabaseCreation()
	{
		/** @var \Nette\Database\Connection $connection */
		$connection = $this->getContext()->getConnection();
		$returnActualDatabaseName = function () use ($connection) { //getSupplementalDriver is performing first connect (behaves lazy)
			preg_match('~.*dbname=([a-z0-9_-]+)~i', $connection->getDsn(), $matches);
			return $matches[1];
		};
		if ($connection->getSupplementalDriver() instanceof MySqlDriver) {
			Assert::match('information_schema', $returnActualDatabaseName());
			Assert::match('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->query('SELECT DATABASE();')->fetchPairs()[0]);
		} else {
			Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $returnActualDatabaseName());
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
			Assert::match('information_schema', $matches[1]);
		} else {
			Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $matches[1]);
		}
	}

	public function testDatabaseConnectionReplacementInApp()
	{
		/** @var \Nette\Database\Context $context */
		$context = $this->getService(\Nette\Database\Context::class);
		new \NdbComponentWithDatabaseAccess($context); //tests inside
		//app is not using onConnect from Testbench but it has to connect to the mock database
	}

	public function testConnectionMockSetup()
	{
		/** @var \Testbench\Mocks\NetteDatabaseConnectionMock $connection */
		$connection = $this->getService(\Testbench\Mocks\NetteDatabaseConnectionMock::class);

		$dbr = (new \Nette\Reflection\ClassType($connection))->getParentClass(); //:-(
		$params = $dbr->getProperty('params');
		$params->setAccessible(TRUE);
		$params = $params->getValue($connection);

		$options = $dbr->getProperty('options');
		$options->setAccessible(TRUE);
		$options = $options->getValue($connection);

		Assert::count(3, $params);
		if ($connection->getSupplementalDriver() instanceof MySqlDriver) {
			Assert::match('mysql:host=%a%;dbname=information_schema', $params[0]);
		} else {
			Assert::match('pgsql:host=%a%;dbname=postgres', $params[0]);
		}

		Assert::same([
			'PDO::MYSQL_ATTR_COMPRESS' => TRUE,
			'lazy' => TRUE,
		], $options);
	}

}

(new TNetteDatabaseTest)->run();
