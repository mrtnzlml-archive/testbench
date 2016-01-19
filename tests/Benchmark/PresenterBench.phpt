<?php

namespace Test;

require __DIR__ . '/../bootstrap.php';

/**
 * @multiple 100
 */
class PresenterBench extends \Tester\TestCase
{

	use \Testbench\TPresenter;

	public function testClassicRender()
	{
		$this->checkAction('Presenter:default');
	}

}

(new PresenterBench)->run();
