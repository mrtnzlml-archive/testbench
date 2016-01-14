<?php

namespace Test;

use Ant\Tests\TPresenter;

class PresenterTestCase extends \Tester\TestCase
{

	use TPresenter {
		TPresenter::createContainer as presenterCreateContainer;
	}

	protected function createContainer()
	{
		$container = $this->presenterCreateContainer([
//			__DIR__ . '/tests.neon',
		]);
		$routeList = $container->getService('router');
		$routeList[] = new \Nette\Application\Routers\Route('/x/y[[[/<presenter>]/<action>][/<id>]]', 'Presenter:default');
		return $container;
	}

}
