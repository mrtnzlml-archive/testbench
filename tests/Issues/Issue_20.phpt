<?php

namespace Tests\Issues;

use Tester\FileMock;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

\Testbench\Bootstrap::setup(dirname(__DIR__) . '/_temp', function (\Nette\Configurator $configurator) {
	$config = <<<CONFIG
application:
	scanComposer: no
routing:
	routes:
		'/x/y[[[/<presenter>]/<action>][/<id>]]': 'Presenter:default'
CONFIG;
	$configurator->addConfig(FileMock::create($config, 'neon'));
	$configurator->addParameters(['appDir' => dirname(__DIR__) . '/../src',]);
});

/**
 * @testCase
 * @see https://github.com/mrtnzlml/testbench/issues/20
 */
final class Issue_20 extends \Tester\TestCase
{

	use \Testbench\TPresenter;

	public function testRenderDefault()
	{
		$this->checkAction('Presenter:default');
	}

}

(new Issue_20)->run();
