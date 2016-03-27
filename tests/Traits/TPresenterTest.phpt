<?php

namespace Test;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class TPresenterTest extends \Testbench\CustomPresenterTestCase
{

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

	public function testRenderBrokenLink()
	{
		$this->checkAction('Presenter:brokenLink'); //FIXME: should fail (?)
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

	public function testUserLogInWithIdentity()
	{
		$user = $this->logIn($identity = new \Nette\Security\Identity(123, ['Role_1', 'Role_2']), ['Role_3']);

		Assert::true($user->isLoggedIn());
		Assert::same($identity, $user->getIdentity());
		Assert::same(123, $user->getIdentity()->getId());
		Assert::true($user->isInRole('Role_1'));
		Assert::true($user->isInRole('Role_2'));
		Assert::false($user->isInRole('Role_3'));
		Assert::same(['Role_1', 'Role_2'], $user->getRoles());
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
		$this->checkForm('Presenter:default', 'form1', [
			'test' => 'test',
		]);
	}

	public function testFormDifferentDestination()
	{
		$this->checkForm('Presenter:default', 'form2', [
			'test' => 'test',
		], '/x/y/json');
	}

	public function testFormWithoutRedirect()
	{
		$this->checkForm('Presenter:default', 'form3', [
			'test' => 'test',
		], FALSE); //do not check redirect
	}

	public function testAjaxForm()
	{
		$this->checkForm('Presenter:default', 'ajaxForm', [
			'test' => 'test',
		], '/x/y/json');

		$this->checkAjaxForm('Presenter:default', 'ajaxForm', [
			'test' => 'test',
		]);

		$this->checkAjaxForm('Presenter:default', 'ajaxForm', [
			'test' => 'test',
		], '/x/y/json');
	}

	public function testCsrfForm()
	{
		$this->checkForm('Presenter:default', 'csrfForm', [
			'test' => 'test',
		]);
	}

	public function testSignal()
	{
		$this->checkSignal('Presenter:default', 'signal');
	}

}

(new TPresenterTest)->run();
