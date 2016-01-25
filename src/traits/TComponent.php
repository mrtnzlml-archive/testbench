<?php

namespace Testbench;

use Nette\ComponentModel\IComponent;

trait TComponent
{

	use TCompiledContainer;

	private $_presenter;

	protected function attachToPresenter(IComponent $component, $name = NULL)
	{
		if ($name === NULL) {
			if (!$name = $component->getName()) {
				$name = $component->getReflection()->getShortName();
			}
		}
		if (!$this->_presenter) {
			$this->_presenter = $this->getService('Testbench\PresenterMock');
			$container = $this->getContainer();
			$container->callInjects($this->_presenter);
		}
		$this->_presenter->onStartup[] = function (PresenterMock $presenter) use ($component, $name) {
			try {
				$presenter->removeComponent($component);
			} catch (\Nette\InvalidArgumentException $exc) {
			}
			$presenter->addComponent($component, $name);
		};
		$this->_presenter->run(new ApplicationRequestMock);
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
