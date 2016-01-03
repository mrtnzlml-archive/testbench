<?php

namespace Ant\Tests;

use Nette\Application;

class RequestMock extends Application\Request
{

	public function __construct($name = NULL, $method = NULL, array $params = [], array $post = [], array $files = [], array $flags = [])
	{
		$name = $name ?: 'Foo'; //It's going to be terminated anyway (see: \PresenterMock::afterRender)
		parent::__construct($name, $method, $params, $post, $files, $flags);
	}

}
