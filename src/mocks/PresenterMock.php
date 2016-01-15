<?php

namespace Testbench;

class PresenterMock extends \Nette\Application\UI\Presenter
{

	/** @var callable[] */
	public $onStartup = [];

	public function run(\Nette\Application\Request $request)
	{
		$this->autoCanonicalize = FALSE;
		return parent::run($request);
	}

	public function startup()
	{
		parent::startup();
		$this->onStartup($this);
	}

	public function afterRender()
	{
		$this->terminate();
	}

	public function isAjax()
	{
		return FALSE;
	}

	public function link($destination, $args = [])
	{
		if (!is_array($args)) {
			$args = array_slice(func_get_args(), 1);
		}
		array_unshift($args, $destination);
		return 'plink:' . strtr(json_encode($args), '"', "'");
	}

}
