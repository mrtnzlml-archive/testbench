<?php

namespace ModuleModule;

use Nette;

class PresenterPresenter extends Nette\Application\UI\Presenter
{

	public function renderDefault()
	{
		$this->template->variable = 'test';
	}

}
