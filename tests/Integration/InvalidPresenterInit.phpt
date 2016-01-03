<?php

namespace Test;

use Nette;
use Tester;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class InvalidPresenterInit extends CustomTestCase
{

	public function testClassicRender()
	{
		Tester\Assert::exception(function () {
			$this->checkAction('default');
		}, 'LogicException');
	}

}

(new InvalidPresenterInit())->run();
