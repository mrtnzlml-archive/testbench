<?php

class Testbench
{

	const QUICK = 0;
	const FINE = 5;
	const SLOW = 10;

}

if (class_exists('Kdyby\Doctrine\Connection')) { //BC:
	class_alias('Testbench\Mocks\ApplicationRequestMock', 'Testbench\ApplicationRequestMock');
	class_alias('Testbench\Mocks\ConnectionMock', 'Testbench\ConnectionMock');
	class_alias('Testbench\Mocks\ControlMock', 'Testbench\ControlMock');
	class_alias('Testbench\Mocks\HttpRequestMock', 'Testbench\HttpRequestMock');
	class_alias('Testbench\Mocks\PresenterMock', 'Testbench\PresenterMock');
}
