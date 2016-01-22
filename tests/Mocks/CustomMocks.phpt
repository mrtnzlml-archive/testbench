<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class CustomMocks extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;

	public function testCustomMocks()
	{
//		Assert::type('Testbench\CustomApplicationRequestMock', $this->getService('Testbench\ApplicationRequestMock'));
		Assert::type('Testbench\CustomPresenterMock', $this->getService('Testbench\PresenterMock'));
	}

}

(new CustomMocks)->run();
