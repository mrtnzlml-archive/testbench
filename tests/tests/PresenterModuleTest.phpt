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

	use \Test\PresenterTester;
	use \Kdyby\TesterExtras\CompiledContainer; //FIXME: přesunout (nefunguje?)

	public function setUp()
	{
		$this->openPresenter('Module:Presenter:');
	}

	public function testClassicRender()
	{
		$this->checkAction('default');
	}

}

(new PresenterModuleTest())->run();
