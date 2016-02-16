<?php

namespace Testbench;

use Tester\Assert;

trait TPresenter
{

	use TCompiledContainer;

	/** @var \Nette\Application\IPresenter */
	private $_presenter;

	private $_httpCode;

	private $_exception;

	private $_ajaxMode = FALSE;

	/**
	 * @param string $destination
	 * @param array $params
	 * @param array $post
	 *
	 * @return \Nette\Application\IResponse
	 * @throws \Exception
	 */
	private function check($destination, $params = [], $post = [])
	{
		$destination = ltrim($destination, ':');
		$pos = strrpos($destination, ':');
		$presenter = substr($destination, 0, $pos);
		$action = substr($destination, $pos + 1) ?: 'default';

		if (!$this->_presenter) {
			$container = $this->getContainer();
			$container->removeService('httpRequest');
			$headers = $this->_ajaxMode ? ['X-Requested-With' => 'XMLHttpRequest'] : [];
			$container->addService('httpRequest', new HttpRequestMock(NULL, NULL, [], [], [], $headers));
			$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');
			$class = $presenterFactory->getPresenterClass($presenter);
			$this->_presenter = $container->createInstance($class);
			$this->_presenter->autoCanonicalize = FALSE;
			$this->_presenter->invalidLinkMode = \Nette\Application\UI\Presenter::INVALID_LINK_EXCEPTION;
			$container->callInjects($this->_presenter);
		}
		$request = new ApplicationRequestMock(
			$presenter,
			$post ? 'POST' : 'GET',
			['action' => $action] + $params,
			$post
		);
		try {
			$this->_httpCode = 200;
			$response = $this->_presenter->run($request);
			return $response;
		} catch (\Exception $exc) {
			$this->_exception = $exc;
			$this->_httpCode = $exc->getCode();
			throw $exc;
		}
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @param array $post
	 *
	 * @return \Nette\Application\Responses\TextResponse
	 * @throws \Exception
	 */
	private function checkAction($destination, $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\TextResponse', $response);
			Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$dom = @\Tester\DomQuery::fromHtml($response->getSource()); // @ - not valid HTML
			Assert::true($dom->has('html'));
			Assert::true($dom->has('title'));
			Assert::true($dom->has('body'));
		}
		return $response;
	}

	/**
	 * @param string $destination
	 * @param string $signal
	 * @param array $params
	 * @param array $post
	 *
	 * @return \Nette\Application\IResponse
	 */
	private function checkSignal($destination, $signal, $params = [], $post = [])
	{
		return $this->checkRedirect($destination, '/', [
				'do' => $signal,
			] + $params, $post);
	}

	/**
	 * @param string $destination
	 * @param string $path
	 * @param array $params
	 * @param array $post
	 *
	 * @return \Nette\Application\Responses\RedirectResponse
	 * @throws \Exception
	 */
	private function checkRedirect($destination, $path = '/', $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\RedirectResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\RedirectResponse', $response);
			Assert::same(302, $response->getCode());
			Assert::match("~^https?://fake\.url{$path}[a-z0-9?&=_/]*$~", $response->getUrl());
		}
		return $response;
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @param array $post
	 *
	 * @return \Nette\Application\Responses\JsonResponse
	 * @throws \Exception
	 */
	private function checkJson($destination, $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\JsonResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\JsonResponse', $response);
			Assert::same('application/json', $response->getContentType());
		}
		return $response;
	}

	/**
	 * @param string $destination
	 * @param string $formName
	 * @param array $post
	 * @param string|boolean $path Path after redirect or FALSE if it's form without redirect
	 *
	 * @return \Nette\Application\Responses\RedirectResponse
	 * @throws \Tester\AssertException
	 */
	private function checkForm($destination, $formName, $post = [], $path = '/')
	{
		if (is_string($path)) {
			return $this->checkRedirect($destination, $path, [
				'do' => $formName . '-submit',
			], $post);
		} elseif (is_bool($path)) {
			/** @var \Nette\Application\Responses\RedirectResponse $response */
			$response = $this->check($destination, [
				'do' => $formName . '-submit',
			], $post);
			if (!$this->_exception) {
				Assert::same(200, $this->getReturnCode());
				Assert::type('Nette\Application\Responses\TextResponse', $response);
			}
			return $response;
		} else {
			\Tester\Assert::fail('Path should be string or boolean (probably FALSE).');
		}
	}

	private function checkAjaxForm($destination, $formName, $post = [], $path = FALSE)
	{
		if (is_string($path)) {
			$this->checkForm($destination, $formName, $post, $path);
			Assert::false($this->_presenter->isAjax());
		}
		$this->_presenter = NULL; //FIXME: not very nice, but performance first
		$this->_ajaxMode = TRUE;
		$response = $this->check($destination, [
			'do' => $formName . '-submit',
		], $post);
		Assert::true($this->_presenter->isAjax());
		if (!$this->_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\JsonResponse', $response);
		}
		$this->_presenter = NULL;
		$this->_ajaxMode = FALSE;
		return $response;
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @param array $post
	 *
	 * @return \Nette\Application\Responses\TextResponse
	 * @throws \Exception
	 */
	private function checkRss($destination, $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\TextResponse', $response);
			Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$dom = \Tester\DomQuery::fromXml($response->getSource());
			Assert::true($dom->has('rss'));
			Assert::true($dom->has('channel'));
			Assert::true($dom->has('title'));
			Assert::true($dom->has('link'));
			Assert::true($dom->has('item'));
		}
		return $response;
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @param array $post
	 *
	 * @return \Nette\Application\Responses\TextResponse
	 * @throws \Exception
	 */
	private function checkSitemap($destination, $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\TextResponse', $response);
			Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$dom = @\Tester\DomQuery::fromHtml($response->getSource()); // @ - not valid HTML
			Assert::true($dom->has('urlset'));
			Assert::true($dom->has('url'));
			Assert::true($dom->has('loc'));
		}
		return $response;
	}

	/**
	 * @param integer $id
	 * @param array|null $roles
	 * @param \Nette\Security\IIdentity|array|null $data
	 *
	 * @return \Nette\Security\User
	 */
	private function logIn($id = 1, $roles = NULL, $data = NULL)
	{
		$identity = $data instanceof \Nette\Security\IIdentity ? $data
			: new \Nette\Security\Identity($id, $roles, $data);
		/** @var \Nette\Security\User $user */
		$user = $this->getContainer()->getByType('Nette\Security\User');
		$user->login($identity);
		return $user;
	}

	/**
	 * @return \Nette\Security\User
	 */
	private function logOut()
	{
		/** @var \Nette\Security\User $user */
		$user = $this->getContainer()->getByType('Nette\Security\User');
		$user->logout();
		return $user;
	}

	/**
	 * @return \Nette\Application\UI\Presenter
	 */
	private function getPresenter()
	{
		return $this->_presenter;
	}

	/**
	 * @return integer
	 */
	private function getReturnCode()
	{
		return $this->_httpCode;
	}

	/**
	 * @return \Exception
	 */
	private function getException()
	{
		return $this->_exception;
	}

}
