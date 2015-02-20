Simple test bench for Nette Framework
=====================================
Write tests as simple as possible. This project helps you to write tests very quickly. DRY!

Minimal code
============

```php
<?php //HomepagePresenterTest.phpt

namespace Test;

use Nette;
use Tester;

$container = require __DIR__ . '/../bootstrap.php';

class HomepagePresenterTest extends Tester\TestCase {

	private $container;
	private $tester;

	function __construct(Nette\DI\Container $container) {
		$this->container = $container;
		$this->tester = new Presenter($container);
	}

	function setUp() {
		$this->tester->init('Homepage'); //init bench with the presenter name
	}

	function testRenderDefault() {
		$this->tester->testAction('default'); //test your action
	}

}

$test = new HomepagePresenterTest($container);
$test->run();
```

Testing modules
===============
It's simple. Just init bench with Module:Presenter name like this:

```php
function setUp() {
	$this->tester->init('Admin:Presenter'); //init bench with the module:presenter name
}
```

Testing restricted areas
========================
```php
function setUp() {
	$this->tester->init('Admin:Presenter'); //init bench with the module:presenter name
	$this->tester->logIn();
	//OR:
	$this->tester->logIn(1); //with user ID
	$this->tester->logIn(1, 'role'); //with user ID and role
	$this->tester->logIn(1, ['role1', 'role2']); //with user ID and roles
	$this->tester->logIn(1, ['role1', 'role2'], ['data']); //with user ID and roles and additional data
}
```

You can use logout as well:
```php
function tearDown() {
	$this->tester->logOut();
}
```

Testing forms
=============
```php
function testSearchForm() {
	$response = $this->tester->test('default', 'POST', array(
		'do' => 'search-submit',
	), array(
		'search' => 'test',
	));
	Tester\Assert::true($response instanceof Nette\Application\Responses\RedirectResponse);
}
```