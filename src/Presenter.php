<?php

namespace Test;

use Nette;
use Tester;

/**
 * TODO: test sitemap & RSS
 * Class Presenter
 * @package Test
 */
class Presenter extends Nette\Object {

	/** @var \Nette\DI\Container */
	private $container;
	/** @var Nette\Application\UI\Presenter */
	private $presenter;
	private $presName;
	private $code;

	const GET = 'GET';
	const POST = 'POST';

	/**
	 * @param Nette\DI\Container $container
	 */
	public function __construct(Nette\DI\Container $container) {
		$this->container = $container;
	}

	/**
	 * @param $presName string Fully qualified presenter name.
	 */
	public function init($presName) {
		$presenterFactory = $this->container->getByType('Nette\Application\IPresenterFactory');
		$this->presenter = $presenterFactory->createPresenter($presName);
		$this->presenter->autoCanonicalize = FALSE;
		$this->presName = $presName;
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 * @return mixed
	 */
	public function test($action, $method = self::GET, $params = [], $post = []) {
		$params['action'] = $action;
		$request = new Nette\Application\Request($this->presName, $method, $params, $post);
		try {
			$this->code = 200;
			$response = $this->presenter->run($request);
			return $response;
		} catch (\Exception $exc) {
			$this->code = $exc->getCode();
		}
		return NULL;
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 * @return mixed
	 */
	public function testAction($action, $method = self::GET, $params = [], $post = []) {
		$response = $this->test($action, $method, $params, $post);
		if ($response) {
			Tester\Assert::true($response instanceof Nette\Application\Responses\TextResponse);
			Tester\Assert::true($response->getSource() instanceof Nette\Application\UI\ITemplate);

			$html = (string)$response->getSource();
			$dom = @Tester\DomQuery::fromHtml($html);
			Tester\Assert::true($dom->has('html'));
			Tester\Assert::true($dom->has('title'));
			Tester\Assert::true($dom->has('body'));
		}
		return $response;
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 * @return mixed
	 */
	public function testJson($action, $method = self::GET, $params = [], $post = []) {
		$response = $this->test($action, $method, $params, $post);
		Tester\Assert::true($response instanceof Nette\Application\Responses\JsonResponse);
		Tester\Assert::same('application/json', $response->getContentType());
		return $response;
	}

	/**
	 * @param $action
	 * @param string $method
	 * @param array $post
	 * @return mixed
	 */
	public function testForm($action, $method = self::POST, $post = []) {
		$response = $this->test($action, $method, $post);
		Tester\Assert::true($response instanceof Nette\Application\Responses\RedirectResponse);
		return $response;
	}

	/**
	 * @param int $id
	 * @param null $roles
	 * @param null $data
	 * @return object
	 */
	public function logIn($id = 1, $roles = NULL, $data = NULL) {
		$identity = new Nette\Security\Identity($id, $roles, $data);
		$user = $this->container->getByType('Nette\Security\User');
		$user->login($identity);
		return $user;
	}

	public function logOut() {
		$user = $this->container->getByType('Nette\Security\User');
		$user->logout();
		return $user;
	}

	/**
	 * @return Nette\Application\UI\Presenter
	 */
	public function getPresenter() {
		return $this->presenter;
	}

	public function getReturnCode() {
		return $this->code;
	}

}
