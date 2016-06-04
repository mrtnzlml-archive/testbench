<?php

namespace Ant\Tests\Latte;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

Assert::type(\Nette\Application\UI\Control::class, new \Testbench\ControlMock);
