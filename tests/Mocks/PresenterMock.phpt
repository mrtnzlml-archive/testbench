<?php

namespace Tests\Mocks;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$latte = new \Latte\Engine;
$latte->setLoader(new \Latte\Loaders\StringLoader);
$latte->addProvider('uiControl', new \Testbench\Mocks\PresenterMock);
\Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

/** @var \Testbench\Mocks\PresenterMock $mock */
$mock = $latte->getProviders()['uiControl'];
Assert::type('Testbench\Mocks\PresenterMock', new \Testbench\PresenterMock); //BC

Assert::false($mock->isAjax());

Assert::noError(function () use ($mock) {
	$mock->link('Inva:lid');
	Assert::null($mock->invalidLinkMode);
});

Assert::exception(function () use ($mock) {
	$mock->afterRender();
}, 'Nette\Application\AbortException');

$mock->loadState(['__terminate' => TRUE]);
Assert::exception(function () use ($mock) {
	$mock->startup();
}, 'Nette\Application\AbortException');

Assert::match(
	'<a href="plink|data!(0=10)"></a>',
	$latte->renderToString('<a n:href="data! 10"></a>')
);

Assert::match(
	'<a href="plink|data!#hash(0=10, a=20, b=30)"></a>',
	$latte->renderToString('<a n:href="data!#hash 10, a => 20, \'b\' => 30"></a>')
);

Assert::match(
	'<a href="plink|Homepage:"></a>',
	$latte->renderToString('<a n:href="Homepage:"></a>')
);
