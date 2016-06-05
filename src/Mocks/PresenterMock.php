<?php

namespace Testbench\Mocks;

/**
 * @method onStartup(PresenterMock $this)
 */
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
		if ($this->getParameter('__terminate') === TRUE) {
			$this->terminate();
		}
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
		$params = urldecode(http_build_query($args, NULL, ', '));
		$params = $params ? "($params)" : '';
		return "plink|$destination$params";
	}

}
