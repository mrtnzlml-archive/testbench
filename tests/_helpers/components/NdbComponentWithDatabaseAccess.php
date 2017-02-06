<?php

use Tester\Assert;

class NdbComponentWithDatabaseAccess extends \Nette\Application\UI\Control
{

	public function __construct(\Nette\Database\Context $context)
	{
		parent::__construct();

		$connection = $context->getConnection();
		$returnActualDatabaseName = function () use ($connection) { //getSupplementalDriver is performing first connect (behaves lazy)
			preg_match('~.*dbname=([a-z0-9_-]+)~i', $connection->getDsn(), $matches);
			return $matches[1];
		};
		if ($connection->getSupplementalDriver() instanceof \Nette\Database\Drivers\MySqlDriver) {
			Assert::match('information_schema', $returnActualDatabaseName());
			Assert::match('_testbench_' . getenv(\Tester\Environment::THREAD), $connection->query('SELECT DATABASE();')->fetchPairs()[0]);
		} else {
			Assert::same('_testbench_' . getenv(\Tester\Environment::THREAD), $returnActualDatabaseName());
		}
	}

	public function render()
	{
		$this->template->render(__DIR__ . '/Component.latte');
	}

}
