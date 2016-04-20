<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TCompiledContainerTest extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;

	public function testGetContainer()
	{
		Assert::type('Nette\DI\Container', $container = $this->getContainer());
		Assert::same($container, $this->getContainer());
	}

	public function testGetService()
	{
		Assert::type('Nette\Application\Application', $this->getService('Nette\Application\Application'));
	}

	public function testRefreshContainer()
	{
		Assert::type('Nette\DI\Container', $container = $this->getContainer());
		Assert::same($container, $this->getContainer());
		$refreshedContainer = $this->refreshContainer();
		Assert::type('Nette\DI\Container', $refreshedContainer);
		Assert::notSame($container, $refreshedContainer);
	}

	public function testRefreshContainerWithConfig()
	{
		$refreshedContainer1 = $this->refreshContainer();
		Assert::error(function () use ($refreshedContainer1) {
			$refreshedContainer1->parameters['test'];
		}, 'E_NOTICE', 'Undefined index: test');

		$refreshedContainer2 = $this->refreshContainer([
			'test' => [
				'xxx' => ['yyy'],
			],
		]);
		Assert::same(['xxx' => ['yyy']], $refreshedContainer2->parameters['test']);

		Assert::notSame($refreshedContainer1, $refreshedContainer2);
	}

	public function testRunLevels()
	{
		putenv('RUNLEVEL=0');
		Assert::same(0, (int)getenv('RUNLEVEL'));
		putenv('RUNLEVEL=5'); //do not skip
		$this->markTestAsSlow();
		Assert::same(\Testbench::FINE, (int)getenv('RUNLEVEL'));
		putenv('RUNLEVEL=10');
		$this->markTestAsVerySlow();
		Assert::same(\Testbench::SLOW, (int)getenv('RUNLEVEL'));
		putenv('RUNLEVEL=7');
		$this->changeRunLevel(7);
		Assert::same(7, (int)getenv('RUNLEVEL'));
		putenv('RUNLEVEL=0');
		$this->markTestAsSlow(FALSE);
		Assert::same(0, (int)getenv('RUNLEVEL'));
	}

}

(new TCompiledContainerTest)->run();
