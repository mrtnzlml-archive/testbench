<?php

namespace Test;

use Nette;
use Tester;
use Tracy;

/**
 * Class Bootstrap
 * @package Test
 */
class Bootstrap extends Nette\Object
{

	public static function setup($root_dir)
	{
		if (!class_exists('Tester\Assert')) {
			echo "Install Nette Tester using `composer update --dev`\n";
			exit(1);
		}
		umask(0);
		Tester\Environment::setup();
		date_default_timezone_set('Europe/Prague');

		if (!is_dir($root_dir . '/temp/')) {
			mkdir($root_dir . '/temp/');
		}
		define('TEMP_DIR', $root_dir . '/temp/' . getmypid());
		Tester\Helpers::purge(TEMP_DIR);
		@chmod(TEMP_DIR, 0777);
		Tracy\Debugger::$logDirectory = TEMP_DIR;

		$_ENV = $_GET = $_POST = $_FILES = [];
		$configurator = new Nette\Configurator;
		$configurator->setTempDirectory(TEMP_DIR);
		return $configurator;
	}

}
