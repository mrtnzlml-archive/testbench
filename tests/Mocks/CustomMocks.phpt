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
		Assert::type('Testbench\PresenterMock', $this->getService('Testbench\PresenterMock'));
		Assert::type('Testbench\CustomPresenterMock', $this->getService('Testbench\PresenterMock'));

		Assert::notSame('Testbench\PresenterMock', get_class($this->getService('Testbench\PresenterMock')));
		Assert::same('Testbench\CustomPresenterMock', get_class($this->getService('Testbench\PresenterMock')));
	}

}

(new CustomMocks)->run();
