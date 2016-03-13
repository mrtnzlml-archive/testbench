<?php

namespace Test;

require __DIR__ . '/../bootstrap.php';

/**
 * @multiple 50
 */
class PresenterBench extends \Tester\TestCase
{

	use \Testbench\TCompiledContainer;
	use \Testbench\TPresenter;

	public function testClassicRender()
	{
		$this->changeRunLevel(\Testbench::FINE);
		$this->checkAction('Presenter:default');
	}

}

(new PresenterBench)->run();
