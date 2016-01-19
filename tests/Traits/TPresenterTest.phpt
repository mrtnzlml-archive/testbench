<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TPresenterTest extends \Tester\TestCase
{

	use \Testbench\TPresenter;

	public function testClassicRender()
	{
		$this->checkAction('Presenter:default');
	}

	public function testClassicRenderShort()
	{
		$this->checkAction('Presenter:');
	}

	public function testClassicRenderFqn()
	{
		$this->checkAction(':Presenter:default');
	}

	public function test404Render()
	{
		Assert::exception(function () {
			$this->checkAction('Presenter:404');
		}, 'Nette\Application\BadRequestException');
		Assert::same(404, $this->getReturnCode());
	}

	public function test500Render()
	{
		Assert::exception(function () {
			$this->checkAction('Presenter:fail');
		}, 'Nette\Application\BadRequestException');
		Assert::same(500, $this->getReturnCode());
	}

	public function testRenderException()
	{
		Assert::exception(function () {
			$this->checkAction('Presenter:exception');
		}, 'Latte\CompileException');
		Assert::type('Latte\CompileException', $this->getException());
	}

	public function testRedirect()
	{
		$this->checkRedirect('Presenter:redirect', '/');
	}

	public function testRedirectRss()
	{
		$this->checkRedirect('Presenter:redirectRss', '/x/y/rss');
	}

	public function testJsonOutput()
	{
		$this->checkJson('Presenter:json');
	}

	public function testRss()
	{
		$this->checkRss('Presenter:rss');
	}

	public function testSitemap()
	{
		$this->checkSitemap('Presenter:sitemap');
	}

	public function testUserLogIn()
	{
		$user = $this->logIn();
		Assert::true($user->isLoggedIn());
	}

	public function testUserLogInWithId()
	{
		$user = $this->logIn(1);
		Assert::true($user->isLoggedIn());
		Assert::same(1, $user->identity->id);
	}

	public function testUserLogInWithIdRole()
	{
		$user = $this->logIn(1, 'admin');
		Assert::true($user->isLoggedIn());
		Assert::same(1, $user->identity->id);
		Assert::true($user->isInRole('admin'));
	}

	public function testUserLogInWithIdRoles()
	{
		$user = $this->logIn(1, ['test1', 'test2']);
		Assert::true($user->isLoggedIn());
		Assert::same(1, $user->identity->id);
		Assert::true($user->isInRole('test1'));
		Assert::true($user->isInRole('test2'));
		Assert::false($user->isInRole('admin'));
	}

	public function testUserLogOut()
	{
		$user = $this->logOut();
		Assert::false($user->isLoggedIn());
	}

	public function testPresenterInstance()
	{
		Assert::null($this->getPresenter()); //presenter is not open yet
		$this->checkAction('Presenter:default');
		Assert::type('Nette\Application\UI\Presenter', $this->getPresenter()); //presenter is not open yet
	}

	public function testForm()
	{
		$this->checkForm('Presenter:default', 'form', [
			'test' => 'test',
		]);
	}

	public function testSignal()
	{
		$this->checkSignal('Presenter:default', 'signal');
	}

}

(new TPresenterTest)->run();
