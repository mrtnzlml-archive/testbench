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

	public function testDatabaseCreation()
	{
		$connection = $this->getEntityManager()->getConnection();
		$connection->connect();
		Assert::match('db_tests_' . getmypid(), $connection->getDatabase());
	}

}

(new DatabaseBench)->run();
