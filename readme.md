[![Build Status](https://travis-ci.org/mrtnzlml/testbench.svg?branch=master)](https://travis-ci.org/mrtnzlml/testbench)

Tested against PHP 5.4, 5.5, 5.6, 7.0 and HHVM.

Heavily inspired by these GitHub projects:
- [Kdyby](https://github.com/Kdyby/TesterExtras) tests
- [Librette](https://github.com/librette) tests
- [Nette](https://github.com/nette) tests

And articles:
- [Bootstrap your integration testing database](https://jiripudil.cz/blog/bootstrap-your-integration-testing-database) (Jiří Pudil)

Simple test bench for Nette Framework projects
----------------------------------------------
Write integration tests as simple as possible. This project helps you to write tests very quickly. DRY! The main goal of this project is to make testing very simple for everyone and help with the difficult start.

You can find few examples in this readme or take a look to the `tests` folder in this project.

Minimal code
-----------
At first you need classic bootstrap file (just example, DIY):

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

Tracy\Debugger::enable(TRUE);
//only next line is important:
Testbench\Bootstrap::setup(__DIR__ . '/_temp', function (\Nette\Configurator $configurator) {
	$configurator->createRobotLoader()->addDirectory([
		__DIR__ . '/../app',
	])->register();
	$configurator->addParameters([
		'appDir' => __DIR__ . '/../app',
	]);
	$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
	$configurator->addConfig(__DIR__ . '/tests.neon');
});
```

It's important, that we are not creating dependency injection container here. You can use [autoload](https://getcomposer.org/doc/04-schema.md#autoload) from composer if you don't want to use robot loader.
You should also create config file e.g. `tests.neon`. This file is needed only for database tests at this moment (Doctrine only - stay tuned). In this file you should configure your project before tests:

```neon
doctrine:
	wrapperClass: Testbench\ConnectionMock

testbench:
	dbname: cms_new #probably same as doctrine:dbname (I am looking for better solution)
	sqls: #what should be loaded after empty database creation
		- %appDir%/../sqls/1.sql
```

And you are ready to go:

```php
<?php //HomepagePresenterTest.phpt

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class HomepagePresenterTest extends \Tester\TestCase
{

	use \Testbench\TPresenter;

	public function testRenderDefault()
	{
		$this->checkAction('Homepage:default');
	}

	public function testRenderDefaultModule()
    {
        $this->checkAction('Module:Homepage:default');
    }

}

(new HomepagePresenterTest())->run();
```

Testing restricted areas
-----------
```php
use \Testbench\TPresenter;
public function setUp()
{
	$this->logIn();
	// OR:
	$this->logIn(1); //with user ID
	$this->logIn(1, 'role'); //with user ID and role
	$this->logIn(1, ['role1', 'role2']); //with user ID and roles
	$this->logIn(1, ['role1', 'role2'], ['data']); //with user ID and roles and additional data
}
```

You can use logout as well:
```php
use \Testbench\TPresenter;
public function tearDown()
{
	$this->logOut();
}
```

Testing signals
-----------
```php
use \Testbench\TPresenter;
public function testSignal()
{
	$this->checkSignal('action-name', 'signal-name');
}
```

Testing forms
-----------
```php
use \Testbench\TPresenter;
public function testSearchForm()
{
	$this->checkForm('action-name', 'form-name', [
		'input' => 'value',
	]);
}
```

It's just simple stupid test. Testbench is going to help you only with basic and boring tasks. You are the tester so you can do whatever you want after this test:
```php
use \Testbench\TPresenter;
public function testSearchForm()
{
	$response = $this->checkForm('action-name', 'form-name', [
		'input' => 'value',
	]);

	//Tester\Assert::... with $response
}
```

Testing redirects
-----------
```php
use \Testbench\TPresenter;
public function testRedirect()
{
	$this->checkRedirect('action-name');
	$this->checkRedirect('action-name', '/presenter/action'); //optional destination URL
}
```

Testing UI\Control render
-----------
See: https://tester.nette.org/#toc-assert-match
```php
use \Testbench\TComponent;
public function testComponentRender()
{
	$control = new \Component;
	$this->checkRenderOutput($control, '<strong>OK%A%'); //match string
	$this->checkRenderOutput($control, __DIR__ . '/Component.expected'); //match file content
}
```

Working with database (Doctrine)
-----------
Testbench is taking care of database creation and deletion. It can also load SQLs. Now it's up to you. Get entity manager and do whatever you want to do:
```php
use \Testbench\TDatabaseSetup;
public function testDatabase()
{
	$em = $this->getEntityManager();
	//Tester\Assert::...
}
```
Pretty easy, right?

Testing exceptions
-----------
Yes, this is just pure Nette\Tester. But stay tuned... :)
```php
use \Testbench\TPresenter;
public function testRenderException()
{
	Tester\Assert::exception(function () {
		$this->checkAction('exception');
	}, 'Latte\CompileException');
}
```

Testing JSON output
-----------
Still in progress. It would be nice if you could check output using match method from Nette\Tester. But for now:
```php
use \Testbench\TPresenter;
public function testJsonOutput()
{
	$this->checkJson('json-action');
}
```

Testing RSS and Sitemaps
-----------
```php
use \Testbench\TPresenter;
public function testRss()
{
	$this->checkRss('rss');
}

public function testSitemap()
{
	$this->checkSitemap('sitemap');
}
```

This test expects minimal template for RSS:
```latte
{contentType application/xml; charset=utf-8}
<<?php ?>?xml version="1.0" encoding="UTF-8"?>

<rss version="2.0">
	<channel>
		<title>TITLE</title>
		<link>{link //:Presenter:default}</link>

		<item n:foreach="$posts as $post">
			<title>{$post->title}</title>
			<description>{$post->content}</description>
		</item>
	</channel>
</rss>
```

And Sitemap:
```latte
{contentType application/xml; charset=utf-8}
<<?php ?>?xml version="1.0" encoding="UTF-8"?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url n:foreach="$sitemap as $s">
		<loc>{link //Homepage:default}</loc>
	</url>
</urlset>
```

Give it a shot!
-----------
Look at the tests in this project. You'll see how to use it properly.
