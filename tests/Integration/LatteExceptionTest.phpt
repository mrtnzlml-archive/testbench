<?php

namespace Test;

use Tester;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @exitCode 255
 */
class LatteExceptionTest extends CustomTestCase
{

    public function __construct()
    {
        $this->openPresenter('Presenter:');
    }

    public function testLatteException()
    {
        //E_USER_ERROR: Exception in Nette\Bridges\ApplicationLatte\Template::__toString(): Component with name 'nonExistentComponent' does not exist. in vendor/nette/component-model/src/ComponentModel/Container.php:162
        $this->checkAction('latteException');
    }

}

(new LatteExceptionTest())->run();
