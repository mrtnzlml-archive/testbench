<?php declare(strict_types = 1);

namespace Testbench\Mocks;

use Nette\Http;
use Nette\Http\Request;

class HttpRequestMock extends Request
{

	public function __construct(
		?Http\UrlScript $url = null,
		$query = null,
		$post = [],
		$files = [],
		$cookies = [],
		$headers = [],
		$method = PHP_SAPI,
		$remoteAddress = '127.0.0.1',
		$remoteHost = '127.0.0.1',
		$rawBodyCallback = null
	)
	{
		$url = $url ?: new Http\UrlScript('http://test.bench/');
		if ($query !== null) {
			$url->setQuery($query);
		}
		parent::__construct(
			$url,
			null, //deprecated
			$post,
			$files,
			$cookies,
			$headers,
			$method,
			$remoteAddress,
			$remoteHost,
			$rawBodyCallback
		);
	}

}
