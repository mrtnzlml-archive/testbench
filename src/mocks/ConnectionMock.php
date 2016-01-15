<?php

namespace Testbench;

/**
 * @method onConnect(ConnectionMock $self)
 */
class ConnectionMock extends \Kdyby\Doctrine\Connection
{

	public $onConnect = [];

	public function connect()
	{
		if (parent::connect()) {
			$this->onConnect($this);
		}
	}

}
