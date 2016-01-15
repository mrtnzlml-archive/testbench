<?php

require __DIR__ . '/../vendor/autoload.php';

$configFiles = [
	__DIR__ . '/tests.neon',
];

if (file_exists($localConfig = __DIR__ . '/tests.local.neon')) {
	$configFiles[] = $localConfig;
}

Testbench\Bootstrap::setup(__DIR__ . '/_helpers/temp', $configFiles);
