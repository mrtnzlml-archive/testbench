<?php

namespace Testbench;

use Nette\Http;

class HttpRequestMock extends \Nette\Http\Request
{

	public function __construct(
		Http\UrlScript $url = NULL,
		$query = NULL,
		$post = [],
		$files = [],
		$cookies = [],
		$headers = [],
		$method = PHP_SAPI,
		$remoteAddress = '127.0.0.1',
		$remoteHost = '127.0.0.1',
		$rawBodyCallback = NULL
	) {
		$url = $url ?: new Http\UrlScript('http://fake.url/');
		if ($query !== NULL) {
			$url->setQuery($query);
		}
		parent::__construct(
			$url,
			NULL, //deprecated
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
