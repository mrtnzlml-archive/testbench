<?php

namespace Tests\Mocks;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$latte = new \Latte\Engine;
$latte->setLoader(new \Latte\Loaders\StringLoader);
$latte->addProvider('uiControl', new \Testbench\Mocks\ControlMock);
\Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

Assert::type('Nette\Application\UI\Control', $latte->getProviders()['uiControl']);
Assert::type('Nette\Application\UI\Control', new \Testbench\ControlMock); //BC

Assert::match(
	'<a href="link|data!(0=10)"></a>',
	$latte->renderToString('<a n:href="data! 10"></a>')
);

Assert::match(
	'<a href="link|data!#hash(0=10, a=20, b=30)"></a>',
	$latte->renderToString('<a n:href="data!#hash 10, a => 20, \'b\' => 30"></a>')
);

Assert::match(
	'<a href="link|Homepage:"></a>',
	$latte->renderToString('<a n:href="Homepage:"></a>')
);
