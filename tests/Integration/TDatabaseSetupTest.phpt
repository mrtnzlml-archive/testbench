<?php

namespace Test;

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
		$connection = $this->getEntityManager()->getConnection();
		$connection->connect();
		Assert::match('db_tests_' . getmypid(), $connection->getDatabase());
	}

}

(new TDatabaseSetupTest)->run();
