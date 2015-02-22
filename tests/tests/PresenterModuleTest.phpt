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

	public function setUp() {
		$this->tester->init('Module:Presenter');
	}

	public function testClassicRender() {
		$this->tester->testAction('default');
	}

}

$test = new PresenterTest($container);
$test->run();
