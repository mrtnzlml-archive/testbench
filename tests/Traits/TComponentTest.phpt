<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TComponentTest extends \Tester\TestCase
{

	use \Testbench\TComponent;

	public function testAttachToPresenter()
	{
		$control = new \Testbench\ControlMock;
		Assert::exception(function () use ($control) {
			$control->lookup('Nette\Application\IPresenter');
		}, 'Nette\InvalidStateException', "Component '' is not attached to 'Nette\\Application\\IPresenter'.");
		$this->attachToPresenter($control);
		Assert::type('Testbench\CustomPresenterMock', $control->lookup('Nette\Application\IPresenter'));
	}

	public function testRender()
	{
		$control = new \Component;
		$this->checkRenderOutput($control, '<strong>OK</strong>');
		$this->checkRenderOutput($control, __DIR__ . '/Component.expected');
	}

	public function testRenderWithExplicitAttach()
	{
		$this->attachToPresenter($control = new \Component);
		$this->checkRenderOutput($control, '<strong>OK</strong>');
		$this->checkRenderOutput($control, __DIR__ . '/Component.expected');
	}

	public function testMultipleAttaches()
	{
		$control = new \Component;
		$this->attachToPresenter($control);
		$this->attachToPresenter($control);
		\Tester\Environment::$checkAssertions = FALSE;
	}

}

(new TComponentTest)->run();
