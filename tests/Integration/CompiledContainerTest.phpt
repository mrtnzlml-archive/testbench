<?php

namespace Test;

use Ant\Tests\TCompiledContainer;
use Tester;

require __DIR__ . '/../bootstrap.php';

class CompiledContainerTest extends Tester\TestCase
{

	use TCompiledContainer;

	/** @var \Nette\DI\Container */
	private $dic;
	/** @var \Nette\DI\Container */
	private $tmpDic;

	public function __construct()
	{
		$this->dic = $this->getContainer();
	}

	public function setUp()
	{
		$this->tmpDic = $this->getContainer();
	}

	public function testContainer()
	{
		Tester\Assert::type('Nette\DI\Container', $this->dic);
//		Tester\Assert::true($this->isContainerCreated());
	}

	public function testRefreshContainer()
	{
		$old = $this->tmpDic;
		$new = $this->getContainer();
		Tester\Assert::same($old, $new);

		$old = $this->tmpDic;
		$service = $this->getService('Nette\Http\Session');
		$service->start();
		$this->refreshContainer();
		$new = $this->getContainer();
		Tester\Assert::notSame($old, $new);
	}

//	public function testCreateConfiguration()
//	{
//		/** @var \Nette\Configurator $config */
//		$config = $this->parentDoCreateConfiguration();
//		Tester\Assert::type('Nette\Configurator', $config);
//	}

	public function testService()
	{
		/** @var \Nette\Http\Session $service */
		$service = $this->getService('Nette\Http\Session');
		$service->start();
		Tester\Assert::type('Nette\Http\Session', $service);
	}

//	public function tearDown()
//	{
//		Tester\Assert::true($this->isContainerCreated());
//		Tester\Assert::true($this->tearDownContainer());
//		Tester\Assert::false($this->isContainerCreated());
//		Tester\Assert::false($this->tearDownContainer());
//		Tester\Assert::false($this->isContainerCreated());
//	}

}

(new CompiledContainerTest)->run();
