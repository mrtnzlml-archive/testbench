<?php declare(strict_types = 1);

namespace Testbench\Mocks;

use Nette\Application\Request;

class ApplicationRequestMock extends Request
{

	/**
	 * @param mixed[] $params
	 * @param mixed[] $post
	 * @param mixed[] $files
	 * @param mixed[] $flags
	 */
	public function __construct(
		?string $name = null,
		?string $method = null,
		array $params = [],
		array $post = [],
		array $files = [],
		array $flags = []
	)
	{
		$name = $name ?: 'Foo'; //It's going to be terminated anyway (see: \PresenterMock::afterRender)
		parent::__construct($name, $method, $params, $post, $files, $flags);
	}

}
