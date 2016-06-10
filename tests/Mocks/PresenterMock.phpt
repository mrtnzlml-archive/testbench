<?php

namespace Ant\Tests\Latte;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$latte = new \Latte\Engine;
$latte->setLoader(new \Latte\Loaders\StringLoader);
\Nette\Bridges\ApplicationLatte\UIMacros::install($latte->getCompiler());

$params['_control'] = new \Testbench\PresenterMock;

$renderToString = function ($template, $params = []) use ($latte) {
	return strtr($latte->renderToString($template, $params), ['&#039;' => "'"]);
};

Assert::match(
	"<a href=\"plink:['data!',10]\"></a>",
	$renderToString('<a n:href="data! 10"></a>', $params)
);

Assert::match(
	"<a href=\"plink:{'0':'data!#hash','1':10,'a':20,'b':30}\"></a>",
	$renderToString('<a n:href="data!#hash 10, a => 20, \'b\' => 30"></a>', $params)
);

Assert::match(
	"<a href=\"plink:['Homepage:']\"></a>",
	$renderToString('<a n:href="Homepage:"></a>', $params)
);
