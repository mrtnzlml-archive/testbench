<?php

namespace Ant\Tests\Latte;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mock = new \Testbench\PresenterMock;

Assert::false($mock->isAjax());

Assert::exception(function () use ($mock) {
	$mock->link('Inva:lid');
	Assert::same(\Nette\Application\UI\Presenter::INVALID_LINK_EXCEPTION, $mock->invalidLinkMode);
}, 'Nette\InvalidStateException');

Assert::exception(function () use ($mock) {
	$mock->afterRender();
}, 'Nette\Application\AbortException');

$mock->loadState(['__terminate' => TRUE]);
Assert::exception(function () use ($mock) {
	$mock->startup();
}, 'Nette\Application\AbortException');
