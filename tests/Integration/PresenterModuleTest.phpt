<?php

namespace Test;

use Ant\Tests\TPresenter;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class PresenterModuleTest extends TestCase
{

	use TPresenter;

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
