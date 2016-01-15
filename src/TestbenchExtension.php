<?php

namespace Testbench;

class TestbenchExtension extends \Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$this->compiler->getContainerBuilder()->parameters[$this->name] = $this->getConfig();
	}

}
