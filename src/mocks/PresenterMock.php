<?php

namespace Testbench;

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

	protected function handleInvalidLink(\Nette\Application\UI\InvalidLinkException $e)
	{
		$this->invalidLinkMode = $this::INVALID_LINK_EXCEPTION;
		parent::handleInvalidLink($e);
	}

}
