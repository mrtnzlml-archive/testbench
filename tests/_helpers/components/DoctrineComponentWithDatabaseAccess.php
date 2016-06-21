<?php

use Tester\Assert;

class DoctrineComponentWithDatabaseAccess extends \Nette\Application\UI\Control
{

	public function __construct(\Kdyby\Doctrine\EntityManager $entityManager)
	{
		parent::__construct();

		$connection = $entityManager->getConnection();
		Assert::type('Testbench\Mocks\ConnectionMock', $connection); //not a service (listeners will not work)!
		Assert::false($connection->isConnected());
		Assert::count(1, $connection->onConnect);
		if ($connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySqlPlatform) {
			Assert::match('testbench_initial', $connection->getDatabase());
			Assert::match('db_tests_' . getmypid(), $connection->query('SELECT DATABASE();')->fetchColumn());
		} else {
			Assert::same('db_tests_' . getmypid(), $connection->getDatabase());
		}
	}

	public function render()
	{
		$this->template->render(__DIR__ . '/Component.latte');
	}

}
