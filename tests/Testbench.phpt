<?php

namespace Tests;

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class Testbench extends \Tester\TestCase
{

	public function testConstants()
	{
		Assert::same(0, \Testbench::QUICK);
		Assert::same(5, \Testbench::FINE);
		Assert::same(10, \Testbench::SLOW);
	}

}

(new Testbench)->run();
