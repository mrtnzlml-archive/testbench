[![Build Status](https://travis-ci.org/mrtnzlml/testbench.svg?branch=master)](https://travis-ci.org/mrtnzlml/testbench)

Heavily inspired by [Kdyby\TesterExtras](https://github.com/Kdyby/TesterExtras).

Simple test bench for Nette Framework projects
----------------------------------------------
Write tests as simple as possible. This project helps you to write tests very quickly. DRY! The main goal of this project is to make testing very simple for everyone.

You can find few examples in this readme or take a look to the `tests` folder in this project.

Minimal code
-----------
At first you need classic bootstrap file. It can be really simple:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$loader = new \Nette\Loaders\RobotLoader();
$loader->setCacheStorage(new \Nette\Caching\Storages\MemoryStorage());
$loader->addDirectory(__DIR__ . '/../app');
$loader->addDirectory(__DIR__ . '/../custom');
$loader->addDirectory(__DIR__ . '/../libs');
$loader->register();

Test\Bootstrap::setup(__DIR__);
```

It's important, that we are not creating dependency injection container here. You can use [autoload](https://getcomposer.org/doc/04-schema.md#autoload) from composer if you don't want to use robot loader. It's usually very useful to create own test case which inherits from original one:

```php
<?php

class PresenterTestCase extends Tester\TestCase
{

	use Test\PresenterTester {
		Test\PresenterTester::createContainer as parentCreateContainer;
	}

	protected function createContainer()
	{
		return $this->parentCreateContainer([
			__DIR__ . '/../app/config/config.neon',
		]);
	}

}
```

With this test case, testing is really easy:

```php
<?php //HomepagePresenterTest.phpt

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class HomepagePresenterTest extends \PresenterTestCase
{

	public function __construct()
	{
		$this->openPresenter('Homepage:');
	}

	public function testRenderDefault()
	{
		$this->checkAction('default');
	}

}

(new HomepagePresenterTest())->run();
```

Testing modules
===============
It's simple. Just init bench with `Module:Presenter:` name like this:

```php
public function __construct()
{
	$this->openPresenter('Module:Presenter:');
}
```

You should always use full presenter name.

Testing restricted areas
========================
```php
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
public function tearDown()
{
	$this->logOut();
}
```

Testing signals
===============
```php
public function testSignal()
{
	$this->checkSignal('action-name', 'signal-name');
}
```

Testing forms
=============
```php
public function testSearchForm()
{
	$this->checkForm('action-name', 'form-name', array(
		'input' => 'value',
	));
}
```

It's just simple stupid test. You are the tester so you can do whatever you want after this test:
```php
public function testSearchForm()
{
	$response = $this->checkForm('action-name', 'form-name', array(
		'input' => 'value',
	));
	
	//Tester\Assert::... with $response
}
```

Testing return codes
====================
```php
public function test404Render()
{
	$this->checkAction('404');
	Tester\Assert::same(404, $this->getReturnCode());
	
	// OR:
	
	Tester\Assert::exception(function () {
		$this->checkAction('404');
	}, 'Nette\Application\BadRequestException');
}
```

Testing exceptions
==================
I don't think this is very useful, but:
```php
public function testRenderException()
{
	$this->checkAction('exception');
	Tester\Assert::type('Latte\CompileException', $this->getException());
}
```

It's better to use classic exception test:
```php
public function testRenderException()
{
	Tester\Assert::exception(function () {
		$this->checkAction('exception');
	}, 'Latte\CompileException');
}
```

Testing JSON output
===================
Still in progress. But for now:
```php
public function testJsonOutput()
{
	$this->checkJson('json-action');
}
```

Testing RSS and Sitemaps
========================
```php
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
===============
Look at the tests in this project. You'll see how to use it properly.
