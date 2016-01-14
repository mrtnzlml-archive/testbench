<?php

namespace Test;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class PresenterModuleTest extends PresenterTestCase
{

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

}

(new PresenterModuleTest())->run();
