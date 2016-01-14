<?php

namespace Test;

use Ant\Tests\ControlMock;
use Ant\Tests\PresenterMock;
use Ant\Tests\TComponent;
use Nette\Application\IPresenter;
use Tester;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TComponentTest extends Tester\TestCase
{

	use TComponent;

	public function testAttachToPresenter()
	{
		$control = new ControlMock;
		Assert::exception(function () use ($control) {
			$control->lookup(IPresenter::class);
		}, 'Nette\InvalidStateException', "Component '' is not attached to 'Nette\\Application\\IPresenter'.");
		$this->attachToPresenter($control);
		Assert::type(PresenterMock::class, $control->lookup(IPresenter::class));
	}

}

(new TComponentTest)->run();
