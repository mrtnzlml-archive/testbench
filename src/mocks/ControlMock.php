<?php

namespace Ant\Tests;

class ControlMock
{

	public function link($destination, $args = [])
	{
		if (!is_array($args)) {
			$args = array_slice(func_get_args(), 1);
		}
		array_unshift($args, $destination);
		return 'link:' . strtr(json_encode($args), '"', "'");
	}

}
