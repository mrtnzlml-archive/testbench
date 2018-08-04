<?php declare(strict_types = 1);

namespace Testbench;

class DatabasesRegistry
{

	private $dataFile;

	public function __construct()
	{
		$this->dataFile = 'nette.safe://' . Bootstrap::$tempDir . '/../databases.testbench';
	}

	/**
	 * @return TRUE if registration successful or FALSE if database record already exists
	 */
	public function registerDatabase(string $databaseName): bool
	{
		if (file_exists($this->dataFile)) {
			$data = file_get_contents($this->dataFile);
		} else {
			$data = '';
		}

		if (!preg_match('~' . $databaseName . '~', $data)) { //database doesn't exist in log file
			$handle = fopen($this->dataFile, 'a+');
			fwrite($handle, $databaseName . "\n");
			fclose($handle);

			return true;
		} else { //database already exists in log file
			return false;
		}
	}

}
