<?php declare(strict_types = 1);

namespace Testbench;

use Nette\DI\Container;
use Testbench;
use Tester\Environment;

trait TCompiledContainer
{

	protected function getContainer(): Container
	{
		return ContainerFactory::create(false);
	}

	protected function getService($class)
	{
		return $this->getContainer()->getByType($class);
	}

	protected function refreshContainer($config = [])
	{
		return ContainerFactory::create(true, $config);
	}

	protected function changeRunLevel($testSpeed = Testbench::FINE): void
	{
		if ((int) getenv('RUNLEVEL') < $testSpeed) {
			Environment::skip(
				"Required runlevel '$testSpeed' but current runlevel is '" . (int) getenv('RUNLEVEL') . "' (higher runlevel means slower tests)\n" .
				"You can run this test with environment variable: 'RUNLEVEL=$testSpeed vendor/bin/run-tests ...'\n"
			);
		}
	}

	protected function markTestAsSlow($really = true): void
	{
		$this->changeRunLevel($really ? Testbench::FINE : Testbench::QUICK);
	}

	protected function markTestAsVerySlow($really = true): void
	{
		$this->changeRunLevel($really ? Testbench::SLOW : Testbench::QUICK);
	}

}
