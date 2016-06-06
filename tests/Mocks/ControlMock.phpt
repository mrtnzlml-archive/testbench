<?php

namespace Tests\Mocks;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$latte = new \Latte\Engine;
$latte->setLoader(new \Latte\Loaders\StringLoader);
\Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

$params['_control'] = new \Testbench\Mocks\ControlMock;
Assert::type('Nette\Application\UI\Control', $params['_control']);
Assert::type('Nette\Application\UI\Control', new \Testbench\ControlMock); //BC

Assert::match(
	'<a href="link|data!(0=10)"></a>',
	$latte->renderToString('<a n:href="data! 10"></a>', $params)
);

Assert::match(
	'<a href="link|data!#hash(0=10, a=20, b=30)"></a>',
	$latte->renderToString('<a n:href="data!#hash 10, a => 20, \'b\' => 30"></a>', $params)
);

Assert::match(
	'<a href="link|Homepage:"></a>',
	$latte->renderToString('<a n:href="Homepage:"></a>', $params)
);
