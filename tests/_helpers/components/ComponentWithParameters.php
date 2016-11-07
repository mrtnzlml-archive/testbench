<?php

class ComponentWithParameters extends \Nette\Application\UI\Control
{

	public function render($parameterOne, $parameterTwo = NULL)
	{
		$this->template->parameterOne = $parameterOne;
		$this->template->parameterTwo = $parameterTwo;
		$this->template->render(__DIR__ . '/ComponentWithParameters.latte');
	}

}
