<?php declare(strict_types = 1);

namespace Testbench\Mocks;

use Nette\Application\Request;
use Nette\Application\UI\Presenter;

/**
 * @method onStartup(PresenterMock $this)
 */
class PresenterMock extends Presenter
{

	/** @var callable[] */
	public $onStartup = [];

	public function run(Request $request)
	{
		$this->autoCanonicalize = false;
		return parent::run($request);
	}

	public function startup(): void
	{
		if ($this->getParameter('__terminate') === true) {
			$this->terminate();
		}
		parent::startup();
		$this->onStartup($this);
	}

	public function afterRender(): void
	{
		$this->terminate();
	}

	public function isAjax()
	{
		return false;
	}

	public function link($destination, $args = [])
	{
		if (!is_array($args)) {
			$args = array_slice(func_get_args(), 1);
		}
		$params = urldecode(http_build_query($args, null, ', '));
		$params = $params ? "($params)" : '';
		return "plink|$destination$params";
	}

}
