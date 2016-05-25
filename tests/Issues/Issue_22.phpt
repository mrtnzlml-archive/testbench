<?php

namespace Test;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @see https://github.com/mrtnzlml/testbench/issues/22
 * @phpVersion 7
 */
class Issue_22 extends \Tester\TestCase
{

	use \Testbench\TComponent;

	public function testAnonymousComponentRender()
	{
		$control = new class extends \Nette\Application\UI\Control
		{
			public function render()
			{
				echo 'ok';
			}
		};
		$this->checkRenderOutput($control, 'ok');
	}

}

(new Issue_22)->run();
