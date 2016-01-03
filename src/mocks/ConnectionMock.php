<?php

namespace Ant\Tests;

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
