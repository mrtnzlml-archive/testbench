<?php

$dic = require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class HomepagePresenterTest extends Tester\TestCase
{

	private $dic;

	use Testbench\TPresenter;

	public function __construct($dic)
	{
		$this->dic = $dic;
	}

	public function getDIContainer()
	{
		return $this->dic;
	}

	public function testRenderDefault()
	{
		$this->checkAction('Post:Default:default');
	}

}

(new HomepagePresenterTest($dic))->run();
