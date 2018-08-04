<?php declare(strict_types = 1);

namespace Testbench\Mocks;

use Nette\Application\UI\Control;

class ControlMock extends Control
{

	public function link($destination, $args = [])
	{
		if (!is_array($args)) {
			$args = array_slice(func_get_args(), 1);
		}
		$params = urldecode(http_build_query($args, null, ', '));
		$params = $params ? "($params)" : '';
		return "link|$destination$params";
	}

}
