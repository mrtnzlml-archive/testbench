<?php

namespace Testbench;

class Runner
{

	public function prepareArguments(array $args, $testsDir)
	{
		//Scaffold
//		$scaffold = array_search('--scaffold', $args);
//		if ($scaffold !== FALSE) {
//			if (!isset($args[$scaffold + 1])) {
//				die("Error: specify scaffold output folder like this: '--scaffold <bootstrap.php>'\n");
//			}
//			$scaffoldBootstrap = $args[$scaffold + 1];
//			$scaffoldDir = dirname($scaffoldBootstrap);
//			rtrim($scaffoldDir, DIRECTORY_SEPARATOR);
//			if (count(glob("$scaffoldDir/*")) !== 0) {
//				die("Error: please use different empty folder - I don't want to destroy your work\n");
//			}
//			require $scaffoldBootstrap; //FIXME: špatný přístup (předávat jen NEON?)
//			\Nette\Utils\FileSystem::createDir($scaffoldDir . '/_temp');
//			$scaffold = new \Testbench\Scaffold\TestsGenerator;
//			$scaffold->generateTests($scaffoldDir);
//			\Tester\Environment::$checkAssertions = FALSE;
//			die("Tests generated to the folder '$scaffoldDir'\n");
//		}

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

	public function findVendorDirectory()
	{
		$recursionLimit = 10;
		$findVendor = function ($dirName = 'vendor/bin', $dir = __DIR__) use (&$findVendor, &$recursionLimit) {
			if (!$recursionLimit--) {
				throw new \Exception('Cannot find vendor directory.');
			}
			$found = $dir . "/$dirName";
			if (is_dir($found) || is_file($found)) {
				return dirname($found);
			}
			return $findVendor($dirName, dirname($dir));
		};
		return $findVendor();
	}

	/**
	 * @see http://stackoverflow.com/a/2638272/3135248
	 */
	function getRelativePath($from, $to)
	{
		// some compatibility fixes for Windows paths
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$to = is_dir($to) ? rtrim($to, '\/') . '/' : $to;
		$from = str_replace('\\', '/', $from);
		$to = str_replace('\\', '/', $to);

		$from = explode('/', $from);
		$to = explode('/', $to);
		$relPath = $to;

		foreach ($from as $depth => $dir) {
			// find first non-matching dir
			if ($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if ($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}

		return implode('/', $relPath);
	}

}
