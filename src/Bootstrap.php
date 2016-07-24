<?php

namespace Testbench;

class Bootstrap
{

	public static $tempDir;

	/** @var callable */
	public static $onBeforeContainerCreate;

	public static function setup($tempDir, $callback = NULL)
	{
		if (!class_exists('Tester\Assert')) {
			echo "Install Nette Tester using `composer update --dev`\n";
			exit(1);
		}
		self::$tempDir = $tempDir;
		self::$onBeforeContainerCreate = $callback;

		umask(0);
		if (!ob_get_level() > 0) { //\Tester\Environment::setup already called
			\Tester\Environment::setup();
		}
		date_default_timezone_set('Europe/Prague');

		if (class_exists('Tracy\Debugger')) {
			\Tracy\Debugger::$logDirectory = self::$tempDir;
		}

		$_ENV = $_GET = $_POST = $_FILES = [];

		$_SERVER['HTTP_USER_AGENT'] = 'Awesome Browser';
		$_SERVER['REMOTE_ADDR'] = '11.22.33.44';
	}

}
