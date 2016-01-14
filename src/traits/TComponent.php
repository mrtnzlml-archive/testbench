<?php

namespace Ant\Tests;

use Nette\ComponentModel\IComponent;

trait TComponent
{

	use TCompiledContainer;

	protected function attachToPresenter(IComponent $component, $name = NULL)
	{
		if ($name === NULL) {
			$name = $component->getName();
		}
		$presenter = new PresenterMock;
		$presenter->onStartup[] = function (PresenterMock $presenter) use ($component, $name) {
			$presenter->addComponent($component, $name);
		};
		$container = $this->getContainer();
		$container->callInjects($presenter);
		$presenter->run(new RequestMock);
	}

}
