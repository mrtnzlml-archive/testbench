<?php

namespace Test;

use Nette\Application\IPresenter;
use Testbench;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TComponentTest extends \Tester\TestCase
{

	use Testbench\TComponent;

	public function testAttachToPresenter()
	{
		$control = new Testbench\ControlMock;
		Assert::exception(function () use ($control) {
			$control->lookup(IPresenter::class);
		}, 'Nette\InvalidStateException', "Component '' is not attached to 'Nette\\Application\\IPresenter'.");
		$this->attachToPresenter($control);
		Assert::type(Testbench\PresenterMock::class, $control->lookup(IPresenter::class));
	}

}

(new TComponentTest)->run();
