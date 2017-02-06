<?php

namespace Tests\Traits;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Tester\Assert;

require getenv('BOOTSTRAP');

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
		$db->onConnect[] = function () {
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
		/** @var \Testbench\Mocks\DoctrineConnectionMock $connection */
		$connection = $this->getEntityManager()->getConnection();
		if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
			Assert::match('information_schema', $connection->getDatabase());
			Assert::match('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->query('SELECT DATABASE();')->fetchColumn());
		} else {
			Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->getDatabase());
		}
	}

	public function testDatabaseSqls()
	{
		/** @var \Testbench\Mocks\DoctrineConnectionMock $connection */
		$connection = $this->getEntityManager()->getConnection();
		$result = $connection->query('SELECT * FROM table_1')->fetchAll();
		if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
			Assert::same([
				['id' => '1', 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => '2', 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => '3', 'column_1' => 'value_1', 'column_2' => 'value_2'],
				[
					'id' => '4',
					'column_1' => 'from_migration_1',
					'column_2' => 'from_migration_2',
				],
			], $result);
			Assert::match('information_schema', $connection->getDatabase());
		} else {
			Assert::same([
				['id' => 1, 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => 2, 'column_1' => 'value_1', 'column_2' => 'value_2'],
				['id' => 3, 'column_1' => 'value_1', 'column_2' => 'value_2'],
			], $result);
			Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->getDatabase());
		}
	}

	public function testDatabaseConnectionReplacementInApp()
	{
		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $this->getService(\Kdyby\Doctrine\EntityManager::class);
		new \DoctrineComponentWithDatabaseAccess($em); //tests inside
		//app is not using onConnect from Testbench but it has to connect to the mock database
	}

}

(new TDoctrineTest)->run();
