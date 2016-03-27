<?php

namespace Testbench;

trait TCompiledContainer
{

	/** @return \Nette\DI\Container */
	protected function getContainer()
	{
		return \Testbench\ContainerFactory::create(FALSE);
	}

	protected function getService($class)
	{
		return $this->getContainer()->getByType($class);
	}

	protected function refreshContainer()
	{
		return \Testbench\ContainerFactory::create(TRUE);
	}

	//FIXME: should be in TCompiledContainer?
	protected function changeRunLevel($testSpeed = \Testbench::FINE)
	{
		if ((int)getenv('RUNLEVEL') < $testSpeed) {
			\Tester\Environment::skip(
				"Required runlevel '$testSpeed' but current runlevel is '" . (int)getenv('RUNLEVEL') . "' (higher runlevel means slower tests)\n" .
				"You can run this test with environment variable: 'RUNLEVEL=$testSpeed vendor/bin/run-tests ...'\n"
			);
		}
	}

	protected function markTestAsSlow($really = TRUE)
	{
		$this->changeRunLevel($really ? \Testbench::FINE : \Testbench::QUICK);
	}

	protected function markTestAsVerySlow($really = TRUE)
	{
		$this->changeRunLevel($really ? \Testbench::SLOW : \Testbench::QUICK);
	}

}
