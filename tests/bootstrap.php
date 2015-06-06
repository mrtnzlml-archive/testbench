<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Bootstrap.php';

Test\Bootstrap::setup(__DIR__);

$loader = new Nette\Loaders\RobotLoader;
$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage(TEMP_DIR));
//$loader->setCacheStorage(new Nette\Caching\Storages\MemoryStorage());
$loader->autoRebuild = TRUE;
$loader->addDirectory(__DIR__ . '/../src');
$loader->addDirectory(__DIR__ . '/../tests/ModuleModule');
$loader->addDirectory(__DIR__ . '/../tests/presenters');
$loader->register();

Test\Bootstrap::cleanup(__DIR__);
