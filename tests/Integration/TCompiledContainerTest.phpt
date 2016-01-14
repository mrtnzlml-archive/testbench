<?php

namespace Test;

use Ant\Tests\TCompiledContainer;
use Tester;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TCompiledContainerTest extends Tester\TestCase
{

	use TCompiledContainer;

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

}

(new TCompiledContainerTest)->run();
