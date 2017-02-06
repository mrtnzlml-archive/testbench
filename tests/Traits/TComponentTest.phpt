<?php

namespace Tests\Traits;

use Tester\Assert;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class TComponentTest extends \Tester\TestCase
{

	use \Testbench\TComponent;

	public function testAttachToPresenter()
	{
		$control = new \Component;
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

	/**
	 * @see vendor/nette/application/tests/Bridges.Latte/UIMacros.control.2.phpt
	 */
	public function testRenderWithParametersNetteCompatibility() {
		$latte = new \Latte\Engine;
		$latte->setLoader(new \Latte\Loaders\StringLoader);
		\Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());
		$latte->addProvider('uiControl', new \ComponentWithParameters);

		Assert::same('["var1"]', $latte->renderToString('{control cwp var1}'));
		Assert::same('["var1",1,2]', $latte->renderToString('{control cwp var1, 1, 2}'));
		Assert::same('[{"var1":5,"0":1,"1":2}]', $latte->renderToString('{control cwp var1 => 5, 1, 2}'));
	}

	public function testRenderWithParameters()
	{
		$control = new \ComponentWithParameters;
		$this->checkRenderOutput($control, '[1]', [1]);
		$this->checkRenderOutput($control, '[1,"2"]', [1, '2']);
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
		Assert::type('Testbench\CustomPresenterMock', $control->lookup('Nette\Application\IPresenter'));
		$this->attachToPresenter($control);
		Assert::type('Testbench\CustomPresenterMock', $control->lookup('Nette\Application\IPresenter'));
		\Tester\Environment::$checkAssertions = FALSE;
	}

	public function testMultipleAttachesDifferentComponents()
	{
		$this->attachToPresenter($control = new \Component, 'name_1');
		Assert::type('Testbench\CustomPresenterMock', $control->lookup('Nette\Application\IPresenter'));
		$this->attachToPresenter($control = new \Component, 'name_2');
		Assert::type('Testbench\CustomPresenterMock', $control->lookup('Nette\Application\IPresenter'));
		\Tester\Environment::$checkAssertions = FALSE;
	}

}

(new TComponentTest)->run();
