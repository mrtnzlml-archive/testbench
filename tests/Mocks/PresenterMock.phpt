<?php

namespace Tests\Mocks;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$latte = new \Latte\Engine;
$latte->setLoader(new \Latte\Loaders\StringLoader);
\Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

$params['_control'] = $mock = new \Testbench\Mocks\PresenterMock;
Assert::type('Testbench\Mocks\PresenterMock', new \Testbench\PresenterMock);

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
	$latte->renderToString('<a n:href="data! 10"></a>', $params)
);

Assert::match(
	'<a href="plink|data!#hash(0=10, a=20, b=30)"></a>',
	$latte->renderToString('<a n:href="data!#hash 10, a => 20, \'b\' => 30"></a>', $params)
);

Assert::match(
	'<a href="plink|Homepage:"></a>',
	$latte->renderToString('<a n:href="Homepage:"></a>', $params)
);
