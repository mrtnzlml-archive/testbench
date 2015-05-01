<?php

namespace Test;

use Nette;
use Tester;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class PresenterTest extends Tester\TestCase {

	/**
	 * @var PresenterTester
	 */
	private $tester;

	public function __construct(Nette\DI\Container $container) {
		$this->tester = new PresenterTester($container, 'Presenter');
	}

	public function testClassicRender() {
		$this->tester->testAction('default');
	}

	public function test404Render() {
		$tester = $this->tester; // PHP 5.3
		Tester\Assert::exception(function () use ($tester) {
			$tester->testAction('404');
		}, 'Nette\Application\BadRequestException');
		Tester\Assert::same(404, $this->tester->getReturnCode());
	}

	public function test500Render() {
		$tester = $this->tester; // PHP 5.3
		Tester\Assert::exception(function () use ($tester) {
			$tester->testAction('fail');
		}, 'Nette\Application\BadRequestException');
		Tester\Assert::same(500, $this->tester->getReturnCode());
	}

	public function testRenderException() {
		$tester = $this->tester; // PHP 5.3
		Tester\Assert::exception(function () use ($tester) {
			$tester->testAction('exception');
		}, 'Latte\CompileException');
		Tester\Assert::type('Latte\CompileException', $this->tester->getException());
	}

	public function testRedirect() {
		$this->tester->testRedirect('redirect');
	}

	public function testJsonOutput() {
		$this->tester->testJson('json');
	}

	public function testRss() {
		$this->tester->testRss('rss');
	}

	public function testSitemap() {
		$this->tester->testSitemap('sitemap');
	}

	public function testUserLogIn() {
		$user = $this->tester->logIn();
		Tester\Assert::true($user->isLoggedIn());
	}

	public function testUserLogInWithId() {
		$user = $this->tester->logIn(1);
		Tester\Assert::true($user->isLoggedIn());
		Tester\Assert::same(1, $user->identity->id);
	}

	public function testUserLogInWithIdRole() {
		$user = $this->tester->logIn(1, 'admin');
		Tester\Assert::true($user->isLoggedIn());
		Tester\Assert::same(1, $user->identity->id);
		Tester\Assert::true($user->isInRole('admin'));
	}

	public function testUserLogInWithIdRoles() {
		$user = $this->tester->logIn(1, array('test1', 'test2'));
		Tester\Assert::true($user->isLoggedIn());
		Tester\Assert::same(1, $user->identity->id);
		Tester\Assert::true($user->isInRole('test1'));
		Tester\Assert::true($user->isInRole('test2'));
		Tester\Assert::false($user->isInRole('admin'));
	}

	public function testUserLogOut() {
		$user = $this->tester->logOut();
		Tester\Assert::false($user->isLoggedIn());
	}

	public function testPresenterInstance() {
		Tester\Assert::type('Nette\Application\UI\Presenter', $this->tester->getPresenter());
	}

	public function testForm() {
		$this->tester->testForm('default', 'form', array(
			'test' => 'test',
		));
	}

	public function testSignal() {
		$this->tester->testSignal('default', 'signal');
	}

}

$test = new PresenterTest($container);
$test->run();
