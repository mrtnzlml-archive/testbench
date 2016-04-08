<?php

namespace Test;

use Nette\Utils\FileSystem;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class Runner extends \Tester\TestCase
{

	private $tempDir;

	/** @var \Testbench\Runner */
	private $runner;

	public function setUp()
	{
		$this->tempDir = __DIR__ . '/_temp';
		$this->runner = new \Testbench\Runner;
	}

	public function testWithoutArguments()
	{
		Assert::same([
			'-s',
			'-p',
			'php',
			$this->tempDir,
		], $this->runner->prepareArguments([], $this->tempDir));
	}

	public function testNativeArguments()
	{
		Assert::same([
			'-j',
			'20',
			'-s',
			'-p',
			'php',
			$this->tempDir,
		], $this->runner->prepareArguments(['-j', '20'], $this->tempDir));
	}

	public function testInterpreter()
	{
		Assert::same([
			'-p',
			'php-cgi',
			'-s',
			$this->tempDir,
		], $this->runner->prepareArguments(['-p', 'php-cgi'], $this->tempDir));
	}

	public function testConfigExists()
	{
		$os = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'win' : 'unix';
		FileSystem::write($configFile = $this->tempDir . "/php-$os.ini", '');
		Assert::same([
			'-s',
			'-p',
			'php',
			'-c',
			$configFile,
			$this->tempDir,
		], $this->runner->prepareArguments([], $this->tempDir));
		FileSystem::delete($configFile);
	}

	public function testTemp()
	{
		Assert::same([
			'-s',
			'-p',
			'php',
			$this->tempDir,
		], $this->runner->prepareArguments(['--temp', $this->tempDir . '/_temp2'], $this->tempDir));
	}

	public function testPath()
	{
		Assert::same([
			'path/to/tests',
			'-s',
			'-p',
			'php',
		], $this->runner->prepareArguments(['path/to/tests'], $this->tempDir));

		Assert::same([
			'-p',
			'php-cgi',
			'path/to/tests',
			'-s',
		], $this->runner->prepareArguments(['-p', 'php-cgi', 'path/to/tests'], $this->tempDir));

		Assert::same([
			'path/to/tests',
			'-p',
			'php-cgi',
			'-s',
		], $this->runner->prepareArguments(['path/to/tests', '-p', 'php-cgi'], $this->tempDir));

		Assert::same([
			'-s',
			'path/to/tests',
			'-p',
			'php-cgi',
		], $this->runner->prepareArguments(['-s', 'path/to/tests', '-p', 'php-cgi'], $this->tempDir));
	}

}

(new Runner)->run();
