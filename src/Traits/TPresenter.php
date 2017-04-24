<?php

namespace Testbench;

use Tester\Assert;
use Tester\Dumper;

trait TPresenter
{

	/** @var \Nette\Application\IPresenter */
	private $__testbench_presenter;

	private $__testbench_httpCode;

	private $__testbench_exception;

	private $__testbench_ajaxMode = FALSE;

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
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
		$url = new \Nette\Http\UrlScript($container->parameters['testbench']['url']);
		$container->addService('httpRequest', new Mocks\HttpRequestMock($url, $params, $post, [], [], $headers));
		$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');
		$this->__testbench_presenter = $presenterFactory->createPresenter($presenter);
		$this->__testbench_presenter->autoCanonicalize = FALSE;
		$this->__testbench_presenter->invalidLinkMode = \Nette\Application\UI\Presenter::INVALID_LINK_EXCEPTION;

		$postCopy = $post;
		if (isset($params['do'])) {
			foreach ($post as $key => $field) {
				if (is_array($field) && array_key_exists(\Nette\Forms\Form::REQUIRED, $field)) {
					$post[$key] = $field[0];
				}
			}
		}

		/** @var \Kdyby\FakeSession\Session $session */
		$session = $this->__testbench_presenter->getSession();
		$session->setFakeId('testbench.fakeId');
		$session->getSection('Nette\Forms\Controls\CsrfProtection')->token = 'testbench.fakeToken';
		$post = $post + ['_token_' => 'goVdCQ1jk0UQuVArz15RzkW6vpDU9YqTRILjE=']; //CSRF magic! ¯\_(ツ)_/¯

		$request = new Mocks\ApplicationRequestMock(
			$presenter,
			$post ? 'POST' : 'GET',
			['action' => $action] + $params,
			$post
		);
		try {
			$this->__testbench_httpCode = 200;
			$this->__testbench_exception = NULL;
			$response = $this->__testbench_presenter->run($request);

			if (isset($params['do'])) {
				if (preg_match('~(.+)-submit$~', $params['do'], $matches)) {
					/** @var \Nette\Application\UI\Form $form */
					$form = $this->__testbench_presenter->getComponent($matches[1]);
					foreach ($form->getControls() as $control) {
						if (array_key_exists($control->getName(), $postCopy)) {
							$subvalues = $postCopy[$control->getName()];
							$rq = \Nette\Forms\Form::REQUIRED;
							if (is_array($subvalues) && array_key_exists($rq, $subvalues) && $subvalues[$rq]) {
								if ($control->isRequired() !== TRUE) {
									Assert::fail("field '{$control->name}' should be defined as required, but it's not");
								}
							}
						}
						if ($control->hasErrors()) {
							$errors = '';
							$counter = 1;
							foreach ($control->getErrors() as $error) {
								$errors .= "  - $error\n";
								$counter++;
							}
							Assert::fail("field '{$control->name}' returned this error(s):\n$errors");
						}
					}
					foreach ($form->getErrors() as $error) {
						Assert::fail($error);
					}
				}
			}

			return $response;
		} catch (\Exception $exc) {
			$this->__testbench_exception = $exc;
			$this->__testbench_httpCode = $exc->getCode();
			throw $exc;
		}
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
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

			$html = (string)$response->getSource();
			//DOMDocument doesn't handle HTML tags inside of script tags very well
			$html = preg_replace('~<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>~', '', $html); //http://stackoverflow.com/a/6660315/3135248
			$dom = @\Tester\DomQuery::fromHtml($html);
			Assert::true($dom->has('html'), "missing 'html' tag");
			Assert::true($dom->has('title'), "missing 'title' tag");
			Assert::true($dom->has('body'), "missing 'body' tag");
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
		return $this->checkRedirect($destination, FALSE, [
				'do' => $signal,
			] + $params, $post);
	}

	protected function checkAjaxSignal($destination, $signal, $params = [], $post = [])
	{
		$this->__testbench_ajaxMode = TRUE;
		$response = $this->check($destination, [
				'do' => $signal,
			] + $params, $post);
		Assert::true($this->__testbench_presenter->isAjax());
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\JsonResponse', $response);
		}
		$this->__testbench_ajaxMode = FALSE;
		return $response;
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param string $path
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
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
			if ($path) {
				if (!\Tester\Assert::isMatching("~^https?://test\.bench{$path}(?(?=\?).+)$~", $response->getUrl())) {
					$path = Dumper::color('yellow') . Dumper::toLine($path) . Dumper::color('white');
					$url = Dumper::color('yellow') . Dumper::toLine($response->getUrl()) . Dumper::color('white');
					$originalUrl = new \Nette\Http\Url($response->getUrl());
					Assert::fail(
						str_repeat(' ', strlen($originalUrl->getHostUrl()) - 13) // strlen('Failed: path ') = 13
						. "path $path doesn't match\n$url\nafter redirect"
					);
				}
			}
		}
		return $response;
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
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
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $scheme what is expected
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 */
	public function checkJsonScheme($destination, array $scheme, $params = [], $post = [])
	{
		$response = $this->checkJson($destination, $params, $post);
		Assert::same($scheme, $response->getPayload());
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param string $formName
	 * @param array $post provided to the presenter via POST
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

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param $formName
	 * @param array $post provided to the presenter via POST
	 * @param string|bool $path
	 *
	 * @return \Nette\Application\IResponse
	 * @throws \Exception
	 */
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
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
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
			Assert::true($dom->has('rss'), "missing 'rss' element");
			Assert::true($dom->has('channel'), "missing 'channel' element");
			Assert::true($dom->has('title'), "missing 'title' element");
			Assert::true($dom->has('link'), "missing 'link' element");
			Assert::true($dom->has('item'), "missing 'item' element");
		}
		return $response;
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
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

			$xml = \Tester\DomQuery::fromXml($response->getSource());
			Assert::same('urlset', $xml->getName(), 'root element is');
			$url = $xml->children();
			Assert::same('url', $url->getName(), "child of 'urlset'");
			Assert::same('loc', $url->children()->getName(), "child of 'url'");
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
	 * @return bool
	 */
	protected function isUserLoggedIn()
	{
		/** @var \Nette\Security\User $user */
		$user = \Testbench\ContainerFactory::create(FALSE)->getByType('Nette\Security\User');
		return $user->isLoggedIn();
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
