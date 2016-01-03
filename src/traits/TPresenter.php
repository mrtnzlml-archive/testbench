<?php

namespace Ant\Tests;

use Nette;
use Nette\Http\Request as HttpRequest;
use Tester;

trait TPresenter
{

	use TDatabaseSetup;

	/** @var Nette\Application\IPresenter */
	private $presenter;

	private $httpCode;

	private $exception;

	protected function openPresenter($fqa)
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
	 * @param array $params
	 * @param array $post
	 *
	 * @return Nette\Application\IResponse
	 * @throws \Exception
	 */
	public function check($action, $params = [], $post = [])
	{
		if (!$this->presenter) {
			throw new \LogicException("You have to open the presenter using \$this->openPresenter(\$name); before calling actions");
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
	 * @param $action
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
	 * @return integer
	 */
	public function getReturnCode()
	{
		return $this->httpCode;
	}

}
