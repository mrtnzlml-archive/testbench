<?php

namespace Test;

use Nette;
use Tester;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class PresenterModuleTest extends Tester\TestCase
{

	use PresenterTester;

	public function __construct()
	{
		$this->openPresenter('Module:Presenter:');
	}

	public function testClassicRender()
	{
		$this->checkAction('default');
	}

}

(new PresenterModuleTest())->run();
