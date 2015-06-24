<?php

namespace Test;

use Tester;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class PresenterTest extends CustomTestCase
{

	public function __construct()
	{
		$this->openPresenter('Presenter:');
	}

	public function testClassicRender()
	{
		$this->checkAction('default');
	}

	public function test404Render()
	{
		Tester\Assert::exception(function () {
			$this->checkAction('404');
		}, 'Nette\Application\BadRequestException');
		Tester\Assert::same(404, $this->getReturnCode());
	}

	public function test500Render()
	{
		Tester\Assert::exception(function () {
			$this->checkAction('fail');
		}, 'Nette\Application\BadRequestException');
		Tester\Assert::same(500, $this->getReturnCode());
	}

	public function testRenderException()
	{
		Tester\Assert::exception(function () {
			$this->checkAction('exception');
		}, 'Latte\CompileException');
		Tester\Assert::type('Latte\CompileException', $this->getException());
	}

	public function testRedirect()
	{
		$this->checkRedirect('redirect', '/');
	}

	public function testRedirectRss()
	{
		$this->checkRedirect('redirectRss', '/presenter/rss');
	}

	public function testJsonOutput()
	{
		$this->checkJson('json');
	}

	public function testRss()
	{
		$this->checkRss('rss');
	}

	public function testSitemap()
	{
		$this->checkSitemap('sitemap');
	}

	public function testUserLogIn()
	{
		$user = $this->logIn();
		Tester\Assert::true($user->isLoggedIn());
	}

	public function testUserLogInWithId()
	{
		$user = $this->logIn(1);
		Tester\Assert::true($user->isLoggedIn());
		Tester\Assert::same(1, $user->identity->id);
	}

	public function testUserLogInWithIdRole()
	{
		$user = $this->logIn(1, 'admin');
		Tester\Assert::true($user->isLoggedIn());
		Tester\Assert::same(1, $user->identity->id);
		Tester\Assert::true($user->isInRole('admin'));
	}

	public function testUserLogInWithIdRoles()
	{
		$user = $this->logIn(1, ['test1', 'test2']);
		Tester\Assert::true($user->isLoggedIn());
		Tester\Assert::same(1, $user->identity->id);
		Tester\Assert::true($user->isInRole('test1'));
		Tester\Assert::true($user->isInRole('test2'));
		Tester\Assert::false($user->isInRole('admin'));
	}

	public function testUserLogOut()
	{
		$user = $this->logOut();
		Tester\Assert::false($user->isLoggedIn());
	}

	public function testPresenterInstance()
	{
		Tester\Assert::type('Nette\Application\UI\Presenter', $this->getPresenter());
	}

	public function testForm()
	{
		$this->checkForm('default', 'form', [
			'test' => 'test',
		]);
	}

	public function testSignal()
	{
		$this->checkSignal('default', 'signal');
	}

}

(new PresenterTest())->run();
