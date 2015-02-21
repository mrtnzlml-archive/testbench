<?php

namespace Test;

use Nette;
use Tester;

$container = require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class PresenterTest extends Tester\TestCase {

	private $tester;

	public function __construct(Nette\DI\Container $container) {
		$this->tester = new Presenter($container);
	}

	public function setUp() {
		$this->tester->init('Presenter');
	}

	public function testClassicRender() {
		$this->tester->testAction('default');
		Tester\Assert::same(200, $this->tester->getReturnCode());
	}

	public function test404Render() {
		$this->tester->testAction('404'); //FIXME: maybe special method in testBench?
		Tester\Assert::same(404, $this->tester->getReturnCode());
	}

	public function test500Render() {
		$this->tester->testAction('fail'); //FIXME: maybe special method in testBench?
		Tester\Assert::same(500, $this->tester->getReturnCode());
	}

	public function testJsonOutput() {
		$this->tester->testJson('json');
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
		Tester\Assert::true($this->tester->getPresenter() instanceof Nette\Application\UI\Presenter);
	}

	//TODO:
//	public function testForm() {
//		$response = $this->tester->test('default', 'POST', array(
//			'do' => 'form-submit',
//		), array(
//			'test' => 'test',
//		));
//		Tester\Assert::true($response instanceof Nette\Application\Responses\RedirectResponse);
//	}

}

$test = new PresenterTest($container);
$test->run();
