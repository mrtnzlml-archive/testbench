<?php

namespace Testbench;

class Runner
{

	public function prepareArguments(array $args, $testsDir)
	{
		//Resolve tests dir from command line input
		$pathToTests = NULL;
		$position = 0;
		foreach ($args as $_) {
			if (isset($args[$position - 1]) && !preg_match('~^-[a-z0-9_-]+~i', $args[$position - 1])) {
				if (in_array($args[$position - 1], ['-s', '--stop-on-fail', '-i', '--info', '-h', '--help'])) { //skip singles
					continue;
				}
				$pathToTests = $args[$position];
			} elseif (count($args) === 1) {
				$pathToTests = $args[0];
			}
			$position++;
		}

		//Show information about skipped tests
		if (!in_array('-s', $args)) {
			$args[] = '-s';
		}

		//Specify PHP interpreter to run
		if (!in_array('-p', $args)) {
			$args[] = '-p';
			$args[] = 'php';
		}

		//Look for php.ini file
		$os = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'win' : 'unix';
		$iniFile = $testsDir . "/php-$os.ini";
		if (!in_array('-c', $args) && is_file($iniFile)) {
			$args[] = '-c';
			$args[] = $iniFile;
		}

		//Purge temp directory
		$found = array_search('--temp', $args);
		if ($found !== FALSE) {
			$dir = $args[$found + 1];
			unset($args[$found], $args[$found + 1]);
			$args = array_values($args);
		} else {
			$dir = $testsDir . '/_temp';
		}
		if (!is_dir($dir)) {
			mkdir($dir);
		}
		$rdi = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
		$rii = new \RecursiveIteratorIterator($rdi, \RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($rii as $entry) {
			if ($entry->isDir()) {
				rmdir($entry);
			} else {
				unlink($entry);
			}
		}

		if ($pathToTests === NULL) {
			$args[] = $testsDir;
		}
		return $args;
	}

}
