<?php

namespace Tests\Mocks;

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
		Assert::type('Testbench\Mocks\PresenterMock', $this->getService('Testbench\Mocks\PresenterMock'));
		Assert::type('Testbench\CustomPresenterMock', $this->getService('Testbench\Mocks\PresenterMock'));

		Assert::notSame('Testbench\Mocks\PresenterMock', get_class($this->getService('Testbench\Mocks\PresenterMock')));
		Assert::same('Testbench\CustomPresenterMock', get_class($this->getService('Testbench\Mocks\PresenterMock')));
	}

}

(new CustomMocks)->run();
