<?php

use Nette\Application\UI;

class ScaffoldPresenter extends Nette\Application\UI\Presenter
{

	public function renderDefault($variable, $optional = 'optionalValue')
	{
		$this->template->variable = $variable;
	}

}
