<?php

namespace Testbench;

use Tester\Assert;

trait TPresenter
{

	/** @var \Nette\Application\IPresenter */
	private $__testbench_presenter;

	private $__testbench_httpCode;

	private $__testbench_exception;

	private $__testbench_ajaxMode = FALSE;

	/**
	 * @param string $destination
	 * @param array $params
	 * @param array $post
	 *
	 * @return \Nette\Application\IResponse
	 * @throws \Exception
	 */
	protected function check($destination, $params = [], $post = [])
	{
		$destination = ltrim($destination, ':');
		$pos = strrpos($destination, ':');
		$presenter = substr($destination, 0, $pos);
		$action = substr($destination, $pos + 1) ?: 'default';

		$container = \Testbench\ContainerFactory::create(FALSE);
		$container->removeService('httpRequest');
		$headers = $this->__testbench_ajaxMode ? ['X-Requested-With' => 'XMLHttpRequest'] : [];
		$container->addService('httpRequest', new HttpRequestMock(NULL, NULL, [], [], [], $headers));
		$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');
		$class = $presenterFactory->getPresenterClass($presenter);
		$this->__testbench_presenter = $container->createInstance($class);
		$this->__testbench_presenter->autoCanonicalize = FALSE;
		$this->__testbench_presenter->invalidLinkMode = \Nette\Application\UI\Presenter::INVALID_LINK_EXCEPTION;
		$container->callInjects($this->__testbench_presenter);

		/** @var \Kdyby\FakeSession\Session $session */
		$session = $this->__testbench_presenter->getSession();
		$session->setFakeId('testbench.fakeId');
		$session->getSection('Nette\Forms\Controls\CsrfProtection')->token = 'testbench.fakeToken';
		$post = $post + ['_token_' => 'goVdCQ1jk0UQuVArz15RzkW6vpDU9YqTRILjE=']; //CSRF magic! ¯\_(ツ)_/¯

		$request = new ApplicationRequestMock(
			$presenter,
			$post ? 'POST' : 'GET',
			['action' => $action] + $params,
			$post
		);
		try {
			$this->__testbench_httpCode = 200;
			$response = $this->__testbench_presenter->run($request);
			return $response;
		} catch (\Exception $exc) {
			$this->__testbench_exception = $exc;
			$this->__testbench_httpCode = $exc->getCode();
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
	protected function checkAction($destination, $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
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
	protected function checkSignal($destination, $signal, $params = [], $post = [])
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
	protected function checkRedirect($destination, $path = '/', $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\RedirectResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
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
	protected function checkJson($destination, $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\JsonResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
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
	protected function checkForm($destination, $formName, $post = [], $path = '/')
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
			if (!$this->__testbench_exception) {
				Assert::same(200, $this->getReturnCode());
				Assert::type('Nette\Application\Responses\TextResponse', $response);
			}
			return $response;
		} else {
			\Tester\Assert::fail('Path should be string or boolean (probably FALSE).');
		}
	}

	protected function checkAjaxForm($destination, $formName, $post = [], $path = FALSE)
	{
		if (is_string($path)) {
			$this->checkForm($destination, $formName, $post, $path);
			Assert::false($this->__testbench_presenter->isAjax());
		}
		$this->__testbench_presenter = NULL; //FIXME: not very nice, but performance first
		$this->__testbench_ajaxMode = TRUE;
		$response = $this->check($destination, [
			'do' => $formName . '-submit',
		], $post);
		Assert::true($this->__testbench_presenter->isAjax());
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\JsonResponse', $response);
		}
		$this->__testbench_presenter = NULL;
		$this->__testbench_ajaxMode = FALSE;
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
	protected function checkRss($destination, $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
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
	protected function checkSitemap($destination, $params = [], $post = [])
	{
		/** @var \Nette\Application\Responses\TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
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
	 * @param \Nette\Security\IIdentity|integer $id
	 * @param array|null $roles
	 * @param array|null $data
	 *
	 * @return \Nette\Security\User
	 */
	protected function logIn($id = 1, $roles = NULL, $data = NULL)
	{
		if ($id instanceof \Nette\Security\IIdentity) {
			$identity = $id;
		} else {
			$identity = new \Nette\Security\Identity($id, $roles, $data);
		}
		/** @var \Nette\Security\User $user */
		$user = \Testbench\ContainerFactory::create(FALSE)->getByType('Nette\Security\User');
		$user->login($identity);
		return $user;
	}

	/**
	 * @return \Nette\Security\User
	 */
	protected function logOut()
	{
		/** @var \Nette\Security\User $user */
		$user = \Testbench\ContainerFactory::create(FALSE)->getByType('Nette\Security\User');
		$user->logout();
		return $user;
	}

	/**
	 * @return \Nette\Application\UI\Presenter
	 */
	protected function getPresenter()
	{
		return $this->__testbench_presenter;
	}

	/**
	 * @return integer
	 */
	protected function getReturnCode()
	{
		return $this->__testbench_httpCode;
	}

	/**
	 * @return \Exception
	 */
	protected function getException()
	{
		return $this->__testbench_exception;
	}

}
