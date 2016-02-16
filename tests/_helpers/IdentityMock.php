<?php

namespace Testbench;

class IdentityMock extends \Nette\Object implements \Nette\Security\IIdentity
{

	public function getId()
	{
		return 1;
	}

	public function getRoles()
	{
		return ['test1', 'test2'];
	}

}
