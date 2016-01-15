<?php

namespace Testbench;

use Nette;
use Nette\Http\Request as HttpRequest;
use Tester;

//FIXME: uzavírat presentery v rámci jednoho vlákna po otestování
trait TPresenter
{

	use TCompiledContainer;

	/** @var Nette\Application\IPresenter */
	private $presenter;

	private $httpCode;

	private $exception;

	/**
	 * @param string $action
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function check($action, $params = [], $post = [])
	{
		if (!$this->presenter) {
			$action = $this->openPresenter($action);
		}
		$request = new Nette\Application\Request(
			$this->presenter->getName(),
			$post ? 'POST' : 'GET',
			['action' => $action] + $params,
			$post
		);
		try {
			$this->httpCode = 200;
			$response = $this->presenter->run($request);
			return $response;
		} catch (\Exception $exc) {
			$this->exception = $exc;
			$this->httpCode = $exc->getCode();
			throw $exc;
		}
	}

	/**
	 * @param string $action
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\Responses\TextResponse
	 * @throws \Exception
	 */
	public function checkAction($action, $params = [], $post = [])
	{
		/** @var Nette\Application\Responses\TextResponse $response */
		$response = $this->check($action, $params, $post);
		if (!$this->exception) {
			Tester\Assert::same(200, $this->getReturnCode());
			Tester\Assert::type('Nette\Application\Responses\TextResponse', $response);
			Tester\Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$dom = @Tester\DomQuery::fromHtml($response->getSource()); // @ - not valid HTML
			Tester\Assert::true($dom->has('html'));
			Tester\Assert::true($dom->has('title'));
			Tester\Assert::true($dom->has('body'));
		}
		return $response;
	}

	/**
	 * @param string $action
	 * @param string $signal
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 */
	public function checkSignal($action, $signal, $params = [], $post = [])
	{
		return $this->checkRedirect($action, '/', [
				'do' => $signal,
			] + $params, $post);
	}

	/**
	 * @param string $action
	 * @param string $path
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\Responses\RedirectResponse
	 * @throws \Exception
	 */
	public function checkRedirect($action, $path = '/', $params = [], $post = [])
	{
		/** @var Nette\Application\Responses\RedirectResponse $response */
		$response = $this->check($action, $params, $post);
		if (!$this->exception) {
			Tester\Assert::same(200, $this->getReturnCode());
			Tester\Assert::same(302, $response->getCode());
			Tester\Assert::type('Nette\Application\Responses\RedirectResponse', $response);
			Tester\Assert::match("~^https?://fake\.url{$path}[a-z0-9?&=_/]*$~", $response->getUrl());
		}
		return $response;
	}

	/**
	 * @param string $action
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\Responses\JsonResponse
	 * @throws \Exception
	 */
	public function checkJson($action, $params = [], $post = [])
	{
		/** @var Nette\Application\Responses\JsonResponse $response */
		$response = $this->check($action, $params, $post);
		if (!$this->exception) {
			Tester\Assert::same(200, $this->getReturnCode());
			Tester\Assert::type('Nette\Application\Responses\JsonResponse', $response);
			Tester\Assert::same('application/json', $response->getContentType());
		}
		return $response;
	}

	/**
	 * @param string $action
	 * @param string $formName
	 * @param array $post
	 *
	 * @return Nette\Application\Responses\RedirectResponse
	 */
	public function checkForm($action, $formName, $post = [])
	{
		return $this->checkRedirect($action, '/', [
			'do' => $formName . '-submit',
		], $post);
	}

	/**
	 * @param string $action
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\Responses\TextResponse
	 * @throws \Exception
	 */
	public function checkRss($action, $params = [], $post = [])
	{
		/** @var Nette\Application\Responses\TextResponse $response */
		$response = $this->check($action, $params, $post);
		if (!$this->exception) {
			Tester\Assert::same(200, $this->getReturnCode());
			Tester\Assert::type('Nette\Application\Responses\TextResponse', $response);
			Tester\Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$dom = Tester\DomQuery::fromXml($response->getSource());
			Tester\Assert::true($dom->has('rss'));
			Tester\Assert::true($dom->has('channel'));
			Tester\Assert::true($dom->has('title'));
			Tester\Assert::true($dom->has('link'));
			Tester\Assert::true($dom->has('item'));
		}
		return $response;
	}

	/**
	 * @param string $action
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\Responses\TextResponse
	 * @throws \Exception
	 */
	public function checkSitemap($action, $params = [], $post = [])
	{
		/** @var Nette\Application\Responses\TextResponse $response */
		$response = $this->check($action, $params, $post);
		if (!$this->exception) {
			Tester\Assert::same(200, $this->getReturnCode());
			Tester\Assert::type('Nette\Application\Responses\TextResponse', $response);
			Tester\Assert::type('Nette\Application\UI\ITemplate', $response->getSource());

			$dom = @Tester\DomQuery::fromHtml($response->getSource()); // @ - not valid HTML
			Tester\Assert::true($dom->has('urlset'));
			Tester\Assert::true($dom->has('url'));
			Tester\Assert::true($dom->has('loc'));
		}
		return $response;
	}

	/**
	 * @param integer $id
	 * @param null $roles
	 * @param null $data
	 *
	 * @return Nette\Security\User
	 */
	public function logIn($id = 1, $roles = NULL, $data = NULL)
	{
		$identity = new Nette\Security\Identity($id, $roles, $data);
		/** @var Nette\Security\User $user */
		$user = $this->getContainer()->getByType('Nette\Security\User');
		$user->login($identity);
		return $user;
	}

	/**
	 * @return Nette\Security\User
	 */
	public function logOut()
	{
		/** @var Nette\Security\User $user */
		$user = $this->getContainer()->getByType('Nette\Security\User');
		$user->logout();
		return $user;
	}

	/**
	 * @return Nette\Application\UI\Presenter
	 */
	public function getPresenter()
	{
		return $this->presenter;
	}

	/**
	 * @return integer
	 */
	public function getReturnCode()
	{
		return $this->httpCode;
	}

	/**
	 * @return \Exception
	 */
	public function getException()
	{
		return $this->exception;
	}

	private function openPresenter($destination)
	{
		/**
		 * @var Nette\DI\Container $container
		 * @var TCompiledContainer $this
		 */
		$container = $this->getContainer();
		$fakeUrl = new Nette\Http\UrlScript('http://fake.url/');
		$container->removeService('httpRequest');
		$container->addService('httpRequest', new HttpRequest($fakeUrl, NULL, [], [], [], [], PHP_SAPI, '127.0.0.1', '127.0.0.1'));
		/** @var Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');

		$destination = ltrim($destination, ':');
		$pos = strrpos($destination, ':');
		$presenter = substr($destination, 0, $pos);
		$action = substr($destination, $pos + 1) ?: 'default';

		$class = $presenterFactory->getPresenterClass($presenter);
		if (!class_exists($overriddenPresenter = 'Testbench\\' . $class)) {
			$classPos = strrpos($class, '\\');
			$namespace = substr($class, 0, $classPos);
			$namespace = $namespace ? '\\' . $namespace : '';
			$className = substr($class, $namespace ? $classPos + 1 : $classPos);
			eval('namespace Testbench' . $namespace . '; class ' . $className . ' extends \\' . $class . ' { '
				. 'public function startup() { if ($this->getParameter("__terminate") === TRUE) { $this->terminate(); } parent::startup(); } '
				. 'public static function getReflection() { return parent::getReflection()->getParentClass(); } '
				. '}');
		}
		$this->presenter = $container->createInstance($overriddenPresenter);
		$container->callInjects($this->presenter);
		$this->presenter->autoCanonicalize = FALSE;
		$this->presenter->run(new Nette\Application\Request($presenter, 'GET', ['action' => $action, '__terminate' => TRUE]));
		return $action;
	}

}
