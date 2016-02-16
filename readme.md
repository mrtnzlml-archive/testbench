[![Build Status](https://travis-ci.org/mrtnzlml/testbench.svg?branch=master)](https://travis-ci.org/mrtnzlml/testbench)

Tested against PHP 5.4, 5.5, 5.6 and 7.0. Please read [this wiki](https://github.com/mrtnzlml/testbench/wiki).

Heavily inspired by these GitHub projects:
- [Kdyby](https://github.com/Kdyby/TesterExtras), [Librette](https://github.com/librette), [Nette](https://github.com/nette) tests

And article(s):
- [Bootstrap your integration testing database](https://jiripudil.cz/blog/bootstrap-your-integration-testing-database) (Jiří Pudil)

Simple test bench for Nette Framework projects
----------------------------------------------
Write integration tests as simple as possible. This project helps you to write tests very quickly. DRY! The main goal of this project is to make testing very simple for everyone and help with the difficult start.

You can find few examples in this readme or take a look to the `tests` folder in this project.

Installation
------------

```sh
$ composer require mrtnzlml/testbench
```

Minimal code
------------
At first you need classic bootstrap file (just example, DIY):

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

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
testbench:
	sqls: #what should be loaded after empty database creation
		- %appDir%/../sqls/1.sql
		- %appDir%/../sqls/2.sql
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

You can easily write cover with tests UI\Controls, restricted areas, forms, signals, redirects, ...

Please read [this article](http://zlml.cz/jednoduche-testovani-pro-uplne-kazdeho).

Give it a shot!
-----------
Look at the tests in this project. You'll see how to use it properly. There are examples in `tests` folder or in the wiki. Learn how to use these traits:

- [Testbench\TCompiledContainer](https://github.com/mrtnzlml/testbench/wiki/Testbench%5CTCompiledContainer)
- [Testbench\TComponent](https://github.com/mrtnzlml/testbench/wiki/Testbench%5CTComponent)
- [Testbench\TDoctrine](https://github.com/mrtnzlml/testbench/wiki/Testbench%5CTDoctrine)
- [Testbench\TPresenter](https://github.com/mrtnzlml/testbench/wiki/Testbench%5CTPresenter)
