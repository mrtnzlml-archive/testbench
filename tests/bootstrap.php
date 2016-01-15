<?php

require __DIR__ . '/../vendor/autoload.php';

Testbench\Bootstrap::setup(__DIR__ . '/_helpers/temp', [
	__DIR__ . '/tests.neon',
]);
