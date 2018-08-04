<?php declare(strict_types = 1);

namespace Testbench;

use Exception;
use Kdyby\FakeSession\Session;
use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Forms\Form;
use Nette\Http\Url;
use Nette\Http\UrlScript;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Tester\Assert;
use Tester\AssertException;
use Tester\DomQuery;
use Tester\Dumper;
use Throwable;

trait TPresenter
{

	/** @var IPresenter */
	private $__testbench_presenter;

	private $__testbench_httpCode;

	private $__testbench_exception;

	private $__testbench_ajaxMode = false;

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 * @throws Exception
	 */
	protected function check(string $destination, array $params = [], array $post = []): IResponse
	{
		$destination = ltrim($destination, ':');
		$pos = strrpos($destination, ':');
		$presenter = substr($destination, 0, $pos);
		$action = substr($destination, $pos + 1) ?: 'default';

		$container = ContainerFactory::create(false);
		$container->removeService('httpRequest');
		$headers = $this->__testbench_ajaxMode ? ['X-Requested-With' => 'XMLHttpRequest'] : [];
		$url = new UrlScript($container->parameters['testbench']['url']);
		$container->addService('httpRequest', new Mocks\HttpRequestMock($url, $params, $post, [], [], $headers));
		$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');
		$this->__testbench_presenter = $presenterFactory->createPresenter($presenter);
		$this->__testbench_presenter->autoCanonicalize = false;
		$this->__testbench_presenter->invalidLinkMode = Presenter::INVALID_LINK_EXCEPTION;

		$postCopy = $post;
		if (isset($params['do'])) {
			foreach ($post as $key => $field) {
				if (is_array($field) && array_key_exists(Form::REQUIRED, $field)) {
					$post[$key] = $field[0];
				}
			}
		}

		/** @var Session $session */
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
			$this->__testbench_exception = null;
			$response = $this->__testbench_presenter->run($request);

			if (isset($params['do'])) {
				if (preg_match('~(.+)-submit$~', $params['do'], $matches)) {
					/** @var \Nette\Application\UI\Form $form */
					$form = $this->__testbench_presenter->getComponent($matches[1]);
					foreach ($form->getControls() as $control) {
						if (array_key_exists($control->getName(), $postCopy)) {
							$subvalues = $postCopy[$control->getName()];
							$rq = Form::REQUIRED;
							if (is_array($subvalues) && array_key_exists($rq, $subvalues) && $subvalues[$rq]) {
								if ($control->isRequired() !== true) {
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
		} catch (Throwable $exc) {
			$this->__testbench_exception = $exc;
			$this->__testbench_httpCode = $exc->getCode();
			throw $exc;
		}
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 * @throws Exception
	 */
	protected function checkAction(string $destination, array $params = [], array $post = []): TextResponse
	{
		/** @var TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\TextResponse', $response);
			Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$html = (string) $response->getSource();
			//DOMDocument doesn't handle HTML tags inside of script tags very well
			$html = preg_replace('~<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>~', '', $html); //http://stackoverflow.com/a/6660315/3135248
			$dom = @DomQuery::fromHtml($html);
			Assert::true($dom->has('html'), "missing 'html' tag");
			Assert::true($dom->has('title'), "missing 'title' tag");
			Assert::true($dom->has('body'), "missing 'body' tag");
		}
		return $response;
	}

	/**
	 * @param array $params
	 * @param array $post
	 */
	protected function checkSignal(string $destination, string $signal, array $params = [], array $post = []): IResponse
	{
		return $this->checkRedirect($destination, false, [
				'do' => $signal,
			] + $params, $post);
	}

	protected function checkAjaxSignal($destination, $signal, $params = [], $post = [])
	{
		$this->__testbench_ajaxMode = true;
		$response = $this->check($destination, [
				'do' => $signal,
			] + $params, $post);
		Assert::true($this->__testbench_presenter->isAjax());
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\JsonResponse', $response);
		}
		$this->__testbench_ajaxMode = false;
		return $response;
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 * @throws Exception
	 */
	protected function checkRedirect(string $destination, string $path = '/', array $params = [], array $post = []): RedirectResponse
	{
		/** @var RedirectResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\RedirectResponse', $response);
			Assert::same(302, $response->getCode());
			if ($path) {
				if (!Assert::isMatching("~^https?://test\.bench{$path}(?(?=\?).+)$~", $response->getUrl())) {
					$path = Dumper::color('yellow') . Dumper::toLine($path) . Dumper::color('white');
					$url = Dumper::color('yellow') . Dumper::toLine($response->getUrl()) . Dumper::color('white');
					$originalUrl = new Url($response->getUrl());
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
	 * @throws Exception
	 */
	protected function checkJson(string $destination, array $params = [], array $post = []): JsonResponse
	{
		/** @var JsonResponse $response */
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
	public function checkJsonScheme(string $destination, array $scheme, array $params = [], array $post = []): void
	{
		$response = $this->checkJson($destination, $params, $post);
		Assert::same($scheme, $response->getPayload());
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $post provided to the presenter via POST
	 * @param string|bool $path Path after redirect or FALSE if it's form without redirect
	 * @throws AssertException
	 */
	protected function checkForm(string $destination, string $formName, array $post = [], $path = '/'): RedirectResponse
	{
		if (is_string($path)) {
			return $this->checkRedirect($destination, $path, [
				'do' => $formName . '-submit',
			], $post);
		} elseif (is_bool($path)) {
			/** @var RedirectResponse $response */
			$response = $this->check($destination, [
				'do' => $formName . '-submit',
			], $post);
			if (!$this->__testbench_exception) {
				Assert::same(200, $this->getReturnCode());
				Assert::type('Nette\Application\Responses\TextResponse', $response);
			}
			return $response;
		} else {
			Assert::fail('Path should be string or boolean (probably FALSE).');
		}
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param $formName
	 * @param array $post provided to the presenter via POST
	 * @param string|bool $path
	 * @throws Exception
	 */
	protected function checkAjaxForm(string $destination, $formName, array $post = [], $path = false): IResponse
	{
		if (is_string($path)) {
			$this->checkForm($destination, $formName, $post, $path);
			Assert::false($this->__testbench_presenter->isAjax());
		}
		$this->__testbench_presenter = null; //FIXME: not very nice, but performance first
		$this->__testbench_ajaxMode = true;
		$response = $this->check($destination, [
			'do' => $formName . '-submit',
		], $post);
		Assert::true($this->__testbench_presenter->isAjax());
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\JsonResponse', $response);
		}
		$this->__testbench_presenter = null;
		$this->__testbench_ajaxMode = false;
		return $response;
	}

	/**
	 * @param string $destination fully qualified presenter name (module:module:presenter)
	 * @param array $params provided to the presenter usually via URL
	 * @param array $post provided to the presenter via POST
	 * @throws Exception
	 */
	protected function checkRss(string $destination, array $params = [], array $post = []): TextResponse
	{
		/** @var TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\TextResponse', $response);
			Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$dom = DomQuery::fromXml($response->getSource());
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
	 * @throws Exception
	 */
	protected function checkSitemap(string $destination, array $params = [], array $post = []): TextResponse
	{
		/** @var TextResponse $response */
		$response = $this->check($destination, $params, $post);
		if (!$this->__testbench_exception) {
			Assert::same(200, $this->getReturnCode());
			Assert::type('Nette\Application\Responses\TextResponse', $response);
			Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$xml = DomQuery::fromXml($response->getSource());
			Assert::same('urlset', $xml->getName(), 'root element is');
			$url = $xml->children();
			Assert::same('url', $url->getName(), "child of 'urlset'");
			Assert::same('loc', $url->children()->getName(), "child of 'url'");
		}
		return $response;
	}

	/**
	 * @param IIdentity|int $id
	 * @param array|null $roles
	 * @param array|null $data
	 */
	protected function logIn($id = 1, $roles = null, $data = null): User
	{
		if ($id instanceof IIdentity) {
			$identity = $id;
		} else {
			$identity = new Identity($id, $roles, $data);
		}
		/** @var User $user */
		$user = ContainerFactory::create(false)->getByType('Nette\Security\User');
		$user->login($identity);
		return $user;
	}

	protected function logOut(): User
	{
		/** @var User $user */
		$user = ContainerFactory::create(false)->getByType('Nette\Security\User');
		$user->logout();
		return $user;
	}

	protected function isUserLoggedIn(): bool
	{
		/** @var User $user */
		$user = ContainerFactory::create(false)->getByType('Nette\Security\User');
		return $user->isLoggedIn();
	}

	protected function getPresenter(): Presenter
	{
		return $this->__testbench_presenter;
	}

	protected function getReturnCode(): int
	{
		return $this->__testbench_httpCode;
	}

	protected function getException(): Throwable
	{
		return $this->__testbench_exception;
	}

}
