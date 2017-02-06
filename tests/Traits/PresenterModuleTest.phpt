<?php

namespace Tests\Traits;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class PresenterModuleTest extends \Tester\TestCase
{

	use \Testbench\TPresenter;

	public function testClassicRender1()
	{
		$this->checkAction('Module:Presenter:');
	}

	public function testClassicRender2()
	{
		$this->checkAction('Module:Presenter:default');
	}

	public function testClassicRender3()
	{
		$this->checkAction(':Module:Presenter:default');
	}

	public function testMultipleSame()
	{
		$this->checkAction('Module:Presenter:');
		$this->checkAction('Module:Presenter:default');
		$this->checkAction(':Module:Presenter:default');
	}

}

(new PresenterModuleTest())->run();
