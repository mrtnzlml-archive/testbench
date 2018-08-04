<?php declare(strict_types = 1);

class Testbench
{

	public const QUICK = 0;
	public const FINE = 5;
	public const SLOW = 10;

}

if (class_exists('Kdyby\Doctrine\Connection')) { //BC:
	class_alias('Testbench\Mocks\ApplicationRequestMock', 'Testbench\ApplicationRequestMock');
	class_alias('Testbench\Mocks\DoctrineConnectionMock', 'Testbench\ConnectionMock');
	class_alias('Testbench\Mocks\DoctrineConnectionMock', 'Testbench\Mocks\ConnectionMock');
	class_alias('Testbench\Mocks\ControlMock', 'Testbench\ControlMock');
	class_alias('Testbench\Mocks\HttpRequestMock', 'Testbench\HttpRequestMock');
	class_alias('Testbench\Mocks\PresenterMock', 'Testbench\PresenterMock');
}
