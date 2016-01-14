<?php

namespace Ant\Tests\Latte;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$latte = new \Latte\Engine;
$latte->setLoader(new \Latte\Loaders\StringLoader);
\Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

$params['_control'] = new \Ant\Tests\ControlMock;

Assert::match(
	"<a href=\"link:['data!',10]\"></a>",
	$latte->renderToString('<a n:href="data! 10"></a>', $params)
);

Assert::match(
	"<a href=\"link:{'0':'data!#hash','1':10,'a':20,'b':30}\"></a>",
	$latte->renderToString('<a n:href="data!#hash 10, a => 20, \'b\' => 30"></a>', $params)
);

Assert::match(
	"<a href=\"link:['Homepage:']\"></a>",
	$latte->renderToString('<a n:href="Homepage:"></a>', $params)
);
