<?php

namespace Testbench;

class ControlMock extends \Nette\Application\UI\Control
{

	public function link($destination, $args = [])
	{
		if (!is_array($args)) {
			$args = array_slice(func_get_args(), 1);
		}
		$params = urldecode(http_build_query($args, NULL, ', '));
		$params = $params ? "($params)" : '';
		return "link|$destination$params";
	}

}
