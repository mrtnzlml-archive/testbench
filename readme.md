Simple test bench for Nette Framework projects
==============================================
Write tests as simple as possible. This project helps you to write tests very quickly. DRY!

Minimal code
============

```php
<?php //HomepagePresenterTest.phpt

namespace Test;

use Nette;
use Tester;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class HomepagePresenterTest extends Tester\TestCase {

	private $tester;

	public function __construct(Nette\DI\Container $container) {
		$this->tester = new PresenterTester($container, 'Homepage'); //init bench with the presenter name
	}

	public function testRenderDefault() {
		$this->tester->testAction('default'); //test your action
	}

}

$test = new HomepagePresenterTest($container);
$test->run();
```

There is also `init` function so you can init tester with presenter name in the `setUp` function like this:

```php
public public function setUp() {
	$this->tester->init('Homepage');
}
```

Testing modules
===============
It's simple. Just init bench with `Module:Presenter` name like this:

```php
public function __construct(Nette\DI\Container $container) {
	$this->tester = new PresenterTester($container, 'Admin:Presenter'); //init bench with the module:presenter name
}

// OR:

public function setUp() {
	$this->tester->init('Admin:Presenter'); //init bench with the module:presenter name
}
```

Testing restricted areas
========================
```php
public function setUp() {
	$this->tester->init('Admin:Presenter'); //init bench with the module:presenter name
	$this->tester->logIn();
	// OR:
	$this->tester->logIn(1); //with user ID
	$this->tester->logIn(1, 'role'); //with user ID and role
	$this->tester->logIn(1, ['role1', 'role2']); //with user ID and roles
	$this->tester->logIn(1, ['role1', 'role2'], ['data']); //with user ID and roles and additional data
}
```

You can use logout as well:
```php
public function tearDown() {
	$this->tester->logOut();
}
```

Testing forms
=============
```php
public function testSearchForm() {
	$this->tester->testForm('action-name', 'form-name', array(
		'input' => 'value',
	));
}
```

It's just simple stupid test. You are the tester so you can do whatever you want after this test:
```php
public function testSearchForm() {
	$response = $this->tester->testForm('action-name', 'form-name', array(
		'input' => 'value',
	));
	
	//Tester\Assert::... with $response
}
```

Testing return codes
====================
```php
public function test404Render() {
	$this->tester->testAction('404');
	Tester\Assert::same(404, $this->tester->getReturnCode());
}
```

Testing exceptions
==================
I don't think this is very useful, but:
```php
public function testRenderException() {
	$this->tester->testAction('exception');
	Tester\Assert::type('Latte\CompileException', $this->tester->getException());
}
```

Testing JSON output
===================
Still in progress. But for now:
```php
public function testJsonOutput() {
	$this->tester->testJson('json');
}
```

Testing RSS and Sitemaps
========================
```php
public function testRss() {
	$this->tester->testRss('rss');
}

public function testSitemap() {
	$this->tester->testSitemap('sitemap');
}
```

This test expects minimal template for RSS:
```
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
```
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