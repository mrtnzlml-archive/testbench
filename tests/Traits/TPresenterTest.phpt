<?php

namespace Tests\Traits;

use Tester\Assert;
use Tester\Dumper;

require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class TPresenterTest extends \Testbench\CustomPresenterTestCase
{

	public function testClassicRender()
	{
		$this->checkAction('Presenter:default');

		Assert::error(function () {
			$this->checkAction('Presenter:variabledoesntexist');
		}, E_NOTICE, 'Undefined variable: doesnexist');
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
		$this->checkRedirect('Presenter:redirect', '/x/y');
	}

	public function testRedirectRss()
	{
		$this->checkRedirect('Presenter:redirectRss', '/x/y/rss');
		$this->checkRedirect('Presenter:redirectRss', '/.*');
		$this->checkRedirect('Presenter:redirectRss', '/(x|y)/(x|y)/.?s{2}');
	}

	public function testRedirectRssFailedUrl()
	{
		$path = Dumper::color('yellow') . Dumper::toLine('/x/y/rs') . Dumper::color('white');
		$url = Dumper::color('yellow') . Dumper::toLine('http://test.bench/x/y/rss') . Dumper::color('white');
		Assert::error(function () {
			$this->checkRedirect('Presenter:redirectRss', '/x/y/rs', [
				'flashMessage' => FALSE,
			]);
		}, 'Tester\AssertException', str_repeat(' ', 4) . "path $path doesn't match\n$url\nafter redirect");
	}

	public function testJsonOutput()
	{
		$this->checkJsonScheme('Presenter:json', [
			'string' => [
				1234 => [],
			],
		]);
		Assert::exception(function () {
			$this->checkJsonScheme('Presenter:json', ['string']);
		}, 'Tester\AssertException');
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
		], '/x/y');

		Assert::exception(function () {
			$this->checkForm('Presenter:default', 'form1', []);
		}, 'Tester\AssertException', "field 'test' returned this error(s):\n  - This field is required.");

		Assert::exception(function () {
			$this->checkForm('Presenter:default', 'form1', [
				'test' => 'test',
				'error' => 'FORM ERROR',
			]);
		}, 'Tester\AssertException', "Intended error: FORM ERROR");

		Assert::exception(function () {
			$this->checkForm('Presenter:default', 'form1', [
				'test' => 'test',
			]); //missing path
		}, 'Tester\AssertException');
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
		], '/x/y');
	}

	public function testSignal()
	{
		$this->checkSignal('Presenter:default', 'signal');
	}

	public function testAjaxSignal()
	{
		/** @var \Nette\Application\Responses\JsonResponse $response */
		$response = $this->checkAjaxSignal('Presenter:default', 'ajaxSignal');
		Assert::same(['ok'], $response->getPayload());
	}

	public function testFormEnhanced()
	{
		$this->checkForm('Presenter:default', 'form1', [
			'a' => 'b',
			'test' => [
				\Nette\Forms\Form::REQUIRED => TRUE,
				'value',
			],
		], '/x/y');
		Assert::same(
			'{"test":"value","error":""}',
			$this->getPresenter()->getFlashSession()->getIterator()->getArrayCopy()['flash'][0]->message
		);
		Assert::exception(function () {
			$this->checkForm('Presenter:default', 'form4', [
				'a' => 'b',
				'test' => [
					'value',
					\Nette\Forms\Form::REQUIRED => TRUE,
				],
			], '/x/y');
		}, 'Tester\AssertException', "field 'test' should be defined as required, but it's not");
	}

	public function testUserLoggedIn()
	{
		Assert::false($this->isUserLoggedIn());
		$this->logIn();
		Assert::true($this->isUserLoggedIn());
		$this->logOut();
		Assert::false($this->isUserLoggedIn());
	}
}

(new TPresenterTest)->run();
