<?php

use Nette\Application\UI;

class ScaffoldPresenter extends Nette\Application\UI\Presenter
{

	const TEST = 'xyz';

	public function renderDefault($variable, $optional = 'optionalValue', $nullable = NULL, $const = \ScaffoldPresenter::TEST)
	{
		$this->template->variable = $variable;
	}

}
