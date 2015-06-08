<?php

namespace Test;

use Nette;
use Nette\Http\Request as HttpRequest;
use Tester;

trait PresenterTester
{

	use CompiledContainer;

	/** @var Nette\Application\IPresenter */
	private $presenter;

	private $httpCode;
	private $exception;

	protected function openPresenter($fqa)
	{
		/**
		 * @var Nette\DI\Container $container
		 * @var CompiledContainer $this
		 */
		$container = $this->getContainer();
		$fakeUrl = new Nette\Http\UrlScript('http://fake.url/');
		$container->removeService('httpRequest');
		$container->addService('httpRequest', new HttpRequest($fakeUrl, NULL, [], [], [], [], PHP_SAPI, '127.0.0.1', '127.0.0.1'));
		/** @var Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = $container->getByType('Nette\Application\IPresenterFactory');
		$name = substr($fqa, 0, $namePos = strrpos($fqa, ':'));
		$class = $presenterFactory->getPresenterClass($name);
		if (!class_exists($overriddenPresenter = 'AutomaticTests\\' . $class)) {
			$classPos = strrpos($class, '\\');
			$namespace = substr($class, 0, $classPos);
			$namespace = $namespace ? '\\' . $namespace : '';
			$className = substr($class, $namespace ? $classPos + 1 : $classPos);
			eval('namespace AutomaticTests' . $namespace . '; class ' . $className . ' extends \\' . $class . ' { '
				. 'public function startup() { if ($this->getParameter("__terminate") == TRUE) { $this->terminate(); } parent::startup(); } '
				. 'public static function getReflection() { return parent::getReflection()->getParentClass(); } '
				. '}');
		}
		$this->presenter = $container->createInstance($overriddenPresenter);
		$container->callInjects($this->presenter);
		$this->presenter->autoCanonicalize = FALSE;
		$this->presenter->run(new Nette\Application\Request($name, 'GET', ['action' => substr($fqa, $namePos + 1) ?: 'default', '__terminate' => TRUE]));
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function check($action, $method = 'GET', $params = [], $post = [])
	{
		if (!$this->presenter) {
			throw new \LogicException("You have to open the presenter using \$this->openPresenter(\$name); before calling actions");
		}
		$request = new Nette\Application\Request($this->presenter->getName(), $method, ['action' => $action] + $params, $post);
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
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function checkAction($action, $method = 'GET', $params = [], $post = [])
	{
		$response = $this->check($action, $method, $params, $post);
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
	 * @param $action
	 * @param $signal
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 */
	public function checkSignal($action, $signal, $params = [], $post = [])
	{
		return $this->checkRedirect($action, $post ? 'POST' : 'GET', [
				'do' => $signal,
			] + $params, $post);
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function checkRedirect($action, $method = 'GET', $params = [], $post = [])
	{
		$response = $this->check($action, $method, $params, $post);
		if (!$this->exception) {
			Tester\Assert::same(200, $this->getReturnCode());
			Tester\Assert::type('Nette\Application\Responses\RedirectResponse', $response);
		}
		return $response;
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function checkJson($action, $method = 'GET', $params = [], $post = [])
	{
		$response = $this->check($action, $method, $params, $post);
		if (!$this->exception) {
			Tester\Assert::same(200, $this->getReturnCode());
			Tester\Assert::type('Nette\Application\Responses\JsonResponse', $response);
			Tester\Assert::same('application/json', $response->getContentType());
		}
		return $response;
	}

	/**
	 * @param $action
	 * @param $formName
	 * @param array $post
	 * @param string $method
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function checkForm($action, $formName, $post = [], $method = 'POST')
	{
		return $this->checkRedirect($action, $method, [
			'do' => $formName . '-submit',
		], $post);
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function checkRss($action, $method = 'GET', $params = [], $post = [])
	{
		$response = $this->check($action, $method, $params, $post);
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
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function checkSitemap($action, $method = 'GET', $params = [], $post = [])
	{
		$response = $this->check($action, $method, $params, $post);
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
	 * @param int $id
	 * @param null $roles
	 * @param null $data
	 *
	 * @return Nette\Security\User
	 */
	public function logIn($id = 1, $roles = NULL, $data = NULL)
	{
		$identity = new Nette\Security\Identity($id, $roles, $data);
		/** @var Nette\Security\User $user */
		$user = $this->container->getByType('Nette\Security\User');
		$user->login($identity);
		return $user;
	}

	/**
	 * @return Nette\Security\User
	 */
	public function logOut()
	{
		/** @var Nette\Security\User $user */
		$user = $this->container->getByType('Nette\Security\User');
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

}
