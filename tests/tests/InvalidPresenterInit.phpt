<?php

namespace Test;

use Nette;
use Tester;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class PresenterTest extends Tester\TestCase {

	private $tester;

	public function __construct(Nette\DI\Container $container) {
		$this->tester = new PresenterTester($container);
	}

	public function testClassicRender() {
		$_this = $this;
		Tester\Assert::exception(function () use ($_this) {
			$_this->tester->testAction('default');
		}, 'LogicException', 'Presenter is not set. Use init method or second parameter in constructor.');
	}

}

$test = new PresenterTest($container);
$test->run();
