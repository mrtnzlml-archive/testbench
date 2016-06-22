<?php

namespace Test\Runner;

use Nette\Utils\FileSystem;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class Runner extends \Tester\TestCase
{

	private $tempDir;

	/** @var \Testbench\Runner */
	private $runner;

	private $os;

	public function setUp()
	{
		$this->tempDir = dirname(__DIR__) . '/_temp';
		$this->runner = new \Testbench\Runner;
		$this->os = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'win' : 'unix';
		\Tester\Environment::lock('lock_temp_dir', $this->tempDir); //needed for testConfigExists
	}

	public function testWithoutArguments()
	{
		Assert::same([
			'-p', 'php',
			'-s',
			$this->tempDir,
		], $this->runner->prepareArguments([], $this->tempDir));
	}

	public function testWithoutArgumentsEnv()
	{
		Assert::same([
			'ENV=value', //linux environment variable (always first)
			'-p', 'php',
			'-s',
			$this->tempDir,
		], $this->runner->prepareArguments(['ENV=value'], $this->tempDir));
	}

	public function testWatch()
	{
		Assert::same([
			'-w', 'tests/',
			'-w', 'src/',
			'-p', 'php',
			'-s',
			$this->tempDir,
		], $this->runner->prepareArguments(['-w', 'tests/', '-w', 'src/'], $this->tempDir));
	}

	public function testNativeArguments()
	{
		Assert::same([
			'-j', '20',
			'-p', 'php',
			'-s',
			$this->tempDir,
		], $this->runner->prepareArguments(['-j', '20'], $this->tempDir));
	}

	public function testInterpreter()
	{
		Assert::same([
			'-p', 'php-cgi',
			'-s',
			$this->tempDir,
		], $this->runner->prepareArguments(['-p', 'php-cgi'], $this->tempDir));
	}

	public function testConfigExists()
	{
		$os = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'win' : 'unix';
		FileSystem::write($configFile = $this->tempDir . "/php-$os.ini", '');
		Assert::same([
			'-p', 'php',
			'-s',
			'-c', $configFile,
			$this->tempDir,
		], $this->runner->prepareArguments([], $this->tempDir));
		FileSystem::delete($configFile);
	}

	public function testTemp()
	{
		Assert::same([
			'-p', 'php',
			'-s',
			$this->tempDir,
		], $this->runner->prepareArguments(['--temp', $this->tempDir . '/_temp2'], $this->tempDir));
	}

	public function testPath()
	{
		Assert::same([
			'-p', 'php',
			'-s',
			'path/to/tests',
		], $this->runner->prepareArguments(['path/to/tests'], $this->tempDir));

		Assert::same($expected = [
			'-p', 'php-cgi',
			'-s',
			'path/to/tests',
		], $this->runner->prepareArguments(['-p', 'php-cgi', 'path/to/tests'], $this->tempDir));
		Assert::same($expected, $this->runner->prepareArguments(['path/to/tests', '-p', 'php-cgi'], $this->tempDir));

		Assert::same([
			'-s',
			'-p', 'php-cgi',
			'path/to/tests',
		], $this->runner->prepareArguments(['-s', 'path/to/tests', '-p', 'php-cgi'], $this->tempDir));
	}

	public function testAll()
	{
		Assert::same([
			'ENV=value', //linux environment variable (always first)
			'-p', 'php-cgi',
			'-s',
			'--stop-on-fail',
			'-w', 'tests/',
			'-w', 'src/',
			'-w', 'folder/',
			'-j', '20',
			'path/to/tests',
		], $this->runner->prepareArguments([
			'ENV=value',
			'-p', 'php-cgi',
			'-w', 'tests/',
			'-s',
			'--stop-on-fail',
			'-w', 'src/',
			'-w', 'folder/',
			'-j', '20',
			'--temp', $this->tempDir . '/_temp2',
			'path/to/tests'
		], $this->tempDir));
	}

}

(new Runner)->run();
