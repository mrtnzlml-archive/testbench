<?php

//this script is executed from .travis.yml

require dirname(__DIR__) . '/bootstrap.php';

$output = dirname(__DIR__) . '/_temp/scaffold';

$scaffold = new \Testbench\Scaffold\TestsGenerator;
$scaffold->generateTests($output);

\Tester\Environment::$checkAssertions = FALSE;
