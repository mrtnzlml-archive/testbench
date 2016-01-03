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

	public static function setup($tempDir)
	{
		if (!class_exists('Tester\Assert')) {
			echo "Install Nette Tester using `composer update --dev`\n";
			exit(1);
		}
		umask(0);
		Tester\Environment::setup();
		date_default_timezone_set('Europe/Prague');

		define('TEMP_DIR', $tempDir);
		Tracy\Debugger::$logDirectory = TEMP_DIR;

		$_ENV = $_GET = $_POST = $_FILES = [];
	}

}
