<?php

namespace Test;

use Testbench\TCompiledContainer;
use Testbench\TComponent;
use Testbench\TDoctrine;
use Testbench\TPresenter;

require __DIR__ . '/../bootstrap.php';

/**
 * Trait composition is fine, but there is problem with properties. (https://travis-ci.org/mrtnzlml/testbench/builds/104321286)
 *
 * @testCase
 * @phpVersion 7.0
 */
class MultipleTraits extends \Tester\TestCase
{

	use TCompiledContainer;
	use TComponent {
		TCompiledContainer::getContainer insteadof TComponent;
		TCompiledContainer::getService insteadof TComponent;
		TCompiledContainer::refreshContainer insteadof TComponent;
		TCompiledContainer::createContainer insteadof TComponent;
	}
	use TDoctrine {
		TCompiledContainer::getContainer insteadof TDoctrine;
		TCompiledContainer::getService insteadof TDoctrine;
		TCompiledContainer::refreshContainer insteadof TDoctrine;
		TDoctrine::createContainer insteadof TCompiledContainer; //warning!
	}
	use TPresenter {
		TCompiledContainer::getContainer insteadof TPresenter;
		TCompiledContainer::getService insteadof TPresenter;
		TCompiledContainer::refreshContainer insteadof TPresenter;
		TCompiledContainer::createContainer insteadof TPresenter;
	}

	public function testShutUp()
	{
		\Tester\Environment::$checkAssertions = FALSE;
	}

}

(new MultipleTraits)->run();
