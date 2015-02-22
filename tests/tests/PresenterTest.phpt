<?php

namespace Test;

use Nette;
use Tester;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class PresenterTest extends Tester\TestCase {

	private $tester;

	public function __construct(Nette\DI\Container $container) {
		$this->tester = new PresenterTester($container, 'Presenter');
	}

	public function testClassicRender() {
		$this->tester->testAction('default');
	}

	public function test404Render() {
		$this->tester->testAction('404');
		Tester\Assert::same(404, $this->tester->getReturnCode());
	}

	public function test500Render() {
		$this->tester->testAction('fail');
		Tester\Assert::same(500, $this->tester->getReturnCode());
	}

	public function testRenderException() {
		$this->tester->testAction('exception');
		Tester\Assert::type('Latte\CompileException', $this->tester->getException());
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
		$user = $this->tester->logIn(1, ['test1', 'test2']);
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

}

$test = new PresenterTest($container);
$test->run();
