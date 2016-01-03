<?php

namespace Ant\Tests;

use Nette\ComponentModel\IComponent;

trait TComponent
{

	use TDatabaseSetup;

	protected function attachToPresenter(IComponent $component, $name = NULL)
	{
		if ($name === NULL) {
			$rc = new \ReflectionClass($component);
			$name = $rc->getShortName();
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
