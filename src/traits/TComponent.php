<?php

namespace Testbench;

use Nette\ComponentModel\IComponent;

trait TComponent
{

	use TCompiledContainer;

	protected function attachToPresenter(IComponent $component, $name = NULL)
	{
		if ($name === NULL) {
			if (!$name = $component->getName()) {
				$name = $component->getReflection()->getShortName();
			}
		}
		$presenter = $this->getService('Testbench\PresenterMock');
		$presenter->onStartup[] = function (PresenterMock $presenter) use ($component, $name) {
			$presenter->addComponent($component, $name);
		};
		$container = $this->getContainer();
		$container->callInjects($presenter);
		$presenter->run(new ApplicationRequestMock);
	}

	protected function checkRenderOutput(IComponent $control, $expected)
	{
		if (!$control->getParent()) {
			$this->attachToPresenter($control);
		}
		ob_start();
		$control->render();
		if (is_file($expected)) {
			\Tester\Assert::matchFile($expected, ob_get_clean());
		} else {
			\Tester\Assert::match($expected, ob_get_clean());
		}
	}

}
