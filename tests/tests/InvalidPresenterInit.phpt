<?php

namespace Test;

use Nette;
use Tester;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class InvalidPresenterInit extends Tester\TestCase
{

	use \Test\PresenterTester;
	use \Kdyby\TesterExtras\CompiledContainer; //FIXME: přesunout (nefunguje?)

	public function testClassicRender()
	{
		Tester\Assert::exception(function () {
			$this->checkAction('default');
		}, 'LogicException');
	}

}

(new InvalidPresenterInit($container))->run();
