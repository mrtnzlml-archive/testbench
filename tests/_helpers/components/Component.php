<?php

class Component extends \Nette\Application\UI\Control
{

	public function render()
	{
		$this->template->render(__DIR__ . '/Component.latte');
	}

}
