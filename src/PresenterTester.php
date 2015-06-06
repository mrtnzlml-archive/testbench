<?php

namespace Test;

use Nette;
use Nette\Http\Request as HttpRequest;
use Tester;

/**
 * Class PresenterTester
 * @package Test
 */
class PresenterTester extends Nette\Object
{

	/** @var \Nette\DI\Container */
	private $container;
	/** @var Nette\Application\UI\Presenter */
	private $presenter;
	/** @var string Presenter name. */
	private $presName;

	private $httpCode;
	/** @var \Exception */
	private $exception;

	const GET = 'GET';
	const POST = 'POST';

	/**
	 * @param Nette\DI\Container $container
	 * @param null $presName string Presenter name.
	 */
	public function __construct(Nette\DI\Container $container, $presName = NULL)
	{
		$this->container = $container;
		if ($presName !== NULL) {
			$this->setUpPresenter($presName);
		}
	}

	/**
	 * @param $presName string Presenter name.
	 */
	public function init($presName)
	{
		$this->setUpPresenter($presName);
	}

	/**
	 * @param $presName string Presenter name.
	 */
	private function setUpPresenter($presName)
	{
		/** @var Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');

		$fakeUrl = new Nette\Http\UrlScript('localhost');
		$this->container->removeService('httpRequest');
		$this->container->addService('httpRequest', new HttpRequest($fakeUrl, NULL, [], [], [], [], PHP_SAPI, '127.0.0.1', '127.0.0.1'));
		$class = $presenterFactory->getPresenterClass($presName);
		if (!class_exists($overriddenPresenter = $class)) {
			eval(
				'namespace Test; class ' . $class . ' extends \\' . $class . ' { '
				. 'protected function startup() { if ($this->getParameter("__terminate") == TRUE) { $this->terminate(); } parent::startup(); } '
				. '}'
			);
		}
		$this->presenter = $this->container->createInstance($overriddenPresenter);
		$this->container->callInjects($this->presenter);
		$this->presenter->autoCanonicalize = FALSE;
		$this->presName = $presName;
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
	public function test($action, $method = self::GET, $params = [], $post = [])
	{
		if (!$this->presenter) {
			throw new \LogicException('Presenter is not set. Use init method or second parameter in constructor.');
		}
		$params = [
				'action' => $action,
				'__terminate' => TRUE,
			] + $params;
		$request = new Nette\Application\Request($this->presName, $method, $params, $post);
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
	public function testAction($action, $method = self::GET, $params = [], $post = [])
	{
		$response = $this->test($action, $method, $params, $post);
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
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 */
	public function testSignal($action, $signal, $method = self::GET, $params = [], $post = [])
	{
		return $this->testRedirect($action, $method, [
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
	public function testRedirect($action, $method = self::GET, $params = [], $post = [])
	{
		$response = $this->test($action, $method, $params, $post);
		if (!$this->exception) {
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
	public function testJson($action, $method = self::GET, $params = [], $post = [])
	{
		$response = $this->test($action, $method, $params, $post);
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
	public function testForm($action, $formName, $post = [], $method = self::POST)
	{
		$response = $this->test($action, $method, [
			'do' => $formName . '-submit',
		], $post);
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
	public function testRss($action, $method = self::GET, $params = [], $post = [])
	{
		$response = $this->test($action, $method, $params, $post);
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
	public function testSitemap($action, $method = self::GET, $params = [], $post = [])
	{
		$response = $this->test($action, $method, $params, $post);
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
