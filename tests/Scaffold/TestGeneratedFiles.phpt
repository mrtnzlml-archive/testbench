<?php

require dirname(__DIR__) . '/bootstrap.php';

$output = dirname(__DIR__) . '/_temp/scaffold';

$scaffold = new \Testbench\Scaffold\TestsGenerator;
$scaffold->generateTests($output);

function removeColors($s)
{
	return preg_replace('#\033\[[\d;]+m#', '', $s);
}

Tester\Assert::same('', removeColors(shell_exec('php ' . $output . '/PresenterPresenter.phpt')));
Tester\Assert::same('', removeColors(shell_exec('php ' . $output . '/ModuleModule/PresenterPresenter.phpt')));
