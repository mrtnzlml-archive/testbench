<?php

use Tester\Assert;

class NdbComponentWithDatabaseAccess extends \Nette\Application\UI\Control
{

	public function __construct(\Nette\Database\Context $context)
	{
		parent::__construct();

		$connection = $context->getConnection();
		preg_match('~.*dbname=([a-z0-9_-]+)~i', $connection->getDsn(), $matches);
		if ($connection->getSupplementalDriver() instanceof \Nette\Database\Drivers\MySqlDriver) {
			Assert::match('testbench_initial', $matches[1]);
			Assert::match('db_tests_' . getmypid(), $connection->query('SELECT DATABASE();')->fetchPairs()[0]);
		} else {
			Assert::same('db_tests_' . getmypid(), $matches[1]);
		}
	}

	public function render()
	{
		$this->template->render(__DIR__ . '/Component.latte');
	}

}
