<?php

namespace Test;

use Testbench\TCompiledContainer;
use Testbench\TComponent;
use Testbench\TDatabaseSetup;
use Testbench\TPresenter;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @skip This test test works only with PHP 7.
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
	use TDatabaseSetup {
		TCompiledContainer::getContainer insteadof TDatabaseSetup;
		TCompiledContainer::getService insteadof TDatabaseSetup;
		TCompiledContainer::refreshContainer insteadof TDatabaseSetup;
		TDatabaseSetup::createContainer insteadof TCompiledContainer; //warning!
	}
	use TPresenter {
		TCompiledContainer::getContainer insteadof TPresenter;
		TCompiledContainer::getService insteadof TPresenter;
		TCompiledContainer::refreshContainer insteadof TPresenter;
		TCompiledContainer::createContainer insteadof TPresenter;
	}

}

(new MultipleTraits)->run();
