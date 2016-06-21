<?php

namespace Testbench;

use Kdyby\Events\Subscriber;

class OnConnectListener extends \Nette\Object implements \Kdyby\Events\Subscriber
{

	public function getSubscribedEvents()
	{
		return [
			'Nette\Database\Connection::onConnect' => 'onNdbtConnect',
		];
	}

	public function onNdbtConnect()
	{
		\Tester\Assert::fail('Ok!');
		die('OK');
	}

}
