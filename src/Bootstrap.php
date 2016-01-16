<?php

namespace Testbench;

class Bootstrap extends \Nette\Object
{

	public static $configFiles = [];

	public static $tempDir;

	public static function setup($tempDir, array $configFiles = [])
	{
		if (!class_exists('Tester\Assert')) {
			echo "Install Nette Tester using `composer update --dev`\n";
			exit(1);
		}
		self::$configFiles = $configFiles;
		self::$tempDir = $tempDir;

		umask(0);
		\Tester\Environment::setup();
		date_default_timezone_set('Europe/Prague');

		if (class_exists('Tracy\Debugger')) {
			\Tracy\Debugger::$logDirectory = self::$tempDir;
		}

		$_ENV = $_GET = $_POST = $_FILES = [];
	}

}
