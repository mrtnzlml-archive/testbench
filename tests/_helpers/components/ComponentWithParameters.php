<?php

class ComponentWithParameters extends \Nette\Application\UI\Control
{

	public function render($parameterOne, $parameterTwo = NULL)
	{
		echo json_encode(func_get_args(), JSON_OBJECT_AS_ARRAY);
	}

	public function getComponent($name, $need = TRUE)
	{
		return new self;
	}

}
