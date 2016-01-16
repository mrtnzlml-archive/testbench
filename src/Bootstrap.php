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
		\Tester\Environment::setup();
		date_default_timezone_set('Europe/Prague');

		if (class_exists('Tracy\Debugger')) {
			\Tracy\Debugger::$logDirectory = self::$tempDir;
		}

		$_ENV = $_GET = $_POST = $_FILES = [];
	}

}
